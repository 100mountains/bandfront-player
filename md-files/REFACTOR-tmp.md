

current system in our code from old wordpress uses folders to put purchased files in for no reason. it still needs the bfp folder for demos but we can use a different way. if we construct a function that hooks onto product generation or updating, if its a downloadable audio product - we just leave it where it was created, usually in woocommerce_uploads/ (or if that can be retrieved by a native function that would be better) - then we pass it off to a function which zips up all of it into various formats and then any user can download any format straight away - we just get ffmpeg to do it every time the product is generated and then no more processing on the fly to do that. then if user owns album it just retrieves purchased url presumably in woocommerce_uploads/ which means this program can just always use default urls to stream through API and urls that are pre-generated from the titles to download zips etc

add js console log statements to php. outputs will help me track the execution flow and see important variable values in the browser console.

This approach would be much more efficient and leverage WooCommerce's existing infrastructure. Here's how we might implement this:


## New Product Hook System

ProductProcessor.php

we want this to do the donkey work of this js
this php file works from the child theme to process the files and select what file type you want

we can take out anything to do with file operations and use that to create the audio zips from for our product procccessor

<?php
/**
 * Audio Processing Security and Validation
 */
function validate_audio_format($format) {
    $allowed_formats = ['wav', 'mp3', 'flac', 'aiff', 'alac', 'ogg'];
    return in_array(strtolower($format), $allowed_formats) ? strtolower($format) : false;
}

