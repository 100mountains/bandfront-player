<?php
/**
 * Audio processing functionality for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Audio Processor Class
 * Handles all audio file processing, streaming, and manipulation
 */
class BFP_Audio_Engine {
    
    private $main_plugin;
    private $_preload_times = 0;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Create a temporal file and redirect to the new file
     */
    public function output_file($args) {
        if (empty($args['url'])) {
            return;
        }

        $url = $args['url'];
        $original_url = $url;
        $url = do_shortcode($url);
        $url_fixed = $this->fix_url($url);

        do_action('bfp_play_file', $args['product_id'], $url);

        $file_name = $this->demo_file_name($original_url);
        $o_file_name = 'o_' . $file_name;

        $purchased = $this->main_plugin->woocommerce_user_product($args['product_id']);
        if (false !== $purchased) {
            $o_file_name = 'purchased/o_' . $purchased . $file_name;
            $file_name = 'purchased/' . $purchased . '_' . $file_name;
        }

        $file_path = $this->main_plugin->get_files_directory_path() . $file_name;
        $o_file_path = $this->main_plugin->get_files_directory_path() . $o_file_name;

        if ($this->valid_demo($file_path)) {
            header('location: http' . ((is_ssl()) ? 's:' : ':') . $this->main_plugin->get_files_directory_url() . $file_name);
            exit;
        } elseif ($this->valid_demo($o_file_path)) {
            header('location: http' . ((is_ssl()) ? 's:' : ':') . $this->main_plugin->get_files_directory_url() . $o_file_name);
            exit;
        }

        try {
            $c = false;
            if (false !== ($path = $this->is_local($url_fixed))) {
                $c = copy($path, $file_path);
            } else {
                $response = wp_remote_get(
                    $url_fixed,
                    array(
                        'timeout' => BFP_REMOTE_TIMEOUT,
                        'stream' => true,
                        'filename' => $file_path,
                    )
                );
                if (!is_wp_error($response) && 200 == $response['response']['code']) {
                    $c = true;
                }
            }

            if (true === $c) {
                if (!function_exists('mime_content_type') || false === ($mime_type = mime_content_type($file_path))) {
                    $mime_type = 'audio/mpeg';
                }

                if (
                    !empty($args['secure_player']) &&
                    !empty($args['file_percent']) &&
                    0 !== ($file_percent = @intval($args['file_percent'])) &&
                    false === $purchased
                ) {
                    $this->process_secure_audio($file_path, $o_file_path, $file_percent, $file_name, $o_file_name, $args);
                }

                if (!headers_sent()) {
                    $this->send_file_headers($mime_type, $file_name, $file_path);
                }

                readfile($file_path);
                exit;
            }
        } catch (Exception $err) {
            error_log($err->getMessage());
        }
        
        $this->print_page_not_found('It is not possible to generate the file for demo. Possible causes are: - the amount of memory allocated to the php script on the web server is not enough, - the execution time is too short, - or the "uploads/bfp" directory does not have write permissions.');
    }
    
    /**
     * Process secure audio with limited playback percentage
     */
    private function process_secure_audio($file_path, $o_file_path, $file_percent, &$file_name, &$o_file_name, $args) {
        $ffmpeg = $this->main_plugin->get_global_attr('_bfp_ffmpeg', false);

        if ($ffmpeg && function_exists('shell_exec')) {
            $this->process_with_ffmpeg($file_path, $o_file_path, $file_percent);
        }

        if ($ffmpeg && file_exists($o_file_path)) {
            // BUG FIX: Need to update $file_path variable for later use
            $original_file_path = $file_path;
            if (unlink($file_path)) {
                if (!rename($o_file_path, $file_path)) {
                    $file_path = $o_file_path;
                    $file_name = $o_file_name;
                }
            } else {
                $file_path = $o_file_path;
                $file_name = $o_file_name;
            }
        } else {
            try {
                try {
                    require_once dirname(dirname(__FILE__)) . '/vendors/php-mp3/class.mp3.php';
                    $mp3 = new BFPMP3;
                    $mp3->cut_mp3($file_path, $o_file_path, 0, $file_percent/100, 'percent', false);
                    unset($mp3);
                    if (file_exists($o_file_path)) {
                        if (unlink($file_path)) {
                            if (!rename($o_file_path, $file_path)) {
                                $file_path = $o_file_path;
                                $file_name = $o_file_name;
                            }
                        } else {
                            $file_path = $o_file_path;
                            $file_name = $o_file_name;
                        }
                    }
                } catch (Exception $exp) {
                    $this->truncate_file($file_path, $file_percent);
                }
            } catch (Error $err) {
                $this->truncate_file($file_path, $file_percent);
            }
        }
        
        do_action('bfp_truncated_file', $args['product_id'], $args['url'], $file_path);
    }
    
