# Phreakscope

A low-overhead sampling profiler for PHP applications that integrates with Grafana Pyroscope for continuous profiling.

## Features

- **Low overhead**: 100Hz sampling with pthread-based architecture
- **Line-level resolution**: Unlike xhprof, provides accurate line-level profiling data
- **Container-friendly**: Works in PHP-FPM containers without root privileges
- **Kubernetes ready**: Configurable via environment variables, works with sidecar agents
- **Pyroscope integration**: Seamless integration with Grafana Pyroscope

## Architecture

```
PHP-FPM (Worker) ──────▶ Unix Domain Socket ──────▶ Go Agent ───▶ Pyroscope
└ C Extension                                         └ 30s batch    └ Visualization
  (sampler)                                           pprof Push
```

## Quick Start

### 1. Build C Extension

```bash
cd ext/phreakscope
phpize
./configure
make -j$(nproc)
sudo make install
echo "extension=phreakscope.so" > /etc/php/conf.d/phreakscope.ini
```

### 2. Build Go Agent

```bash
cd cmd/agent
go build -o /usr/local/bin/phreakscope-agent .
```

### 3. Start Agent

```bash
export PHREAKSCOPE_SOCKET=/var/run/phreakscope.sock
export PHREAKSCOPE_LABELS="service=myapp,instance=$(hostname)"

phreakscope-agent \
  --listen=$PHREAKSCOPE_SOCKET \
  --pyro.url=http://pyroscope:4040 \
  --interval=30s &
```

### 4. Configure PHP Application

```php
<?php
// Include autoloader
require_once '/path/to/phreakscope/src/Autoload.php';

// Your application code here
```

Or configure auto_prepend_file in php.ini:

```ini
auto_prepend_file=/path/to/phreakscope/src/Autoload.php
```

## Docker Setup

```bash
cd docker
docker-compose up -d
```

Visit http://localhost:8080 to generate profile data, then check http://localhost:4040 for Pyroscope UI.

## Configuration

### C Extension INI Settings

```ini
phreakscope.interval_usec = 10000    ; 100Hz sampling (10ms interval)
phreakscope.buffer_bytes = 1048576   ; 1MB buffer size
phreakscope.max_depth = 64           ; Maximum stack depth
```

### Environment Variables

```bash
PHREAKSCOPE_SOCKET=/var/run/phreakscope.sock
PHREAKSCOPE_LABELS="service=myapp,instance={HOSTNAME}"
```

## API Reference

### C Extension Functions

- `phreakscope_start()`: Start profiling
- `phreakscope_stop()`: Stop profiling
- `phreakscope_dump_raw()`: Get raw profile data

### Go Agent Options

- `--listen`: Unix domain socket path
- `--pyro.url`: Pyroscope server URL
- `--interval`: Flush interval (default: 30s)

## Performance

- **Overhead**: <1% CPU overhead at 100Hz sampling
- **Memory**: ~1MB buffer per PHP process
- **Throughput**: Handles thousands of requests per second

## License

Apache 2.0 License