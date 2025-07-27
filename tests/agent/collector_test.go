package main

import (
	"bytes"
	"fmt"
	"net"
	"net/http"
	"os"
	"strings"
	"testing"
	"time"
)

func TestCollectorBasic(t *testing.T) {
	// Create temporary socket
	socketPath := "/tmp/test_phreakscope.sock"
	os.Remove(socketPath)

	collector, err := NewCollector(socketPath)
	if err != nil {
		t.Fatalf("Failed to create collector: %v", err)
	}
	defer collector.Stop()

	// Start collector in goroutine
	go collector.Start()

	// Wait for startup
	time.Sleep(100 * time.Millisecond)

	// Test connection
	conn, err := net.Dial("unix", socketPath)
	if err != nil {
		t.Fatalf("Failed to connect to socket: %v", err)
	}
	defer conn.Close()

	// Send HTTP request
	request := "POST /v1/push HTTP/1.1\r\n" +
		"Host: localhost\r\n" +
		"Content-Type: text/plain\r\n" +
		"Content-Length: 26\r\n" +
		"X-Labels: service=test\r\n" +
		"\r\n" +
		"main;func1;func2 5\r\n" +
		"main;func3 3\r\n"

	_, err = conn.Write([]byte(request))
	if err != nil {
		t.Fatalf("Failed to write request: %v", err)
	}

	// Read response
	response := make([]byte, 1024)
	n, err := conn.Read(response)
	if err != nil {
		t.Fatalf("Failed to read response: %v", err)
	}

	responseStr := string(response[:n])
	if !strings.Contains(responseStr, "200 OK") {
		t.Fatalf("Expected 200 OK, got: %s", responseStr)
	}

	// Wait for processing
	time.Sleep(100 * time.Millisecond)

	// Check collected profiles
	profiles := collector.GetProfiles()
	if len(profiles) != 1 {
		t.Fatalf("Expected 1 profile, got %d", len(profiles))
	}

	samples, ok := profiles["service=test"]
	if !ok {
		t.Fatalf("Profile for 'service=test' not found")
	}

	if len(samples) != 2 {
		t.Fatalf("Expected 2 samples, got %d", len(samples))
	}

	// Verify first sample
	sample1 := samples[0]
	expectedStack1 := []string{"main", "func1", "func2"}
	if !equalStringSlice(sample1.Stack, expectedStack1) {
		t.Fatalf("Expected stack %v, got %v", expectedStack1, sample1.Stack)
	}
	if sample1.Count != 5 {
		t.Fatalf("Expected count 5, got %d", sample1.Count)
	}

	// Verify second sample
	sample2 := samples[1]
	expectedStack2 := []string{"main", "func3"}
	if !equalStringSlice(sample2.Stack, expectedStack2) {
		t.Fatalf("Expected stack %v, got %v", expectedStack2, sample2.Stack)
	}
	if sample2.Count != 3 {
		t.Fatalf("Expected count 3, got %d", sample2.Count)
	}

	t.Logf("✅ Collector test passed")
}

func TestParseCollapsed(t *testing.T) {
	tests := []struct {
		input    string
		expected []Sample
	}{
		{
			input: "main;func1;func2 5\nmain;func3 3\n",
			expected: []Sample{
				{Stack: []string{"main", "func1", "func2"}, Count: 5},
				{Stack: []string{"main", "func3"}, Count: 3},
			},
		},
		{
			input: "single_func 10\n",
			expected: []Sample{
				{Stack: []string{"single_func"}, Count: 10},
			},
		},
		{
			input:    "",
			expected: []Sample{},
		},
		{
			input:    "invalid_line_no_count\n",
			expected: []Sample{},
		},
	}

	for i, test := range tests {
		t.Run(fmt.Sprintf("Test_%d", i), func(t *testing.T) {
			result := parseCollapsed(test.input)
			
			if len(result) != len(test.expected) {
				t.Fatalf("Expected %d samples, got %d", len(test.expected), len(result))
			}

			for j, sample := range result {
				expected := test.expected[j]
				if !equalStringSlice(sample.Stack, expected.Stack) {
					t.Fatalf("Sample %d: expected stack %v, got %v", j, expected.Stack, sample.Stack)
				}
				if sample.Count != expected.Count {
					t.Fatalf("Sample %d: expected count %d, got %d", j, expected.Count, sample.Count)
				}
			}
		})
	}

	t.Logf("✅ parseCollapsed tests passed")
}

func equalStringSlice(a, b []string) bool {
	if len(a) != len(b) {
		return false
	}
	for i := range a {
		if a[i] != b[i] {
			return false
		}
	}
	return true
}