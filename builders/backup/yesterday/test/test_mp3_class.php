<?php
/**
 * Test script for BFPMP3 class functionality
 */

echo "=== BFPMP3 Class Test Script ===\n\n";

// Test configuration
$demo_file = __DIR__ . '/vendors/demo/demo.mp3';
$test_output_dir = __DIR__ . '/test_outputs';

// Create test output directory
if (!is_dir($test_output_dir)) {
    mkdir($test_output_dir, 0755, true);
}

// Test file
$class_file = __DIR__ . '/vendors/php-mp3/class.mp3.php';

echo "Demo file: $demo_file\n";
echo "File exists: " . (file_exists($demo_file) ? 'YES' : 'NO') . "\n";
echo "File size: " . (file_exists($demo_file) ? filesize($demo_file) . ' bytes' : 'N/A') . "\n\n";

echo "=== Testing BFPMP3 Class ===\n";

try {
    // Include the class
    require_once $class_file;
    
    // Create instance
    $mp3 = new BFPMP3();
    
    echo "✓ Class instantiated successfully\n";
    
    // Test 1: Basic MP3 analysis
    echo "Test 1: Basic MP3 analysis...\n";
    $result = $mp3->get_mp3($demo_file, true, false);
    
    if ($result !== false) {
        echo "✓ MP3 analysis successful\n";
        
        // Display basic info
        if (isset($result['id3v1'])) {
            echo "  - ID3v1 data: " . (empty($result['id3v1']) ? 'Empty' : 'Present') . "\n";
        }
        if (isset($result['id3v2'])) {
            echo "  - ID3v2 data: " . (empty($result['id3v2']) ? 'Empty' : 'Present') . "\n";
        }
        if (isset($result['data'])) {
            echo "  - Audio data: " . (empty($result['data']) ? 'Empty' : 'Present') . "\n";
            if (!empty($result['data'])) {
                echo "    * Duration: " . (isset($result['data']['duration']) ? $result['data']['duration'] . 's' : 'Unknown') . "\n";
                echo "    * Bitrate: " . (isset($result['data']['bitrate']) ? $result['data']['bitrate'] . ' kbps' : 'Unknown') . "\n";
            }
        }
    } else {
        echo "✗ MP3 analysis failed\n";
    }
    
    // Test 2: Frame analysis
    echo "Test 2: Frame analysis...\n";
    $result_frames = $mp3->get_mp3($demo_file, true, true);
    
    if ($result_frames !== false && isset($result_frames['frames'])) {
        echo "✓ Frame analysis successful\n";
        echo "  - Total frames: " . count($result_frames['frames']) . "\n";
    } else {
        echo "✗ Frame analysis failed\n";
    }
    
    // Test 3: MP3 cutting (10% of file)
    echo "Test 3: MP3 cutting (10% of original)...\n";
    $cut_output = $test_output_dir . "/demo_cut_10percent.mp3";
    
    // Remove existing file if present
    if (file_exists($cut_output)) {
        unlink($cut_output);
    }
    
    $cut_result = $mp3->cut_mp3($demo_file, $cut_output, 0, 0.1, 'percent', false);
    
    if ($cut_result !== false && file_exists($cut_output)) {
        echo "✓ MP3 cutting successful\n";
        echo "  - Output file: $cut_output\n";
        echo "  - Output size: " . filesize($cut_output) . " bytes\n";
        echo "  - Size reduction: " . round((1 - filesize($cut_output)/filesize($demo_file)) * 100, 1) . "%\n";
    } else {
        echo "✗ MP3 cutting failed\n";
    }
    
    // Test 4: Time conversion utility
    echo "Test 4: Time conversion utility...\n";
    $time_result = $mp3->conv_time(125); // 2 minutes 5 seconds
    echo "✓ Time conversion: 125 seconds = '$time_result'\n";
    
    // Test 5: Genre access
    echo "Test 5: Genre array access...\n";
    if (isset($mp3->id3v1_genres) && is_array($mp3->id3v1_genres)) {
        echo "✓ Genre array accessible\n";
        echo "  - Total genres: " . count($mp3->id3v1_genres) . "\n";
        echo "  - First 5 genres: " . implode(', ', array_slice($mp3->id3v1_genres, 0, 5)) . "\n";
    } else {
        echo "✗ Genre array not accessible\n";
    }
    
    // Test 6: Additional metadata analysis
    echo "Test 6: Extended metadata analysis...\n";
    if ($result !== false && isset($result['data'])) {
        $data = $result['data'];
        echo "✓ Extended metadata:\n";
        echo "  - Sample rate: " . (isset($data['sample_rate']) ? $data['sample_rate'] . ' Hz' : 'Unknown') . "\n";
        echo "  - Channel mode: " . (isset($data['channel_mode']) ? $data['channel_mode'] : 'Unknown') . "\n";
        echo "  - MPEG version: " . (isset($data['mpeg_version']) ? $data['mpeg_version'] : 'Unknown') . "\n";
        echo "  - Layer: " . (isset($data['layer']) ? $data['layer'] : 'Unknown') . "\n";
    } else {
        echo "✗ Extended metadata not available\n";
    }
    
    unset($mp3);
    
} catch (Exception $e) {
    echo "✗ Exception occurred: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "✗ Fatal error occurred: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Check the test_outputs directory for generated files.\n";
