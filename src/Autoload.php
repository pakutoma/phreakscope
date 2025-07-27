<?php
declare(strict_types=1);

namespace Phreakscope;

/**
 * Autoloader for Phreakscope
 * 
 * This file registers the shutdown function that automatically
 * collects and sends profiling data to the agent.
 */

// Check if extension is loaded
if (!extension_loaded('phreakscope')) {
    return;
}

// Get configuration from environment
$socket = getenv('PHREAKSCOPE_SOCKET');
$labels = getenv('PHREAKSCOPE_LABELS');

if (!$socket) {
    return;
}

// Register shutdown function
register_shutdown_function(function() use ($socket, $labels) {
    // Finish request handling first
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    // Stop profiling
    phreakscope_stop();
    
    // Get raw profiling data
    $rawData = phreakscope_dump_raw();
    if (!$rawData) {
        return;
    }
    
    // Convert to collapsed format
    require_once __DIR__ . '/CollapsedEncoder.php';
    $encoder = new CollapsedEncoder();
    $collapsed = $encoder->encode($rawData);
    
    if (!$collapsed) {
        return;
    }
    
    // Prepare HTTP request
    $headers = [
        "POST /v1/push HTTP/1.1",
        "Host: localhost",
        "Content-Type: text/plain",
        "Content-Length: " . strlen($collapsed),
    ];
    
    // Add labels if provided
    if ($labels) {
        $headers[] = "X-Labels: " . $labels;
    }
    
    $headers[] = "";
    $headers[] = $collapsed;
    
    $request = implode("\r\n", $headers);
    
    // Send to Unix Domain Socket
    $sock = @socket_create(AF_UNIX, SOCK_STREAM, 0);
    if (!$sock) {
        return;
    }
    
    if (!@socket_connect($sock, $socket)) {
        @socket_close($sock);
        return;
    }
    
    @socket_write($sock, $request, strlen($request));
    @socket_close($sock);
});

// Start profiling
phreakscope_start();