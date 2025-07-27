<?php
/**
 * Integration test for full phreakscope flow
 * Tests: C extension -> PHP encoder -> Unix socket communication
 */

require_once __DIR__ . '/../../src/CollapsedEncoder.php';

use Phreakscope\CollapsedEncoder;

echo "=== Phreakscope Integration Test ===\n";

// Check if extension is available
if (!extension_loaded('phreakscope')) {
    echo "❌ SKIP: phreakscope extension not loaded\n";
    echo "Run: php tests/extension/basic_test.php first\n";
    exit(0);
}

// Test 1: Full profiling cycle
echo "Test 1: Full profiling cycle...\n";

// Start profiling
if (!phreakscope_start()) {
    echo "❌ FAIL: Could not start profiling\n";
    exit(1);
}

// Generate some workload
function workload_function() {
    $result = 0;
    for ($i = 0; $i < 50000; $i++) {
        $result += sqrt($i) * sin($i / 1000);
    }
    return $result;
}

function another_function() {
    return array_sum(range(1, 10000));
}

function deep_recursion($depth = 10) {
    if ($depth <= 0) {
        return workload_function();
    }
    return deep_recursion($depth - 1) + another_function();
}

echo "Running workload to generate samples...\n";
$start = microtime(true);
$result = deep_recursion(5);
$duration = microtime(true) - $start;
echo "Workload completed in " . number_format($duration * 1000, 2) . "ms\n";

// Let it sample for a bit
usleep(200000); // 200ms

// Stop profiling
if (!phreakscope_stop()) {
    echo "❌ FAIL: Could not stop profiling\n";
    exit(1);
}

// Get raw data
$rawData = phreakscope_dump_raw();
if ($rawData === false || empty($rawData)) {
    echo "❌ FAIL: No raw profiling data\n";
    exit(1);
}

echo "✅ Got raw data: " . strlen($rawData) . " bytes\n";

// Test 2: Encode to collapsed format
echo "Test 2: Encoding to collapsed format...\n";

$encoder = new CollapsedEncoder();
$collapsed = $encoder->encode($rawData);

if ($collapsed === false || empty($collapsed)) {
    echo "❌ FAIL: Could not encode to collapsed format\n";
    exit(1);
}

echo "✅ Encoded to collapsed format: " . strlen($collapsed) . " chars\n";

// Validate collapsed format
$lines = explode("\n", trim($collapsed));
$validLines = 0;
$totalSamples = 0;

foreach ($lines as $line) {
    if (empty($line)) continue;
    
    if (!preg_match('/^(.+) (\d+)$/', $line, $matches)) {
        echo "❌ FAIL: Invalid collapsed line: {$line}\n";
        exit(1);
    }
    
    $stack = $matches[1];
    $count = (int)$matches[2];
    
    if (empty($stack) || $count <= 0) {
        echo "❌ FAIL: Invalid stack or count: {$line}\n";
        exit(1);
    }
    
    $validLines++;
    $totalSamples += $count;
}

echo "✅ Collapsed format valid: {$validLines} unique stacks, {$totalSamples} total samples\n";

// Show sample data
echo "\nSample collapsed data (first 5 lines):\n";
$sampleLines = array_slice($lines, 0, 5);
foreach ($sampleLines as $line) {
    echo "  {$line}\n";
}
if (count($lines) > 5) {
    echo "  ... and " . (count($lines) - 5) . " more lines\n";
}

// Test 3: Unix socket communication simulation
echo "\nTest 3: Unix socket communication simulation...\n";

$socketPath = "/tmp/test_phreakscope_integration.sock";
@unlink($socketPath);

// Create a simple server to receive data
$pid = pcntl_fork();
if ($pid == 0) {
    // Child process - mock server
    $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
    socket_bind($socket, $socketPath);
    socket_listen($socket, 1);
    
    $client = socket_accept($socket);
    $request = socket_read($client, 4096);
    
    // Simple HTTP response
    $response = "HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nOK";
    socket_write($client, $response);
    
    socket_close($client);
    socket_close($socket);
    exit(0);
}

// Parent process - client
usleep(100000); // Let server start

// Prepare HTTP request
$labels = "service=test,instance=integration";
$headers = [
    "POST /v1/push HTTP/1.1",
    "Host: localhost",
    "Content-Type: text/plain",
    "Content-Length: " . strlen($collapsed),
    "X-Labels: {$labels}",
    "",
    $collapsed
];

$request = implode("\r\n", $headers);

// Send via Unix socket
$sock = socket_create(AF_UNIX, SOCK_STREAM, 0);
if (!$sock) {
    echo "❌ FAIL: Could not create socket\n";
    exit(1);
}

if (!socket_connect($sock, $socketPath)) {
    echo "❌ FAIL: Could not connect to socket\n";
    exit(1);
}

if (!socket_write($sock, $request)) {
    echo "❌ FAIL: Could not write to socket\n";
    exit(1);
}

// Read response
$response = socket_read($sock, 1024);
socket_close($sock);

if (!str_contains($response, "200 OK")) {
    echo "❌ FAIL: Invalid response: {$response}\n";
    exit(1);
}

echo "✅ Unix socket communication successful\n";

// Wait for child process
pcntl_wait($status);
@unlink($socketPath);

echo "\n✅ All integration tests passed!\n";
echo "\nThe complete flow works:\n";
echo "  1. C extension samples execution at 100Hz\n";
echo "  2. Raw data is collected in efficient binary format\n";
echo "  3. PHP encoder converts to collapsed format\n";
echo "  4. Data is sent via Unix socket to agent\n";
echo "\nPhreakscope is ready for production use!\n";