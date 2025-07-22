<?php
/**
 * Test Demo Message Access
 * 
 * This test verifies that the demo message can be correctly accessed
 * from the new _bfp_demos array structure.
 */

require_once dirname(__DIR__) . '/BandfrontPlayer.php';

// Initialize the plugin
if (class_exists('Bandfront\Core\Bootstrap')) {
    $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
    $config = $bootstrap->getComponent('config');
    
    if ($config) {
        echo "=== Demo Message Test ===\n";
        
        // Test 1: Get demo config
        $demosConfig = $config->getState('_bfp_demos', []);
        echo "Demo config structure:\n";
        print_r($demosConfig);
        
        // Test 2: Get message specifically
        $message = $demosConfig['message'] ?? 'No message found';
        echo "\nDemo message: '$message'\n";
        
        // Test 3: Test with a sample message
        $config->updateState('_bfp_demos', [
            'enabled' => true,
            'duration_percent' => 50,
            'demo_fade' => 0,
            'demo_filetype' => 'mp3',
            'demo_start_time' => 0,
            'message' => 'This is a test demo message!',
        ]);
        
        // Re-fetch to verify
        $demosConfig = $config->getState('_bfp_demos', []);
        $message = $demosConfig['message'] ?? 'No message found';
        echo "\nAfter setting test message: '$message'\n";
        
        echo "\n=== Test Complete ===\n";
    } else {
        echo "Error: Could not get config component\n";
    }
} else {
    echo "Error: Bootstrap class not found\n";
}
