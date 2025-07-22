<?php
/**
 * Test Sndloop Configuration
 * 
 * This test verifies that the sndloop mode setting is correctly configured.
 */

require_once dirname(__DIR__) . '/BandfrontPlayer.php';

// Initialize the plugin
if (class_exists('Bandfront\Core\Bootstrap')) {
    $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
    $config = $bootstrap->getComponent('config');
    
    if ($config) {
        echo "=== Sndloop Configuration Test ===\n";
        
        // Test 1: Get sndloop mode setting
        $sndloopMode = $config->getState('_bfp_sndloop_mode', 0);
        echo "Current sndloop mode: " . ($sndloopMode ? 'enabled' : 'disabled') . "\n";
        
        // Test 2: Test setting sndloop mode
        $config->updateState('_bfp_sndloop_mode', 1);
        $sndloopMode = $config->getState('_bfp_sndloop_mode', 0);
        echo "After enabling: " . ($sndloopMode ? 'enabled' : 'disabled') . "\n";
        
        // Test 3: Check onload setting
        $onloadMode = $config->getState('_bfp_onload', 0);
        echo "Current onload mode: " . ($onloadMode ? 'enabled' : 'disabled') . "\n";
        
        // Test 4: Test getting form settings
        $formSettings = $config->getAdminFormSettings();
        echo "Sndloop in form settings: " . (isset($formSettings['_bfp_sndloop_mode']) ? 'yes' : 'no') . "\n";
        echo "Onload in form settings: " . (isset($formSettings['_bfp_onload']) ? 'yes' : 'no') . "\n";
        
        echo "\n=== Test Complete ===\n";
    } else {
        echo "Error: Could not get config component\n";
    }
} else {
    echo "Error: Bootstrap class not found\n";
}