    /**
     * Process file with ffmpeg
     */
    private function process_with_ffmpeg($file_path, $o_file_path, $file_percent) {
        $ffmpeg_path = rtrim($this->main_plugin->get_global_attr('_bfp_ffmpeg_path', ''), '/');
        if (is_dir($ffmpeg_path)) {
            $ffmpeg_path .= '/ffmpeg';
        }

        $ffmpeg_path = '"' . esc_attr($ffmpeg_path) . '"';
        $result = @shell_exec($ffmpeg_path . ' -i ' . escapeshellcmd($file_path) . ' 2>&1');
        if (!empty($result)) {
            // BUG FIX: Add array key existence check to prevent undefined offset warnings
            preg_match('/(?<=Duration: )(\d{2}:\d{2}:\d{2})\.\d{2}/', $result, $match);
            if (!empty($match[1])) {
                $time = explode(':', $match[1]) + array(00, 00, 00);
                $total = (!empty($time[0]) && is_numeric($time[0]) ? intval($time[0]) : 0) * 3600 + 
                         (!empty($time[1]) && is_numeric($time[1]) ? intval($time[1]) : 0) * 60 + 
                         (!empty($time[2]) && is_numeric($time[2]) ? intval($time[2]) : 0);
                $total = apply_filters('bfp_ffmpeg_time', floor($total * $file_percent / 100));

                $command = $ffmpeg_path . ' -hide_banner -loglevel panic -vn -i ' . preg_replace(["/^'/", "/'$/"], '"', escapeshellarg($file_path));

                $ffmpeg_watermark = trim($this->main_plugin->get_global_attr('_bfp_ffmpeg_watermark', ''));
                if (!empty($ffmpeg_watermark)) {
                    $ffmpeg_watermark = $this->fix_url($ffmpeg_watermark);
                    if (false !== ($watermark_path = $this->is_local($ffmpeg_watermark))) {
                        $watermark_path = str_replace(array('\\', ':', '.', "'"), array('/', '\:', '\.', "\'"), $watermark_path);
                        $command .= ' -filter_complex "amovie=\'' . trim(escapeshellarg($watermark_path), '"') . '\':loop=0,volume=0.3[s];[0][s]amix=duration=first,afade=t=out:st=' . max(0, $total - 2) . ':d=2"';
                    }
                }
                $command = str_replace("''", "'", $command);
                @shell_exec($command . '  -map 0:a -t ' . $total . ' -y ' . preg_replace(["/^'/", "/'$/"], '"', escapeshellarg($o_file_path)));
            }
        }
    }
    
    /**
     * Send file headers for audio streaming
     */
    private function send_file_headers($mime_type, $file_name, $file_path) {
        if (!$this->main_plugin->get_global_attr('_bfp_disable_302', 0)) {
            header("location: " . $this->main_plugin->get_files_directory_url() . $file_name, true, 302);
            exit;
        }

        header("Content-Type: " . $mime_type);
        header("Content-length: " . filesize($file_path));
        header('Content-Disposition: filename="' . $file_name . '"');
        header("Accept-Ranges: " . (stripos($mime_type, 'wav') ? 'none' : 'bytes'));
        header("Content-Transfer-Encoding: binary");
    }
    
    /**
     * Truncate file to a percentage of its size
     */
    public function truncate_file($file_path, $file_percent) {
        $h = fopen($file_path, 'r+');
        ftruncate($h, intval(filesize($file_path) * $file_percent / 100));
        fclose($h);
    }
    
    /**
     * Generate demo file name
     */
    public function demo_file_name($url) {
        $file_extension = pathinfo($url, PATHINFO_EXTENSION);
        $file_name = md5($url) . ((!empty($file_extension) && preg_match('/^[a-z\d]{3,4}$/i', $file_extension)) ? '.' . $file_extension : '.mp3');
        return $file_name;
    }
    
    /**
     * Check if demo file is valid
     */
    public function valid_demo($file_path) {
        if (!file_exists($file_path) || filesize($file_path) == 0) {
            return false;
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            return substr(finfo_file($finfo, $file_path), 0, 4) !== 'text';
        }
        return true;
    }
    
