# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Phreakscope is a PHP library that bridges xhprof profiling data with Pyroscope, a continuous profiling platform. It converts xhprof's performance data into Google's pprof format for integration with modern observability tools.

## Commands

### Development Setup
```bash
# Install PHP dependencies
composer install

# Regenerate autoloader after adding new classes
composer dump-autoload
```

### Building the sample_prof Extension
The `sample_prof/` directory contains a C extension for line-level PHP profiling:
```bash
cd sample_prof/
phpize
./configure
make
sudo make install

# Clean build artifacts
make clean
phpize --clean
```

### Regenerating Protocol Buffers
If you need to update the protobuf definitions:
```bash
cd src/protobuf/
./generate.sh
```

## Architecture

### Core Components

1. **Profile Collection Flow**
   - `public/index.php`: Registers shutdown handler that enables xhprof and saves profiles to `/tmp/xhprof`
   - `public/pprof.php`: HTTP endpoint that calls ProfileEndpoint to retrieve profiles
   - `src/ProfileEndpoint.php`: Orchestrates profile collection, waiting, and conversion

2. **Data Conversion Pipeline**
   - `src/pprof/XhprofConverter.php`: Main converter that transforms xhprof data to pprof format
   - `src/pprof/`: Domain models representing pprof data structures (Profile, Sample, Location, etc.)
   - `src/protobuf/profiles/`: Protocol buffer generated classes for serialization

3. **Key Design Decisions**
   - Profiles are temporarily stored in `/tmp/xhprof` using xhprof's built-in file storage
   - The endpoint removes old profiles, waits for new ones, then converts only the new profiles
   - Output is gzip-compressed pprof binary format compatible with Pyroscope

### Integration Points

- **Input**: xhprof extension must be installed and enabled
- **Output**: Binary pprof format consumed by Pyroscope via HTTP endpoint
- **Storage**: Temporary file storage in `/tmp/xhprof` (cleaned up after conversion)

## Key Implementation Details

- The ProfileEndpoint uses a wait mechanism to collect profiles over a specified duration
- Profile conversion maps xhprof's function call data to pprof's Location/Function/Sample model
- Memory and CPU samples are both included in the converted profile
- Function names are parsed to extract file paths and create proper Location mappings

## Dependencies

- PHP 8+ with extensions: zlib, xhprof (>=2)
- google/protobuf for protocol buffer support
- Node.js (latest) for development tools (specified in mise.toml)

## Important Reference: nikic/sample_prof

The `sample_prof/` directory contains nikic/sample_prof, a line-level sampling profiler that serves as an important reference for future phreakscope implementations. Key insights:

### Architecture Differences from xhprof
- **Line-level resolution**: Unlike xhprof which hooks into function calls, sample_prof uses a separate thread that periodically samples the current execution point
- **Minimal performance impact**: Uses pthread-based sampling instead of function hooks, causing symmetric slowdown
- **Signal-free design**: Uses a dedicated thread with usleep() instead of SIGPROF signals

### Implementation Details
- **Thread-based sampling**: Creates a pthread that walks the executor stack (`current_execute_data`) to find the currently executing line
- **Pre-allocated memory**: All profiling entries are pre-allocated to avoid allocations during sampling
- **Data structure**: Simple array of `{filename, lineno}` entries that are aggregated into hit counts
- **Output formats**: Supports HTML visualization and Callgrind format for KCacheGrind

### Key Techniques for phreakscope
1. **Stack walking**: The technique of traversing `current_execute_data` to find user code execution points
2. **Line number extraction**: Using `opline->lineno` to get accurate line-level information
3. **Safe data collection**: Pre-allocation and atomic operations to ensure thread safety
4. **Multiple output formats**: Converting raw sampling data to different visualization formats

This implementation demonstrates how to build a profiler that provides more granular data than xhprof while maintaining low overhead, making it an excellent reference for extending phreakscope's capabilities.