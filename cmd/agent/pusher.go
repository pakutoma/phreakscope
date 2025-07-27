package main

import (
	"bytes"
	"compress/gzip"
	"fmt"
	"io"
	"log"
	"net/http"
	"strings"
	"time"

	"github.com/google/pprof/profile"
)

// Pusher sends profiles to Pyroscope
type Pusher struct {
	url    string
	client *http.Client
}

// NewPusher creates a new Pyroscope pusher
func NewPusher(url string) *Pusher {
	return &Pusher{
		url: url,
		client: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

// Push sends samples to Pyroscope
func (p *Pusher) Push(labels string, samples []Sample) error {
	if len(samples) == 0 {
		return nil
	}

	// Convert to pprof Profile
	prof := p.createProfile(samples)

	// Serialize to protobuf
	var buf bytes.Buffer
	if err := prof.Write(&buf); err != nil {
		return fmt.Errorf("failed to serialize profile: %w", err)
	}

	// Compress with gzip
	var compressed bytes.Buffer
	gw := gzip.NewWriter(&compressed)
	if _, err := io.Copy(gw, &buf); err != nil {
		return fmt.Errorf("failed to compress profile: %w", err)
	}
	if err := gw.Close(); err != nil {
		return fmt.Errorf("failed to close gzip writer: %w", err)
	}

	// Prepare labels for URL
	labelPairs := p.parseLabels(labels)
	
	// Build URL with labels
	url := fmt.Sprintf("%s/ingest?name=phreakscope", p.url)
	for k, v := range labelPairs {
		url += fmt.Sprintf("&%s=%s", k, v)
	}

	// Send to Pyroscope with retries
	var lastErr error
	for attempt := 0; attempt < 3; attempt++ {
		if attempt > 0 {
			time.Sleep(time.Duration(attempt) * time.Second)
		}

		req, err := http.NewRequest("POST", url, bytes.NewReader(compressed.Bytes()))
		if err != nil {
			lastErr = err
			continue
		}

		req.Header.Set("Content-Type", "application/octet-stream")
		req.Header.Set("Content-Encoding", "gzip")

		resp, err := p.client.Do(req)
		if err != nil {
			lastErr = err
			continue
		}

		body, _ := io.ReadAll(resp.Body)
		resp.Body.Close()

		if resp.StatusCode >= 200 && resp.StatusCode < 300 {
			log.Printf("Pushed profile for %s: %d samples, %d bytes compressed", 
				labels, len(samples), compressed.Len())
			return nil
		}

		lastErr = fmt.Errorf("HTTP %d: %s", resp.StatusCode, string(body))
	}

	return fmt.Errorf("failed after 3 attempts: %w", lastErr)
}

// createProfile converts samples to pprof Profile
func (p *Pusher) createProfile(samples []Sample) *profile.Profile {
	prof := &profile.Profile{
		SampleType: []*profile.ValueType{
			{
				Type: "samples",
				Unit: "count",
			},
		},
		TimeNanos:     time.Now().UnixNano(),
		DurationNanos: 30 * 1e9, // 30 seconds
	}

	// Build string table
	stringTable := make(map[string]int)
	getStringID := func(s string) uint64 {
		if id, ok := stringTable[s]; ok {
			return uint64(id)
		}
		id := len(prof.StringTable)
		prof.StringTable = append(prof.StringTable, s)
		stringTable[s] = id
		return uint64(id)
	}

	// Add empty string as first entry
	getStringID("")

	// Function and location cache
	functionCache := make(map[string]*profile.Function)
	locationCache := make(map[string]*profile.Location)
	nextID := uint64(1)

	// Convert samples
	for _, sample := range samples {
		var locations []*profile.Location

		for _, frame := range sample.Stack {
			// Check if it's a file:line format
			parts := strings.Split(frame, ":")
			var funcName, fileName string
			var line int64

			if len(parts) == 2 {
				// file:line format
				fileName = parts[0]
				fmt.Sscanf(parts[1], "%d", &line)
				funcName = fmt.Sprintf("%s:%d", fileName, line)
			} else {
				// Function name only
				funcName = frame
			}

			// Get or create function
			fn, ok := functionCache[funcName]
			if !ok {
				fn = &profile.Function{
					ID:         nextID,
					Name:       getStringID(funcName),
					SystemName: getStringID(funcName),
				}
				if fileName != "" {
					fn.Filename = getStringID(fileName)
				}
				nextID++
				functionCache[funcName] = fn
				prof.Function = append(prof.Function, fn)
			}

			// Get or create location
			loc, ok := locationCache[frame]
			if !ok {
				loc = &profile.Location{
					ID: nextID,
					Line: []profile.Line{
						{
							Function: fn,
							Line:     line,
						},
					},
				}
				nextID++
				locationCache[frame] = loc
				prof.Location = append(prof.Location, loc)
			}

			locations = append(locations, loc)
		}

		// Add sample
		prof.Sample = append(prof.Sample, &profile.Sample{
			Location: locations,
			Value:    []int64{sample.Count},
		})
	}

	return prof
}

// parseLabels converts label string to map
func (p *Pusher) parseLabels(labels string) map[string]string {
	result := make(map[string]string)
	
	// Replace {HOSTNAME} with actual hostname
	hostname, _ := os.Hostname()
	labels = strings.ReplaceAll(labels, "{HOSTNAME}", hostname)
	
	for _, pair := range strings.Split(labels, ",") {
		parts := strings.SplitN(pair, "=", 2)
		if len(parts) == 2 {
			result[strings.TrimSpace(parts[0])] = strings.TrimSpace(parts[1])
		}
	}
	
	return result
}