    /**
     * Fix URL for local files
     */
    public function fix_url($url) {
        if (file_exists($url)) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            $url_fixed = 'http' . (is_ssl() ? 's:' : ':') . $url;
        } elseif (strpos($url, '/') === 0) {
            $url_fixed = rtrim(BFP_WEBSITE_URL, '/') . $url;
        } else {
            $url_fixed = $url;
        }
        return $url_fixed;
    }
    
    /**
     * Check if file is local and return path
     */
    public function is_local($url) {
        $file_path = false;
        if (file_exists($url)) {
            $file_path = $url;
        }

        if (false === $file_path) {
            $attachment_id = attachment_url_to_postid($url);
            if ($attachment_id) {
                $attachment_path = get_attached_file($attachment_id);
                if ($attachment_path && file_exists($attachment_path)) {
                    $file_path = $attachment_path;
                }
            }
        }

        if (false === $file_path && defined('ABSPATH')) {
            $path_component = parse_url($url, PHP_URL_PATH);
            $path = rtrim(ABSPATH, '/') . '/' . ltrim($path_component, '/');
            if (file_exists($path)) {
                $file_path = $path;
            }

            if (false === $file_path) {
                $site_url = get_site_url(get_current_blog_id());
                $file_path = str_ireplace($site_url . '/', ABSPATH, $url);
                if (!file_exists($file_path)) {
                    $file_path = false;
                }
            }
        }

        return apply_filters('bfp_is_local', $file_path, $url);
    }
    
    /**
     * Check if the file is an audio file and return its type or false
     */
    public function is_audio($file_path) {
        $aux = function($file_path) {
            if (preg_match('/\.(mp3|ogg|oga|wav|wma|mp4)$/i', $file_path, $match)) {
                return $match[1];
            }
            if (preg_match('/\.m4a$/i', $file_path)) {
                return 'mp4';
            }
            if ($this->is_playlist($file_path)) {
                return 'hls';
            }
            return false;
        };

        $file_name = $this->demo_file_name($file_path);
        $demo_file_path = $this->main_plugin->get_files_directory_path() . $file_name;
        if ($this->valid_demo($demo_file_path)) return $aux($demo_file_path);

        $ext = $aux($file_path);
        if ($ext) return $ext;

        // From troubleshoot
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $troubleshoot_default_extension = $this->main_plugin->get_global_attr('_bfp_default_extension', false);
        if ((empty($extension) || !preg_match('/^[a-z\d]{3,4}$/i', $extension)) && $troubleshoot_default_extension) {
            return 'mp3';
        }

        return false;
    }
    
    /**
     * Check if the file is a playlist
     */
    public function is_playlist($file_path) {
        return preg_match('/\.(m3u|m3u8)$/i', $file_path);
    }
    
    /**
     * Get duration by URL
     */
    public function get_duration_by_url($url) {
        global $wpdb;
        try {
            $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid RLIKE %s;", $url));
            if (empty($attachment)) {
                $uploads_dir = wp_upload_dir();
                $uploads_url = $uploads_dir['baseurl'];
                $parsed_url = explode(parse_url($uploads_url, PHP_URL_PATH), $url);
                $this_host = str_ireplace('www.', '', parse_url(home_url(), PHP_URL_HOST));
                $file_host = str_ireplace('www.', '', parse_url($url, PHP_URL_HOST));
                if (!isset($parsed_url[1]) || empty($parsed_url[1]) || ($this_host != $file_host)) {
                    return false;
                }
                $file = trim($parsed_url[1], '/');
                $attachment = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_wp_attached_file' AND meta_value RLIKE %s;", $file));
            }
            if (!empty($attachment) && !empty($attachment[0])) {
                $metadata = wp_get_attachment_metadata($attachment[0]);
                if (false !== $metadata && !empty($metadata['length_formatted'])) {
                    return $metadata['length_formatted'];
                }
            }
        } catch (Exception $err) {
            error_log($err->getMessage());
        }
        return false;
    }
    
    /**
     * Generate audio URL
     */
    public function generate_audio_url($product_id, $file_index, $file_data = array()) {
        if (!empty($file_data['file'])) {
            $file_url = $file_data['file'];
            if (!empty($file_data['play_src']) || $this->is_playlist($file_url)) {
                return $file_url;
            }

            $_bfp_analytics_property = trim($this->main_plugin->get_global_attr('_bfp_analytics_property', ''));
            if ('' == $_bfp_analytics_property) {
                $files = get_post_meta($product_id, '_bfp_drive_files', true);
                $key = md5($file_url);
                if (!empty($files) && isset($files[$key])) {
                    return $files[$key]['url'];
                }

                $file_name = $this->demo_file_name($file_url);
                $o_file_name = 'o_' . $file_name;

                $purchased = $this->main_plugin->woocommerce_user_product($product_id);
                if (false !== $purchased) {
                    $o_file_name = 'purchased/o_' . $purchased . $file_name;
                    $file_name = 'purchased/' . $purchased . '_' . $file_name;
                }

                $file_path = $this->main_plugin->get_files_directory_path() . $file_name;
                $o_file_path = $this->main_plugin->get_files_directory_path() . $o_file_name;

                if ($this->valid_demo($file_path)) {
                    return 'http' . ((is_ssl()) ? 's:' : ':') . $this->main_plugin->get_files_directory_url() . $file_name;
                } elseif ($this->valid_demo($o_file_path)) {
                    return 'http' . ((is_ssl()) ? 's:' : ':') . $this->main_plugin->get_files_directory_url() . $o_file_name;
                }
            }
        }
        $url = BFP_WEBSITE_URL;
        $url .= ((strpos($url, '?') === false) ? '?' : '&') . 'bfp-action=play&bfp-product=' . $product_id . '&bfp-file=' . $file_index;
        return $url;
    }
    
    /**
     * Tracking play event for analytics
     */
    public function tracking_play_event($product_id, $file_url) {
        $_bfp_analytics_integration = $this->main_plugin->get_global_attr('_bfp_analytics_integration', 'ua');
        $_bfp_analytics_property = trim($this->main_plugin->get_global_attr('_bfp_analytics_property', ''));
        $_bfp_analytics_api_secret = trim($this->main_plugin->get_global_attr('_bfp_analytics_api_secret', ''));
        
        if (!empty($_bfp_analytics_property)) {
            $cid = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            try {
                if (isset($_COOKIE['_ga'])) {
                    $cid_parts = explode('.', sanitize_text_field(wp_unslash($_COOKIE['_ga'])), 3);
                    // BUG FIX: Add array bounds check to prevent undefined index
                    if (isset($cid_parts[2])) {
                        $cid = $cid_parts[2];
                    }
                }
            } catch (Exception $err) {
                error_log($err->getMessage());
            }

            if ('ua' == $_bfp_analytics_integration) {
                $_response = wp_remote_post(
                    'http://www.google-analytics.com/collect',
                    array(
                        'body' => array(
                            'v' => 1,
                            'tid' => $_bfp_analytics_property,
                            'cid' => $cid,
                            't' => 'event',
                            'ec' => 'Music Player for WooCommerce',
                            'ea' => 'play',
                            'el' => $file_url,
                            'ev' => $product_id,
                        ),
                    )
                );
            } else {
                $_response = wp_remote_post(
                    'https://www.google-analytics.com/mp/collect?api_secret=' . $_bfp_analytics_api_secret . '&measurement_id=' . $_bfp_analytics_property,
                    array(
                        'sslverify' => true,
                        'headers' => array(
                            'Content-Type' => 'application/json',
                        ),
                        'body' => json_encode(
                            array(
                                'client_id' => $cid,
                                'events' => array(
                                    array(
                                        'name' => 'play',
                                        'params' => array(
                                            'event_category' => 'Music Player for WooCommerce',
                                            'event_label' => $file_url,
                                            'event_value' => $product_id,
                                        ),
                                    ),
                                ),
                            )
                        ),
                    )
                );
            }

            if (is_wp_error($_response)) {
                error_log($_response->get_error_message());
            }
        }
    }
    
    /**
     * Handle preload functionality
     */
    public function preload($preload, $audio_url) {
        $result = $preload;
        if (strpos($audio_url, 'bfp-action=play') !== false) {
            if ($this->_preload_times) {
                $result = 'none';
            }
            $this->_preload_times++;
        }
        return $result;
    }
    
    /**
     * Print not found page if file is not accessible
     */
    private function print_page_not_found($text = 'The requested URL was not found on this server') {
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

    // In any method that processes Google Drive URLs
    private function process_cloud_url($url) {
        // Use the new cloud tools class
        if (strpos($url, 'drive.google.com') !== false) {
            return BFP_Cloud_Tools::get_google_drive_download_url($url);
        }
        return $url;
    }
}
