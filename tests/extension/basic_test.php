<?php
/**
 * Basic test for phreakscope C extension
 */

echo "=== Phreakscope Extension Basic Test ===\n";

// Check if extension is loaded
if (!extension_loaded('phreakscope')) {
    echo "❌ FAIL: phreakscope extension not loaded\n";
    echo "Build and install the extension first:\n";
    echo "  cd ext/phreakscope\n";
    echo "  phpize && ./configure && make && sudo make install\n";
    echo "  echo 'extension=phreakscope.so' > /etc/php/conf.d/phreakscope.ini\n";
    exit(1);
}

echo "✅ Extension loaded\n";

// Check if functions exist
$functions = ['phreakscope_start', 'phreakscope_stop', 'phreakscope_dump_raw'];
foreach ($functions as $func) {
    if (!function_exists($func)) {
        echo "❌ FAIL: Function {$func} not found\n";
        exit(1);
    }
}
echo "✅ All functions available\n";

// Test basic start/stop cycle
echo "Testing start/stop cycle...\n";

$result = phreakscope_start();
if (!$result) {
    echo "❌ FAIL: phreakscope_start() returned false\n";
    exit(1);
}
echo "✅ Started profiling\n";

// Run some code to generate samples
function test_function() {
    $sum = 0;
    for ($i = 0; $i < 10000; $i++) {
        $sum += sqrt($i);
    }
    return $sum;
}

function nested_test($depth = 5) {
    if ($depth <= 0) {
        return test_function();
    }
    return nested_test($depth - 1);
}

echo "Running test workload...\n";
$result = nested_test();
echo "Test result: " . number_format($result, 2) . "\n";

// Sleep to collect samples
usleep(100000); // 100ms

$result = phreakscope_stop();
if (!$result) {
    echo "❌ FAIL: phreakscope_stop() returned false\n";
    exit(1);
}
echo "✅ Stopped profiling\n";

// Test dump_raw
echo "Testing raw data dump...\n";
$rawData = phreakscope_dump_raw();

if ($rawData === false) {
    echo "❌ FAIL: phreakscope_dump_raw() returned false\n";
    exit(1);
}

if (empty($rawData)) {
    echo "❌ FAIL: Raw data is empty\n";
    exit(1);
}

echo "✅ Got raw data: " . strlen($rawData) . " bytes\n";

// Basic validation of raw data format
if (strlen($rawData) < 12) { // At least location count + sample count
    echo "❌ FAIL: Raw data too short\n";
    exit(1);
}

$locationCount = unpack('V', substr($rawData, 0, 4))[1];
echo "✅ Location count: {$locationCount}\n";

if ($locationCount == 0) {
    echo "❌ FAIL: No locations recorded\n";
    exit(1);
}

echo "✅ All basic tests passed!\n";
echo "\nExtension is working correctly.\n";