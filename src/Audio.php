<?php
namespace bfp;

/**
 * Audio processing functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Audio Processor
 * Handles all audio file processing, streaming, and manipulation
 */
class Audio {
    
    private Plugin $mainPlugin;
    private int $preloadTimes = 0;
    
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    /**
     * Create a temporal file and redirect to the new file
     */
    public function outputFile(array $args): void {
        if (empty($args['url'])) {
            return;
        }

        $url = $args['url'];
        $originalUrl = $url;
        $url = do_shortcode($url);
        $urlFixed = $this->mainPlugin->getFiles()->fixUrl($url);

        do_action('bfp_play_file', $args['product_id'], $url);

        $fileName = $this->mainPlugin->getFiles()->generateDemoFileName($originalUrl);
        $oFileName = 'o_' . $fileName;

        $purchased = $this->mainPlugin->getWooCommerce()?->woocommerceUserProduct($args['product_id']) ?? false;
        if (false !== $purchased) {
            $oFileName = 'purchased/o_' . $purchased . $fileName;
            $fileName = 'purchased/' . $purchased . '_' . $fileName;
        }

        $filePath = $this->mainPlugin->getFileHandler()->getFilesDirectoryPath() . $fileName;
        $oFilePath = $this->mainPlugin->getFileHandler()->getFilesDirectoryPath() . $oFileName;

        if ($this->mainPlugin->getFiles()->isValidDemo($filePath)) {
            header('location: http' . ((is_ssl()) ? 's:' : ':') . $this->mainPlugin->getFileHandler()->getFilesDirectoryUrl() . $fileName);
            exit;
        } elseif ($this->mainPlugin->getFiles()->isValidDemo($oFilePath)) {
            header('location: http' . ((is_ssl()) ? 's:' : ':') . $this->mainPlugin->getFileHandler()->getFilesDirectoryUrl() . $oFileName);
            exit;
        }

        $c = $this->mainPlugin->getFiles()->createDemoFile($urlFixed, $filePath);

        if (true === $c) {
            $mimeType = $this->mainPlugin->getFiles()->getMimeType($filePath);

            if (
                !empty($args['secure_player']) &&
                !empty($args['file_percent']) &&
                0 !== ($filePercent = @intval($args['file_percent'])) &&
                false === $purchased
            ) {
                $this->processSecureAudio($filePath, $oFilePath, $filePercent, $fileName, $oFileName, $args);
            }

            if (!headers_sent()) {
                $this->sendFileHeaders($mimeType, $fileName, $filePath);
            }

            readfile($filePath);
            exit;
        }
        
        $this->printPageNotFound('It is not possible to generate the file for demo. Possible causes are: - the amount of memory allocated to the php script on the web server is not enough, - the execution time is too short, - or the "uploads/bfp" directory does not have write permissions.');
    }
    
    /**
     * Process secure audio with limited playback percentage
     */
    private function processSecureAudio(string $filePath, string $oFilePath, int $filePercent, string &$fileName, string &$oFileName, array $args): void {
        $ffmpeg = $this->mainPlugin->getConfig()->getState('_bfp_ffmpeg', false);

        if ($ffmpeg && function_exists('shell_exec')) {
            $this->processWithFfmpeg($filePath, $oFilePath, $filePercent);
        }

        if ($ffmpeg && file_exists($oFilePath)) {
            $originalFilePath = $filePath;
            if (unlink($filePath)) {
                if (!rename($oFilePath, $filePath)) {
                    $filePath = $oFilePath;
                    $fileName = $oFileName;
                }
            } else {
                $filePath = $oFilePath;
                $fileName = $oFileName;
            }
        } else {
            try {
                try {
                    require_once dirname(dirname(__FILE__)) . '/vendor/php-mp3/class.mp3.php';
                    $mp3 = new \BFPMP3();
                    $mp3->cut_mp3($filePath, $oFilePath, 0, $filePercent/100, 'percent', false);
                    unset($mp3);
                    if (file_exists($oFilePath)) {
                        if (unlink($filePath)) {
                            if (!rename($oFilePath, $filePath)) {
                                $filePath = $oFilePath;
                                $fileName = $oFileName;
                            }
                        } else {
                            $filePath = $oFilePath;
                            $fileName = $oFileName;
                        }
                    }
                } catch (\Exception $exp) {
                    $this->mainPlugin->getFiles()->truncateFile($filePath, $filePercent);
                }
            } catch (\Error $err) {
                $this->mainPlugin->getFiles()->truncateFile($filePath, $filePercent);
            }
        }
        
        do_action('bfp_truncated_file', $args['product_id'], $args['url'], $filePath);
    }
    