function handle_bulk_audio_processing() {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Security checks
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    if (!current_user_can('read')) {
        wp_send_json_error('User lacks read capability');
        return;
    }

    if (!check_ajax_referer('audio_conversion_nonce', 'security', false)) {
        error_log('Nonce verification failed in handle_bulk_audio_processing');
        wp_send_json_error('Invalid security token - Please refresh the page');
        return;
    }

    // Get parameters
    $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : '';
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if (!$format || !$product_id) {
        wp_send_json_error('Missing required parameters');
        return;
    }

    // Validate format
    $valid_format = validate_audio_format($format);
    if (!$valid_format) {
        wp_send_json_error('Invalid audio format');
        return;
    }

    // Check if FFmpeg is available
    $ffmpeg_test = shell_exec('which ffmpeg 2>&1');
    if (empty($ffmpeg_test)) {
        error_log('FFmpeg not found on system');
        wp_send_json_error('FFmpeg is not installed on the server');
        return;
    }

    try {
        // Get user's downloadable files for this product
        $downloads = WC()->customer->get_downloadable_products();
        error_log('=== DEBUG: All WC downloads for customer ===');
        error_log(print_r($downloads, true));
        $product_files = array();
        
        foreach ($downloads as $download) {
            if ($download['product_id'] == $product_id) {
                $product_files[] = $download;
            }
        }
        error_log('=== DEBUG: Filtered product_files for product_id ' . $product_id . ' ===');
        error_log(print_r($product_files, true));

        if (empty($product_files)) {
            wp_send_json_error('No files found for this product');
            return;
        }

        // Sort product_files by filename to ensure correct order and inclusion
        usort($product_files, function($a, $b) {
            return strcmp($a['file']['file'], $b['file']['file']);
        });

        // Create unique temp directory
        $temp_id = uniqid('audio_convert_');
        $temp_dir = sys_get_temp_dir() . '/' . $temp_id;
        
        // Setup WooCommerce upload directory
        $upload_dir = wp_upload_dir();
        $woo_upload_dir = $upload_dir['basedir'] . '/woocommerce_uploads';
        
        error_log('Upload directory: ' . $woo_upload_dir);
        error_log('Temp directory: ' . $temp_dir);
        
        if (!file_exists($woo_upload_dir)) {
            wp_mkdir_p($woo_upload_dir);
        }
        
        if (!wp_mkdir_p($temp_dir) || !wp_mkdir_p($woo_upload_dir)) {
            wp_send_json_error('Failed to create directories');
            return;
        }

        // Get product name and sanitize for matching
        $product_name = isset($product_files[0]['product_name']) ? $product_files[0]['product_name'] : '';
        $sanitized_product_name = strtolower(preg_replace('/[^a-z0-9]+/', '-', $product_name));

        // Only process audio files
        $audio_extensions = ['wav', 'mp3', 'flac', 'aiff', 'alac', 'ogg'];
        $converted_files = array();
        $debug_info = array();

        // Build audio_files array directly from WooCommerce downloads for this product
        $audio_files = array();
        foreach ($product_files as $download) {
            $file_path = $download['file']['file'];
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            if (in_array($ext, $audio_extensions)) {
                // Try to resolve the absolute path
                $possible_paths = array();

                // If it's already an absolute path
                if (file_exists($file_path)) {
                    $possible_paths[] = $file_path;
                }

                // Try relative to uploads dir
                $upload_dir = wp_upload_dir();
                $woo_upload_dir = $upload_dir['basedir'] . '/woocommerce_uploads';
                if (strpos($file_path, '/wp-content/uploads/') === 0) {
                    $possible_paths[] = $upload_dir['basedir'] . '/' . ltrim(substr($file_path, strlen('/wp-content/uploads/')), '/');
                }
                // Try in woocommerce_uploads
                $possible_paths[] = $woo_upload_dir . '/' . basename($file_path);

                // Try with year/month structure (if present in file path)
                if (preg_match('#(\d{4})/(\d{2})#', $file_path, $matches)) {
                    $possible_paths[] = $woo_upload_dir . '/' . $matches[1] . '/' . $matches[2] . '/' . basename($file_path);
                }

                // Try with just the basename in all subdirs
                $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($woo_upload_dir));
                foreach ($rii as $file) {
                    if ($file->isFile() && $file->getFilename() === basename($file_path)) {
                        $possible_paths[] = $file->getPathname();
                    }
                }

                // Find the first existing file
                $actual_file_path = null;
                foreach ($possible_paths as $path) {
                    if (file_exists($path)) {
                        $actual_file_path = $path;
                        break;
                    }
                }
                if ($actual_file_path) {
                    $audio_files[] = $actual_file_path;
                } else {
                    $debug_info[] = 'File not found: ' . basename($file_path);
                }
            }
        }

        // Find and include the cover PNG with the nearest date to the first audio file
        if (!empty($audio_files)) {
            $first_audio = $audio_files[0];
            $first_audio_time = file_exists($first_audio) ? filemtime($first_audio) : false;
            $cover_dir = dirname($first_audio);
            $cover_files = glob($cover_dir . '/cover-*.png');
            $nearest_cover = null;
            $nearest_diff = PHP_INT_MAX;
            foreach ($cover_files as $cover_file) {
                $diff = abs(filemtime($cover_file) - $first_audio_time);
                if ($diff < $nearest_diff) {
                    $nearest_diff = $diff;
                    $nearest_cover = $cover_file;
                }
            }
            if ($nearest_cover) {
                $dest = $temp_dir . '/cover.png';
                if (copy($nearest_cover, $dest)) {
                    $converted_files[] = $dest;
                    error_log('Copied nearest cover image to: ' . $dest);
                } else {
                    error_log('Failed to copy nearest cover image: ' . $nearest_cover);
                }
            }
        }

        // Process each found audio file
        foreach ($audio_files as $actual_file_path) {
            $filename = pathinfo($actual_file_path, PATHINFO_FILENAME);
            $extension = pathinfo($actual_file_path, PATHINFO_EXTENSION);

            // --- Clean up filename for output ---
            $clean_title = $filename;
            $clean_title = preg_replace('/-[a-z0-9]{6,}$/i', '', $clean_title);
            $clean_title = preg_replace('/-DIRECTORS-COMMENTARY-\d{4}$/i', '', $clean_title);
            $clean_title = preg_replace('/-THE-ROB-REMASTER-\d{4}$/i', '', $clean_title);
            $clean_title = preg_replace('/-GAPLESS-MIX$/i', '', $clean_title);
            $clean_title = preg_replace('/--+/', '-', $clean_title);
            $clean_title = trim($clean_title, '-_ ');

            $track_title = $clean_title;
            $safe_track_title = sanitize_file_name($track_title);
            $track_number = '';
            if (preg_match('/(\d{2,3})/', $filename, $matches)) {
                $track_number = $matches[1];
            } elseif (preg_match('/(\d)/', $filename, $matches)) {
                $track_number = '0' . $matches[1];
            }
            if ($track_number !== '') {
                $output_base = $track_number . ' - ' . $safe_track_title;
            } else {
                $output_base = $safe_track_title;
            }
            $output_file = null;
            $command = null;

            switch ($valid_format) {
                case 'mp3':
                    $output_file = $temp_dir . '/' . $output_base . '.mp3';
                    $command = sprintf('ffmpeg -i %s -codec:a libmp3lame -qscale:a 2 %s -y 2>&1',
                        escapeshellarg($actual_file_path), escapeshellarg($output_file));
                    break;
                case 'flac':
                    $output_file = $temp_dir . '/' . $output_base . '.flac';
                    $command = sprintf('ffmpeg -i %s -codec:a flac %s -y 2>&1',
                        escapeshellarg($actual_file_path), escapeshellarg($output_file));
                    break;
                case 'aiff':
                    $output_file = $temp_dir . '/' . $output_base . '.aiff';
                    $command = sprintf('ffmpeg -i %s -f aiff %s -y 2>&1',
                        escapeshellarg($actual_file_path), escapeshellarg($output_file));
                    break;
                case 'alac':
                    $output_file = $temp_dir . '/' . $output_base . '.m4a';
                    $command = sprintf('ffmpeg -i %s -codec:a alac %s -y 2>&1',
                        escapeshellarg($actual_file_path), escapeshellarg($output_file));
                    break;
                case 'ogg':
                    $output_file = $temp_dir . '/' . $output_base . '.ogg';
                    $command = sprintf('ffmpeg -i %s -codec:a libvorbis -qscale:a 5 %s -y 2>&1',
                        escapeshellarg($actual_file_path), escapeshellarg($output_file));
                    break;
                case 'wav':
                    $output_file = $temp_dir . '/' . $output_base . '.wav';
                    if (copy($actual_file_path, $output_file)) {
                        $converted_files[] = $output_file;
                        error_log('Copied WAV file to: ' . $output_file);
                    } else {
                        error_log('Failed to copy WAV file');
                    }
                    continue 2;
            }

            // Execute conversion
            if ($command) {
                error_log('Executing command: ' . $command);
                $output = array();
                $return_var = 0;
                exec($command, $output, $return_var);
                
                if ($return_var !== 0) {
                    error_log('FFmpeg conversion failed with code ' . $return_var . ': ' . implode("\n", $output));
                    $debug_info[] = 'Conversion failed for ' . basename($actual_file_path);
                } else {
                    if (file_exists($output_file) && filesize($output_file) > 0) {
                        $converted_files[] = $output_file;
                        error_log('Successfully converted: ' . $output_file);
                    } else {
                        error_log('Output file not created or empty: ' . $output_file);
                        $debug_info[] = 'Output file not created for ' . basename($actual_file_path);
                    }
                }
            }
        }

        // Check if we have any converted files
        if (empty($converted_files)) {
            wp_send_json_error('No files were converted. Debug info: ' . implode(', ', $debug_info));
            return;
        }

        // Create zip file
        // Use product name for zip filename
        $product_name = sanitize_file_name($product_files[0]['product_name']);
        $zip_filename = $product_name . "_" . $valid_format . "_" . date("Y-m-d") . ".zip";
        $zip_path = $woo_upload_dir . "/" . $zip_filename;
        
        error_log('Creating zip at: ' . $zip_path);
        
        $zip = new ZipArchive();
        $zip_result = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($zip_result === TRUE) {
            $files_added = 0;
            foreach ($converted_files as $file) {
                if (is_file($file)) {
                    $added = $zip->addFile($file, basename($file));
                    if ($added) {
                        $files_added++;
                        error_log('Added to zip: ' . basename($file));
                    } else {
                        error_log('Failed to add to zip: ' . basename($file));
                    }
                }
            }
            $zip->close();
            
            error_log('Zip created with ' . $files_added . ' files');
            
            // Verify zip was created
            if (!file_exists($zip_path) || filesize($zip_path) == 0) {
                wp_send_json_error('Zip file creation failed - file does not exist or is empty');
                return;
            }
        } else {
            $error_msg = 'Failed to create zip file. Error code: ' . $zip_result;
            error_log($error_msg);
            wp_send_json_error($error_msg);
            return;
        }

        // === NEW: Move zip to public directory ===
        $public_zip_dir = $upload_dir['basedir'] . '/audio_zips';
        if (!file_exists($public_zip_dir)) {
            wp_mkdir_p($public_zip_dir);
        }
        $public_zip_path = $public_zip_dir . '/' . $zip_filename;
        if (!rename($zip_path, $public_zip_path)) {
            error_log('Failed to move zip to public directory: ' . $public_zip_path);
            wp_send_json_error('Failed to move zip to public directory');
            return;
        }
        $zip_url = $upload_dir['baseurl'] . '/audio_zips/' . $zip_filename;
        // === END NEW ===

        // Clean up temp directory
        $all_temp_files = glob($temp_dir . '/*');
        foreach ($all_temp_files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                // Recursively remove any subdirectories (shouldn't exist, but just in case)
                array_map('unlink', glob($file . '/*'));
                rmdir($file);
            }
        }
        rmdir($temp_dir);

        // Return download URL
        wp_send_json_success([
            'message' => 'Conversion successful',
            'download_url' => $zip_url,
            'filename' => $zip_filename,
            'files_converted' => count($converted_files),
            'debug_info' => $debug_info
        ]);

    } catch (Exception $e) {
        error_log('Exception in audio processing: ' . $e->getMessage());
        wp_send_json_error('Conversion failed: ' . $e->getMessage());
    }
}
add_action('wp_ajax_handle_bulk_audio_processing', 'handle_bulk_audio_processing');


