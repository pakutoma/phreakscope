<?php
/**
 * Test for CollapsedEncoder class
 */

require_once __DIR__ . '/../../src/CollapsedEncoder.php';

use Phreakscope\CollapsedEncoder;

echo "=== CollapsedEncoder Test ===\n";

$encoder = new CollapsedEncoder();

// Test 1: Empty data
echo "Test 1: Empty data...\n";
$result = $encoder->encode('');
if ($result !== false) {
    echo "❌ FAIL: Empty data should return false\n";
    exit(1);
}
echo "✅ Empty data handled correctly\n";

// Test 2: Mock raw data
echo "Test 2: Mock raw data...\n";

// Create mock raw data
function createMockRawData() {
    $data = '';
    
    // Location count (2 locations)
    $data .= pack('V', 2);
    
    // Location 1: ID=1, key="main.php:10"
    $key1 = "main.php:10";
    $data .= pack('V', 1);              // ID
    $data .= pack('V', strlen($key1));  // Key length
    $data .= $key1;                     // Key data
    
    // Location 2: ID=2, key="test_function"
    $key2 = "test_function";
    $data .= pack('V', 2);              // ID
    $data .= pack('V', strlen($key2));  // Key length
    $data .= $key2;                     // Key data
    
    // Sample count (2 samples)
    $data .= pack('P', 2);
    
    // Sample 1: [1, 2] (VarInt encoded)
    $sample1 = "\x01\x02"; // VarInt: 1, 2
    $data .= pack('V', strlen($sample1));
    $data .= $sample1;
    
    // Sample 2: [1, 2] again (same stack)
    $sample2 = "\x01\x02"; // VarInt: 1, 2
    $data .= pack('V', strlen($sample2));
    $data .= $sample2;
    
    return $data;
}

$mockData = createMockRawData();
echo "Mock data size: " . strlen($mockData) . " bytes\n";

$result = $encoder->encode($mockData);
if ($result === false) {
    echo "❌ FAIL: Mock data encoding failed\n";
    exit(1);
}

echo "Encoded result:\n";
echo $result;

// Validate result format
$lines = explode("\n", trim($result));
if (count($lines) != 1) {
    echo "❌ FAIL: Expected 1 line, got " . count($lines) . "\n";
    exit(1);
}

$line = $lines[0];
if (!preg_match('/^(.+) (\d+)$/', $line, $matches)) {
    echo "❌ FAIL: Invalid line format: {$line}\n";
    exit(1);
}

$stack = $matches[1];
$count = (int)$matches[2];

if ($count != 2) {
    echo "❌ FAIL: Expected count 2, got {$count}\n";
    exit(1);
}

// Stack should be "test_function;main.php:10" (reversed)
$expectedStack = "test_function;main.php:10";
if ($stack != $expectedStack) {
    echo "❌ FAIL: Expected stack '{$expectedStack}', got '{$stack}'\n";
    exit(1);
}

echo "✅ Mock data encoded correctly\n";

// Test 3: Complex VarInt encoding
echo "Test 3: Complex VarInt encoding...\n";

function createComplexMockData() {
    $data = '';
    
    // Location count (1 location)
    $data .= pack('V', 1);
    
    // Location: ID=300 (>127, needs multi-byte VarInt)
    $key = "complex.php:300";
    $data .= pack('V', 300);
    $data .= pack('V', strlen($key));
    $data .= $key;
    
    // Sample count (1 sample)
    $data .= pack('P', 1);
    
    // Sample: [300] encoded as VarInt
    // 300 = 0x12C = (44 with continuation) + (2 without continuation)
    $sample = "\xAC\x02"; // VarInt encoding of 300
    $data .= pack('V', strlen($sample));
    $data .= $sample;
    
    return $data;
}

$complexData = createComplexMockData();
$result = $encoder->encode($complexData);

if ($result === false) {
    echo "❌ FAIL: Complex data encoding failed\n";
    exit(1);
}

echo "Complex result:\n";
echo $result;

$lines = explode("\n", trim($result));
if (count($lines) != 1) {
    echo "❌ FAIL: Expected 1 line for complex data\n";
    exit(1);
}

if (!str_contains($lines[0], "complex.php:300")) {
    echo "❌ FAIL: Complex location not found in result\n";
    exit(1);
}

echo "✅ Complex VarInt encoding works\n";

echo "✅ All CollapsedEncoder tests passed!\n";