    /**
     * Send file headers for audio streaming
     */
    private function sendFileHeaders(string $mimeType, string $fileName, string $filePath): void {
        header("Content-Type: " . $mimeType);
        header("Content-length: " . filesize($filePath));
        header('Content-Disposition: filename="' . $fileName . '"');
        header("Accept-Ranges: " . (stripos($mimeType, 'wav') ? 'none' : 'bytes'));
        header("Content-Transfer-Encoding: binary");
    }
    
    /**
     * Get duration by URL
     */
    public function getDurationByUrl(string $url): string|false {
        global $wpdb;
        try {
            $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid RLIKE %s;", $url));
            if (empty($attachment)) {
                $uploadsDir = wp_upload_dir();
                $uploadsUrl = $uploadsDir['baseurl'];
                $parsedUrl = explode(parse_url($uploadsUrl, PHP_URL_PATH), $url);
                $thisHost = str_ireplace('www.', '', parse_url(home_url(), PHP_URL_HOST));
                $fileHost = str_ireplace('www.', '', parse_url($url, PHP_URL_HOST));
                if (!isset($parsedUrl[1]) || empty($parsedUrl[1]) || ($thisHost != $fileHost)) {
                    return false;
                }
                $file = trim($parsedUrl[1], '/');
                $attachment = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_wp_attached_file' AND meta_value RLIKE %s;", $file));
            }
            if (!empty($attachment) && !empty($attachment[0])) {
                $metadata = wp_get_attachment_metadata($attachment[0]);
                if (false !== $metadata && !empty($metadata['length_formatted'])) {
                    return $metadata['length_formatted'];
                }
            }
        } catch (\Exception $err) {
            error_log($err->getMessage());
        }
        return false;
    }
    
    /**
     * Generate audio URL
     */
    public function generateAudioUrl(int $productId, string|int $fileIndex, array $fileData = []): string {
        if (!empty($fileData['file'])) {
            $fileUrl = $fileData['file'];
            
            // For playlists and direct play sources, return the URL as-is
            if (!empty($fileData['play_src']) || $this->mainPlugin->getFiles()->isPlaylist($fileUrl)) {
                return $fileUrl;
            }
            
            // For Google Drive files stored in meta (legacy support)
            $files = get_post_meta($productId, '_bfp_drive_files', true);
            if (!empty($files)) {
                $key = md5($fileUrl);
                if (isset($files[$key])) {
                    return $files[$key]['url'];
                }
            }
        }
        
        // Use REST API endpoint for secure streaming
        return rest_url("bandfront-player/v1/stream/{$productId}/{$fileIndex}");
    }
    
    /**
     * Tracking play event for analytics
     */
    public function trackingPlayEvent(int $productId, string $fileUrl): void {
        $analyticsSettings = $this->mainPlugin->getConfig()->getStates([
            '_bfp_analytics_integration',
            '_bfp_analytics_property',
            '_bfp_analytics_api_secret'
        ]);
        
        $analyticsIntegration = $analyticsSettings['_bfp_analytics_integration'];
        $analyticsProperty = trim($analyticsSettings['_bfp_analytics_property']);
        $analyticsApiSecret = trim($analyticsSettings['_bfp_analytics_api_secret']);
        
        if (!empty($analyticsProperty)) {
            $cid = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            try {
                if (isset($_COOKIE['_ga'])) {
                    $cidParts = explode('.', sanitize_text_field(wp_unslash($_COOKIE['_ga'])), 3);
                    if (isset($cidParts[2])) {
                        $cid = $cidParts[2];
                    }
                }
            } catch (\Exception $err) {
                error_log($err->getMessage());
            }

            if ('ua' == $analyticsIntegration) {
                $response = wp_remote_post(
                    'http://www.google-analytics.com/collect',
                    [
                        'body' => [
                            'v' => 1,
                            'tid' => $analyticsProperty,
                            'cid' => $cid,
                            't' => 'event',
                            'ec' => 'Music Player for WooCommerce',
                            'ea' => 'play',
                            'el' => $fileUrl,
                            'ev' => $productId,
                        ],
                    ]
                );
            } else {
                $response = wp_remote_post(
                    'https://www.google-analytics.com/mp/collect?api_secret=' . $analyticsApiSecret . '&measurement_id=' . $analyticsProperty,
                    [
                        'sslverify' => true,
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'body' => json_encode(
                            [
                                'client_id' => $cid,
                                'events' => [
                                    [
                                        'name' => 'play',
                                        'params' => [
                                            'event_category' => 'Music Player for WooCommerce',
                                            'event_label' => $fileUrl,
                                            'event_value' => $productId,
                                        ],
                                    ],
                                ],
                            ]
                        ),
                    ]
                );
            }

            if (is_wp_error($response)) {
                error_log($response->get_error_message());
            }
        }
    }
    