## Simplified Audio Class

````php
// ...existing code...

/**
 * Generate file paths based on product and purchase status
 * 
 * @param array $args Request arguments
 * @return array File path information
 */
private function generateFilePaths(array $args): array {
    $originalUrl = $args['url'];
    $fileName = $this->mainPlugin->getFiles()->generateDemoFileName($originalUrl);
    
    $purchased = $this->mainPlugin->getWooCommerce()?->woocommerceUserProduct($args['product_id']) ?? false;
    
    $this->addConsoleLog('generateFilePaths purchase status', ['product_id' => $args['product_id'], 'purchased' => $purchased]);
    
    // No more purchased folder - use WooCommerce's native file access
    $basePath = $this->mainPlugin->getFileHandler()->getFilesDirectoryPath();
    
    return [
        'fileName' => $fileName,
        'filePath' => $basePath . $fileName,
        'purchased' => $purchased,
        'original_url' => $this->getOriginalWooCommerceUrl($args['product_id'], $originalUrl)
    ];
}

/**
 * Get original WooCommerce download URL
 */
private function getOriginalWooCommerceUrl(int $productId, string $originalUrl): string {
    $product = wc_get_product($productId);
    if (!$product) return $originalUrl;
    
    $downloads = $product->get_downloads();
    foreach ($downloads as $download) {
        if ($download->get_file() === $originalUrl) {
            return $download->get_file();
        }
    }
    
    return $originalUrl;
}

