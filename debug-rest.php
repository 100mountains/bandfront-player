<?php
/**
 * Debug REST API endpoints
 * 
 * Access this file directly to see registered REST routes
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Get all REST routes
$server = rest_get_server();
$routes = $server->get_routes();

// Filter for our routes
$our_routes = array_filter($routes, function($route) {
    return strpos($route, 'bandfront-player') !== false;
});

header('Content-Type: text/plain');

echo "=== Bandfront Player REST API Debug ===\n\n";

if (empty($our_routes)) {
    echo "No Bandfront Player routes found!\n\n";
} else {
    echo "Found " . count($our_routes) . " Bandfront Player routes:\n\n";
    
    foreach ($our_routes as $route => $handlers) {
        echo "Route: $route\n";
        foreach ($handlers as $handler) {
            echo "  Methods: " . implode(', ', $handler['methods']) . "\n";
        }
        echo "\n";
    }
}

echo "\n=== All REST Routes ===\n\n";
foreach ($routes as $route => $handlers) {
    echo "$route\n";
}
