<?php
// Demo script to test phreakscope profiling

function cpu_intensive_task($iterations = 1000000) {
    $result = 0;
    for ($i = 0; $i < $iterations; $i++) {
        $result += sqrt($i) * sin($i);
    }
    return $result;
}

function memory_intensive_task() {
    $data = [];
    for ($i = 0; $i < 100000; $i++) {
        $data[] = str_repeat('x', 100);
    }
    return count($data);
}

function nested_function_calls($depth = 10) {
    if ($depth <= 0) {
        return cpu_intensive_task(10000);
    }
    return nested_function_calls($depth - 1);
}

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Phreakscope Demo</title></head><body>\n";
echo "<h1>Phreakscope Profiling Demo</h1>\n";

$start = microtime(true);

echo "<p>Running CPU intensive task...</p>\n";
$cpu_result = cpu_intensive_task();

echo "<p>Running memory intensive task...</p>\n";
$memory_result = memory_intensive_task();

echo "<p>Running nested function calls...</p>\n";
$nested_result = nested_function_calls();

$end = microtime(true);
$duration = ($end - $start) * 1000;

echo "<h2>Results:</h2>\n";
echo "<ul>\n";
echo "<li>CPU task result: " . number_format($cpu_result, 2) . "</li>\n";
echo "<li>Memory task result: " . number_format($memory_result) . " items</li>\n";
echo "<li>Nested calls result: " . number_format($nested_result, 2) . "</li>\n";
echo "<li>Total execution time: " . number_format($duration, 2) . " ms</li>\n";
echo "</ul>\n";

echo "<p>Profile data should be sent to Pyroscope at <a href=\"http://localhost:4040\">http://localhost:4040</a></p>\n";

echo "</body></html>\n";