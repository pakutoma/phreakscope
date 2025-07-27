package main

import (
	"bufio"
	"compress/gzip"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"os"
	"strings"
	"sync"
)

// Sample represents a single stack sample
type Sample struct {
	Stack []string
	Count int64
}

// Collector handles incoming profile data via Unix Domain Socket
type Collector struct {
	socketPath string
	listener   net.Listener
	mu         sync.RWMutex
	profiles   map[string][]Sample // label -> samples
	wg         sync.WaitGroup
}

// NewCollector creates a new collector instance
func NewCollector(socketPath string) (*Collector, error) {
	// Remove existing socket if present
	os.Remove(socketPath)

	listener, err := net.Listen("unix", socketPath)
	if err != nil {
		return nil, fmt.Errorf("failed to listen on %s: %w", socketPath, err)
	}

	// Set permissions
	if err := os.Chmod(socketPath, 0666); err != nil {
		listener.Close()
		return nil, fmt.Errorf("failed to chmod socket: %w", err)
	}

	return &Collector{
		socketPath: socketPath,
		listener:   listener,
		profiles:   make(map[string][]Sample),
	}, nil
}

// Start begins accepting connections
func (c *Collector) Start() {
	c.wg.Add(1)
	defer c.wg.Done()

	for {
		conn, err := c.listener.Accept()
		if err != nil {
			if strings.Contains(err.Error(), "use of closed network connection") {
				return
			}
			log.Printf("Failed to accept connection: %v", err)
			continue
		}

		c.wg.Add(1)
		go c.handleConnection(conn)
	}
}

// Stop closes the listener and waits for handlers to finish
func (c *Collector) Stop() {
	c.listener.Close()
	c.wg.Wait()
}

// GetProfiles returns all collected profiles and clears the buffer
func (c *Collector) GetProfiles() map[string][]Sample {
	c.mu.Lock()
	defer c.mu.Unlock()

	profiles := c.profiles
	c.profiles = make(map[string][]Sample)
	return profiles
}

// handleConnection processes a single HTTP request
func (c *Collector) handleConnection(conn net.Conn) {
	defer c.wg.Done()
	defer conn.Close()

	reader := bufio.NewReader(conn)
	req, err := http.ReadRequest(reader)
	if err != nil {
		log.Printf("Failed to read request: %v", err)
		return
	}

	// Validate request
	if req.Method != "POST" || req.URL.Path != "/v1/push" {
		http.Error(&httpResponseWriter{conn}, "Not found", http.StatusNotFound)
		return
	}

	// Extract labels
	labels := req.Header.Get("X-Labels")
	if labels == "" {
		labels = "unknown"
	}

	// Read body
	var bodyReader io.Reader = req.Body
	if req.Header.Get("Content-Encoding") == "gzip" {
		gr, err := gzip.NewReader(req.Body)
		if err != nil {
			http.Error(&httpResponseWriter{conn}, "Bad gzip", http.StatusBadRequest)
			return
		}
		defer gr.Close()
		bodyReader = gr
	}

	body, err := io.ReadAll(bodyReader)
	if err != nil {
		http.Error(&httpResponseWriter{conn}, "Read error", http.StatusBadRequest)
		return
	}

	// Parse collapsed format
	samples := parseCollapsed(string(body))
	if len(samples) == 0 {
		http.Error(&httpResponseWriter{conn}, "No samples", http.StatusBadRequest)
		return
	}

	// Store samples
	c.mu.Lock()
	c.profiles[labels] = append(c.profiles[labels], samples...)
	c.mu.Unlock()

	// Send response
	response := "HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nOK"
	conn.Write([]byte(response))
}

// parseCollapsed parses collapsed stack format
func parseCollapsed(data string) []Sample {
	var samples []Sample
	
	for _, line := range strings.Split(data, "\n") {
		line = strings.TrimSpace(line)
		if line == "" {
			continue
		}

		// Format: "stack;frame1;frame2 count"
		parts := strings.LastIndex(line, " ")
		if parts == -1 {
			continue
		}

		stack := line[:parts]
		countStr := line[parts+1:]

		var count int64
		fmt.Sscanf(countStr, "%d", &count)

		if count > 0 && stack != "" {
			samples = append(samples, Sample{
				Stack: strings.Split(stack, ";"),
				Count: count,
			})
		}
	}

	return samples
}

// httpResponseWriter implements a minimal http.ResponseWriter for Unix sockets
type httpResponseWriter struct {
	conn net.Conn
}

func (w *httpResponseWriter) Header() http.Header {
	return make(http.Header)
}

func (w *httpResponseWriter) Write(data []byte) (int, error) {
	return w.conn.Write(data)
}

func (w *httpResponseWriter) WriteHeader(statusCode int) {
	fmt.Fprintf(w.conn, "HTTP/1.1 %d %s\r\n", statusCode, http.StatusText(statusCode))
}