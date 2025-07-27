package main

import (
	"flag"
	"fmt"
	"log"
	"os"
	"os/signal"
	"syscall"
	"time"
)

var (
	listenPath   = flag.String("listen", "/var/run/phreakscope.sock", "Unix domain socket path")
	pyroscopeURL = flag.String("pyro.url", "http://pyroscope:4040", "Pyroscope server URL")
	interval     = flag.Duration("interval", 30*time.Second, "Flush interval")
)

func main() {
	flag.Parse()

	// Initialize collector
	collector, err := NewCollector(*listenPath)
	if err != nil {
		log.Fatalf("Failed to create collector: %v", err)
	}

	// Initialize pusher
	pusher := NewPusher(*pyroscopeURL)

	// Start collector
	go collector.Start()

	// Start flush ticker
	ticker := time.NewTicker(*interval)
	defer ticker.Stop()

	// Handle shutdown
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGTERM, syscall.SIGINT)

	log.Printf("Phreakscope agent started (listen=%s, pyroscope=%s, interval=%s)", 
		*listenPath, *pyroscopeURL, *interval)

	for {
		select {
		case <-ticker.C:
			// Flush profiles to Pyroscope
			profiles := collector.GetProfiles()
			for label, samples := range profiles {
				if err := pusher.Push(label, samples); err != nil {
					log.Printf("Failed to push profile for %s: %v", label, err)
				}
			}

		case <-sigChan:
			log.Println("Shutting down...")
			collector.Stop()
			return
		}
	}
}

func init() {
	// Ensure single instance per pod
	if err := os.MkdirAll("/var/run", 0755); err != nil && !os.IsExist(err) {
		log.Fatalf("Failed to create /var/run: %v", err)
	}
}