    /**
     * Handle preload functionality
     */
    public function preload(string $preload, string $audioUrl): string {
        $result = $preload;
        if (strpos($audioUrl, 'bfp-action=play') !== false) {
            if ($this->preloadTimes) {
                $result = 'none';
            }
            $this->preloadTimes++;
        }
        return $result;
    }
    
    /**
     * Print not found page if file is not accessible
     */
    private function printPageNotFound(string $text = 'The requested URL was not found on this server'): void {
        header('Status: 404 Not Found');
        echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
              <HTML><HEAD>
              <TITLE>404 Not Found</TITLE>
              </HEAD><BODY>
              <H1>Not Found</H1>
              <P>' . esc_html($text) . '</P>
              </BODY></HTML>
             ';
    }

    /**
     * Process play request for a specific file
     */
    private function processPlayRequest(int $productId, int $fileIndex): void {
        $files = $this->mainPlugin->getPlayer()->getProductFiles($productId);
        
        if (!empty($files) && isset($files[$fileIndex])) {
            $file = $files[$fileIndex];
            
            // Increment playback counter
            if ($this->mainPlugin->getAnalytics()) {
                $this->mainPlugin->getAnalytics()->incrementPlaybackCounter($productId);
            }
            
            $demoSettings = $this->mainPlugin->getConfig()->getStates([
                '_bfp_secure_player',
                '_bfp_file_percent'
            ], $productId);
            
            // Output the file
            $this->outputFile([
                'url' => $file['file'],
                'product_id' => $productId,
                'secure_player' => $demoSettings['_bfp_secure_player'],
                'file_percent' => $demoSettings['_bfp_file_percent']
            ]);
        }
    }
    
    /**
     * Process with ffmpeg
     */
    private function processWithFfmpeg(string $filePath, string $oFilePath, int $filePercent): void {
        $ffmpegSettings = $this->mainPlugin->getConfig()->getStates([
            '_bfp_ffmpeg_path',
            '_bfp_ffmpeg_watermark'
        ]);
        
        $ffmpegPath = rtrim($ffmpegSettings['_bfp_ffmpeg_path'], '/');
        if (is_dir($ffmpegPath)) {
            $ffmpegPath .= '/ffmpeg';
        }

        $ffmpegPath = '"' . esc_attr($ffmpegPath) . '"';
        $result = @shell_exec($ffmpegPath . ' -i ' . escapeshellcmd($filePath) . ' 2>&1');
        if (!empty($result)) {
            preg_match('/(?<=Duration: )(\d{2}:\d{2}:\d{2})\.\d{2}/', $result, $match);
            if (!empty($match[1])) {
                $time = explode(':', $match[1]);
                $hours = isset($time[0]) && is_numeric($time[0]) ? intval($time[0]) : 0;
                $minutes = isset($time[1]) && is_numeric($time[1]) ? intval($time[1]) : 0;
                $seconds = isset($time[2]) && is_numeric($time[2]) ? intval($time[2]) : 0;
                $total = $hours * 3600 + $minutes * 60 + $seconds;
                $total = apply_filters('bfp_ffmpeg_time', floor($total * $filePercent / 100));

                $command = $ffmpegPath . ' -hide_banner -loglevel panic -vn -i ' . preg_replace(["/^'/", "/'$/"], '"', escapeshellarg($filePath));

                $ffmpegWatermark = trim($ffmpegSettings['_bfp_ffmpeg_watermark']);
                if (!empty($ffmpegWatermark)) {
                    $ffmpegWatermark = $this->mainPlugin->getFiles()->fixUrl($ffmpegWatermark);
                    if (false !== ($watermarkPath = $this->mainPlugin->getFiles()->isLocal($ffmpegWatermark))) {
                        $watermarkPath = str_replace(['\\', ':', '.', "'"], ['/', '\:', '\.', "\'"], $watermarkPath);
                        $command .= ' -filter_complex "amovie=\'' . trim(escapeshellarg($watermarkPath), '"') . '\':loop=0,volume=0.3[s];[0][s]amix=duration=first,afade=t=out:st=' . max(0, $total - 2) . ':d=2"';
                    }
                }
                $command = str_replace("''", "'", $command);
                @shell_exec($command . '  -map 0:a -t ' . $total . ' -y ' . preg_replace(["/^'/", "/'$/"], '"', escapeshellarg($oFilePath)));
            }
        }
    }
}