/**
 * Generate audio URL - now much simpler
 */
public function generateAudioUrl(int $productId, string|int $fileIndex, array $fileData = []): string {
    $purchased = $this->mainPlugin->getWooCommerce()?->woocommerceUserProduct($productId);
    
    // If user owns product, return direct WooCommerce download URL
    if ($purchased) {
        return $this->getWooCommerceDownloadUrl($productId, $fileIndex);
    }
    
    // Otherwise use streaming API for demos
    return rest_url("bandfront-player/v1/stream/{$productId}/{$fileIndex}");
}

/**
 * Get WooCommerce download URL
 */
private function getWooCommerceDownloadUrl(int $productId, string|int $fileIndex): string {
    $product = wc_get_product($productId);
    $downloads = $product->get_downloads();
    
    // Get by index or ID
    $download = is_numeric($fileIndex) ? 
        array_values($downloads)[$fileIndex] ?? null :
        $downloads[$fileIndex] ?? null;
    
    return $download ? $download->get_file() : '';
}
// ...existing code...
````

## Format Download Handler

see files already done.

## Benefits of This Approach

1. **Leverage WooCommerce Infrastructure**: Uses existing `woocommerce_uploads/` directory and download permissions
2. **Pre-generated Formats**: No more on-the-fly processing - everything ready when product is saved
3. **Direct URLs**: Purchased users get direct file URLs, bypassing streaming API
4. **Simplified Caching**: No complex folder structures or file swapping
5. **Better Performance**: No FFmpeg processing during user requests
6. **Standard WooCommerce Flow**: Downloads work through WooCommerce's native system

