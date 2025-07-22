<?php
/**
 * Test SNDLOOP Configuration
 * 
 * This test verifies that the SNDLOOP network settings are correctly configured.
 */

require_once dirname(__DIR__) . '/BandfrontPlayer.php';

// Initialize the plugin
if (class_exists('Bandfront\Core\Bootstrap')) {
    $bootstrap = \Bandfront\Core\Bootstrap::getInstance();
    $config = $bootstrap->getComponent('config');
    
    if ($config) {
        echo "=== SNDLOOP Configuration Test ===\n";
        
        // Test 1: Get SNDLOOP mode setting
        $sndloopMode = $config->getState('_bfp_sndloop_mode', 0);
        echo "SNDLOOP mode: " . ($sndloopMode ? 'enabled' : 'disabled') . "\n";
        
        // Test 2: Get SNDLOOP network settings
        $discovery = $config->getState('_bfp_sndloop_discovery', 0);
        $sendProducts = $config->getState('_bfp_sndloop_send_products', 0);
        $sendMerch = $config->getState('_bfp_sndloop_send_merch', 0);
        
        echo "SNDLOOP discovery: " . ($discovery ? 'enabled' : 'disabled') . "\n";
        echo "Send products: " . ($sendProducts ? 'enabled' : 'disabled') . "\n";
        echo "Send merch: " . ($sendMerch ? 'enabled' : 'disabled') . "\n";
        
        // Test 3: Test setting SNDLOOP settings
        $config->updateState('_bfp_sndloop_mode', 1);
        $config->updateState('_bfp_sndloop_discovery', 1);
        $config->updateState('_bfp_sndloop_send_products', 1);
        
        $sndloopMode = $config->getState('_bfp_sndloop_mode', 0);
        $discovery = $config->getState('_bfp_sndloop_discovery', 0);
        $sendProducts = $config->getState('_bfp_sndloop_send_products', 0);
        
        echo "\nAfter enabling:\n";
        echo "SNDLOOP mode: " . ($sndloopMode ? 'enabled' : 'disabled') . "\n";
        echo "SNDLOOP discovery: " . ($discovery ? 'enabled' : 'disabled') . "\n";
        echo "Send products: " . ($sendProducts ? 'enabled' : 'disabled') . "\n";
        
        // Test 4: Check form settings
        $formSettings = $config->getAdminFormSettings();
        echo "\nForm settings available:\n";
        echo "sndloop_mode: " . (isset($formSettings['_bfp_sndloop_mode']) ? 'yes' : 'no') . "\n";
        echo "sndloop_discovery: " . (isset($formSettings['_bfp_sndloop_discovery']) ? 'yes' : 'no') . "\n";
        echo "sndloop_send_products: " . (isset($formSettings['_bfp_sndloop_send_products']) ? 'yes' : 'no') . "\n";
        echo "sndloop_send_merch: " . (isset($formSettings['_bfp_sndloop_send_merch']) ? 'yes' : 'no') . "\n";
        
        echo "\n=== Test Complete ===\n";
    } else {
        echo "Error: Could not get config component\n";
    }
} else {
    echo "Error: Bootstrap class not found\n";
}
