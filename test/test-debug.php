<?php
/**
 * Debug Test File
 * Run this to test if debug logging is working
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Load our Debug class
require_once __DIR__ . '../src/Utils/Debug.php';

use bfp\Utils\Debug;

// Test logging
echo "Testing Bandfront Player Debug Logging...\n";
echo "=========================================\n\n";

// Log to error_log
Debug::log('TEST: This is a test message from test-debug.php', ['test' => true, 'timestamp' => time()]);
echo "✓ Logged test message using Debug::log()\n";

// Also try direct error_log
error_log('[BFP TEST] Direct error_log test at ' . date('Y-m-d H:i:s'));
echo "✓ Logged test message using error_log()\n";

// Show where logs might be
echo "\nPossible log locations:\n";
echo "- PHP Error Log: " . ini_get('error_log') . "\n";

if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    if (is_string(WP_DEBUG_LOG)) {
        echo "- WordPress Debug Log: " . WP_DEBUG_LOG . "\n";
    } else {
        echo "- WordPress Debug Log: " . WP_CONTENT_DIR . "/debug.log\n";
    }
}

// Check if our custom log exists
$uploadDir = wp_upload_dir();
$customLog = $uploadDir['basedir'] . '/bfp-logs/debug.log';
if (file_exists($customLog)) {
    echo "- BFP Custom Log: " . $customLog . "\n";
    echo "\nLast 5 lines from BFP debug.log:\n";
    $lines = file($customLog);
    $lastLines = array_slice($lines, -5);
    foreach ($lastLines as $line) {
        echo "  " . trim($line) . "\n";
    }
}

echo "\n✓ Debug test complete!\n";
