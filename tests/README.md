# Phreakscope Test Suite

This directory contains comprehensive tests for all Phreakscope components.

## Test Structure

```
tests/
├── extension/          # C extension tests
│   └── basic_test.php  # Basic functionality test
├── php/                # PHP library tests
│   └── collapsed_encoder_test.php  # CollapsedEncoder unit test
├── agent/              # Go agent tests
│   └── collector_test.go  # Collector and parser tests
├── integration/        # End-to-end tests
│   └── full_flow_test.php  # Complete workflow test
└── README.md           # This file
```

## Quick Start

Run all tests:
```bash
./run_tests.sh
```

## Individual Tests

### 1. C Extension Test
Tests the core sampling functionality.

**Prerequisites:**
- C extension must be built and installed
- PHP with phreakscope extension loaded

**Run:**
```bash
php tests/extension/basic_test.php
```

**What it tests:**
- Extension loading
- Function availability
- Start/stop profiling cycle
- Raw data collection
- Basic data validation

### 2. PHP Library Test
Tests the CollapsedEncoder that converts raw data to stack traces.

**Prerequisites:**
- PHP 8.1+ with standard extensions

**Run:**
```bash
php tests/php/collapsed_encoder_test.php
```

**What it tests:**
- Empty data handling
- Mock raw data encoding
- VarInt decoding
- Collapsed format generation
- Stack trace construction

### 3. Go Agent Test
Tests the Unix socket server and profile collection.

**Prerequisites:**
- Go 1.23+
- Unix socket support

**Run:**
```bash
cd tests/agent && go test -v
```

**What it tests:**
- Unix socket HTTP server
- Profile data collection
- Collapsed format parsing
- Sample aggregation
- Label handling

### 4. Integration Test
Tests the complete end-to-end workflow.

**Prerequisites:**
- C extension installed
- PHP with pcntl extension
- Unix socket support

**Run:**
```bash
php tests/integration/full_flow_test.php
```

**What it tests:**
- Complete profiling cycle
- Raw data → collapsed format conversion
- Unix socket communication
- HTTP protocol handling
- Full workflow validation

## Building Prerequisites

### C Extension
```bash
cd ext/phreakscope
phpize
./configure
make -j$(nproc)
sudo make install
echo "extension=phreakscope.so" > /etc/php/conf.d/phreakscope.ini
```

### Go Dependencies
```bash
cd cmd/agent
go mod download
```

## Test Data

### Mock Raw Data Format
The tests use mock binary data that simulates the C extension output:

1. **Location Dictionary**:
   - `uint32`: location count
   - For each location:
     - `uint32`: location ID
     - `uint32`: key length
     - `string`: key data

2. **Sample Data**:
   - `uint64`: sample count
   - For each sample:
     - `uint32`: sample size
     - `bytes`: VarInt-encoded location IDs

### Expected Collapsed Format
```
function1;function2;function3 count
main;workload_function 5
main;another_function 3
```

## Troubleshooting

### C Extension Not Loading
```bash
# Check if extension is installed
php -m | grep phreakscope

# Check for errors
php -d extension=phreakscope.so -v
```

### Go Tests Failing
```bash
# Check Go version
go version

# Run with verbose output
cd tests/agent && go test -v -run TestCollectorBasic
```

### Integration Test Issues
```bash
# Check pcntl extension
php -m | grep pcntl

# Check socket permissions
ls -la /tmp/test_phreakscope*.sock
```

## Performance Testing

For performance testing, see the demo application:
```bash
cd docker
docker-compose up -d
curl http://localhost:8080  # Generate load
```

Monitor results at http://localhost:4040 (Pyroscope UI).