## API Endpoints Become Simpler

````php
// For streaming (demo users)
GET /wp-json/bandfront-player/v1/stream/{product_id}/{track_index}

// For downloads (purchased users get direct URLs)
https://yoursite.com/wp-content/uploads/woocommerce_uploads/bfp-formats/123/mp3/track-name.mp3
https://yoursite.com/wp-content/uploads/woocommerce_uploads/bfp-formats/123/zips/flac.zip
````

This approach eliminates the need for the `purchased/` folder structure entirely and makes the whole system much cleaner and more performant.
















IGNORE BELOW THIS:




Based on the error "The element has no supported sources" and analyzing the code, here's a step-by-step plan to fix the audio streaming issue and implement a proper StreamController:

## Step-by-Step Implementation Plan

### 1. **Create StreamController.php**
- Location: `/var/www/html/wp-content/plugins/bandfront-player/src/StreamController.php`
- Purpose: Handle REST API streaming endpoints
- Key methods:
  - `register()` - Register REST API routes
  - `streamFile()` - Handle streaming requests
  - `checkPermission()` - Validate access permissions

### 2. **Update Plugin.php**
- Add StreamController initialization in `initComponents()`
- Remove the current `handleStreamingRequest()` method
- Remove the query parameter handler from `registerStreamingHandler()`
- Add getter method `getStreamController()`

### 3. **Update Audio.php**
- Modify `generateAudioUrl()` to use REST API URLs instead of query parameters
- Remove the outdated REST endpoint registration code
- Keep all audio processing methods intact

### 4. **Add methods to Utils/Files.php**
- Add `processCloudUrl()` - Handle cloud storage URLs
- Add `isLocal()` - Check if file is local and return path
- Add `getMimeType()` - Get MIME type for audio files
- Add `streamFile()` - Stream file with range support
- Add `getFilePath()` - Get file path for product

### 5. **Update Player.php**
- Ensure `includeMainPlayer()` properly logs debug info
- Verify audio URL generation is working
- No major changes needed, just ensure proper error logging

### 6. **Update engine.js**
- Add better source URL validation
- Add REST API URL support
- Improve error handling for failed sources
- Add automatic URL conversion from query params to REST

### 7. **Create/Update WooCommerce.php**
- Implement `woocommerceUserProduct()` method
- Add product purchase checking logic

## Key Changes Summary:

1. **URL Structure Change**:
   - From: `/?bfp-action=play&bfp-product=123&bfp-file=0`
   - To: `/wp-json/bandfront-player/v1/stream/123/0`

2. **Streaming Architecture**:
   - Move from query parameter handling to REST API
   - Centralize streaming logic in StreamController
   - Use WordPress authentication and permissions

3. **File Operations**:
   - Consolidate all file operations in Utils/Files.php
   - Remove duplicate code from Audio.php and Plugin.php
   - Add proper MIME type detection and range request support

4. **Error Handling**:
   - Add proper logging throughout the streaming pipeline
   - Validate URLs before attempting to play
   - Provide fallback mechanisms in JavaScript

The main issue appears to be that the audio URLs are not being properly generated or the streaming handler is not correctly serving the files. The REST API approach will provide a more robust and WordPress-compliant solution.