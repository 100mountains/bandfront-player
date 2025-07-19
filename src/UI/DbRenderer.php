<?php
namespace Bandfront\UI;

use Bandfront\Core\Config;
use Bandfront\Db\Monitor;
use Bandfront\Storage\FileManager;
use Bandfront\Utils\Debug;

// Set domain for UI
Debug::domain('ui');

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Database Monitor UI
 */
class DbRenderer {
    
    private Config $config;
    private Monitor $monitor;
    private FileManager $fileManager;
    
    public function __construct(Config $config, Monitor $monitor, FileManager $fileManager) {
        $this->config = $config;
        $this->monitor = $monitor;
        $this->fileManager = $fileManager;
    }
   

    /**
     * Render the complete Database Monitor section
     * This is used in the dev-tools.php template
     */
    public function renderDatabaseMonitorSection(): void {
        // Include template functions
        require_once dirname(dirname(__DIR__)) . '/templates/db-templates.php';
  
        // Enqueue admin styles
        wp_enqueue_style(
            'bfp-db-monitor',
            BFP_PLUGIN_URL . 'assets/css/db-monitor.css',
            [],
            BFP_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'bfp-db-monitor',
            BFP_PLUGIN_URL . 'assets/js/db-monitor.js',
            ['jquery'],
            BFP_VERSION,
            true
        );

        // Get the monitoring setting
        $monitoring_enabled = (bool) $this->config->getState('enable_db_monitoring', false);
        
        // Render checkbox
        bfp_render_monitoring_checkbox($monitoring_enabled);
        
        if (!$monitoring_enabled) {
            bfp_render_monitoring_disabled_notice();
        } else {
            // Render sub-tabs
            bfp_render_db_subtabs();
            ?>
            
            <!-- Monitoring Tab Content -->
            <div class="bfp-subtab-content" id="monitoring-subtab" style="display: block;">
                <?php $this->renderMonitoringContent(); ?>
            </div>

            <!-- Products Tab Content -->
            <div class="bfp-subtab-content" id="products-subtab" style="display: none;">
                <?php $this->renderProductsContent(); ?>
            </div>

            <!-- Schema Tab Content -->
            <div class="bfp-subtab-content" id="schema-subtab" style="display: none;">
                <?php $this->renderSchemaContent(); ?>
            </div>
            <?php
        }
        
        $this->renderDatabaseMonitorScripts();
        $this->renderDatabaseMonitorStyles();
        
        // Debug output
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<!-- BFP Debug -->';
            echo '<!-- monitoring_enabled: ' . var_export($monitoring_enabled, true) . ' -->';
            echo '<!-- End BFP Debug -->';
        }
    }
    
    /**
     * Render monitoring content
     */
    private function renderMonitoringContent(): void {
        // Test action buttons
        bfp_render_test_actions();
        
        // Activity monitor
        bfp_render_activity_monitor();
        
        // Get stats data
        $db_stats = $this->getDatabaseStats();
        $perf_metrics = $this->getPerformanceMetrics();
        
        // Render monitor grid
        bfp_render_monitor_grid($db_stats, $perf_metrics);
    }
    
    /**
     * Get database statistics
     */
    private function getDatabaseStats(): array {
        global $wpdb;
        
        $player_meta_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_bfp_%'
        ");
        
        $today_updates = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_bfp_%' 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_modified >= CURDATE()
            )
        ");
        
        $table_info = $wpdb->get_row("
            SELECT 
                ROUND((data_length + index_length) / 1024 / 1024, 2) AS total_size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = '" . DB_NAME . "'
            AND table_name = '{$wpdb->postmeta}'
        ");
        
        return [
            [
                'label' => __('Player Meta Entries', 'bandfront-player'),
                'value' => number_format($player_meta_count)
            ],
            [
                'label' => __('Today\'s Updates', 'bandfront-player'),
                'value' => number_format($today_updates)
            ],
            [
                'label' => __('Postmeta Table Size', 'bandfront-player'),
                'value' => ($table_info->total_size_mb ?? '0') . ' MB'
            ]
        ];
    }
    
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array {
        global $wpdb;
        
        $start = microtime(true);
        $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE '_bfp_%'");
        $query_time = round((microtime(true) - $start) * 1000, 2);
        
        $transient_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_bfp_%'
        ");
        
        return [
            [
                'label' => __('Avg Query Time', 'bandfront-player'),
                'value' => $query_time . ' ms'
            ],
            [
                'label' => __('Active Transients', 'bandfront-player'),
                'value' => number_format($transient_count)
            ]
        ];
    }
    
    /**
     * Render products content
     */
    private function renderProductsContent(): void {
        $products = $this->monitor->getWooCommerceProducts(20);
        
        ?>
        <div class="bfa-schema-container">
            <h3><?php _e('Product Audio Configuration', 'bandfront-player'); ?></h3>
            
            <?php 
            // Render product stats
            bfp_render_product_stats(
                count($products),
                $this->countProductsWithAudio($products),
                $this->getTotalAudioFiles()
            );
            ?>
            
            <?php if (empty($products)): ?>
                <p><?php _e('No products found.', 'bandfront-player'); ?></p>
            <?php else: ?>
                <!-- Products List -->
                <div class="bfa-schema-sections">
                    <?php foreach ($products as $product): 
                        $product_id = $product->ID;
                        $has_audio = $this->productHasAudio($product_id);
                    ?>
                    <div class="bfa-schema-section">
                        <?php bfp_render_product_header($product_id, get_the_title($product_id), $has_audio); ?>
                        <div id="product-<?php echo $product_id; ?>" class="bfa-schema-table" style="display: none;">
                            <?php $this->renderProductDetails($product_id); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- File System Section -->
            <div class="bfa-schema-section" style="margin-top: 30px;">
                <h4 class="bfa-section-header bfa-collapsible" data-target="filesystem-info">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('File System & Directories', 'bandfront-player'); ?>
                    <span class="bfa-toggle dashicons dashicons-arrow-down-alt2"></span>
                </h4>
                <div id="filesystem-info" class="bfa-schema-table" style="display: none;">
                    <?php $this->renderFileSystemInfo(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render detailed product information
     */
    private function renderProductDetails(int $product_id): void {
        // Get product info
        $product = wc_get_product($product_id);
        $product_info = [
            'post_type' => get_post_type($product_id),
            'product_type' => $product ? $product->get_type() : 'N/A',
            'status' => get_post_status($product_id),
            'price' => $product ? wc_price($product->get_price()) : 'N/A',
            'permalink' => get_permalink($product_id)
        ];
        
        // Render product info table
        bfp_render_product_info_table($product_info);
        
        // Get and render audio files (moved before settings for better UX)
        $audio_files = $this->getProductAudioFiles($product_id);
        bfp_render_audio_files_enhanced($audio_files);
        
        // Get and render BFP settings in collapsible section
        $bfp_settings = $this->getProductBfpSettings($product_id);
        bfp_render_bfp_settings_collapsible($bfp_settings);
    }
    
    /**
     * Get product BFP settings formatted for display
     */
    private function getProductBfpSettings(int $product_id): array {
        global $wpdb;
        
        $product_meta = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value 
             FROM {$wpdb->postmeta} 
             WHERE post_id = %d 
             AND meta_key LIKE '_bfp_%'
             ORDER BY meta_key",
            $product_id
        ));
        
        $settings = [];
        foreach ($product_meta as $meta) {
            $value = maybe_unserialize($meta->meta_value);
            $settings[] = [
                'key' => $meta->meta_key,
                'value' => $value,
                'type' => $this->getValueType($value),
                'formatted_value' => $this->formatConfigValue($value)
            ];
        }
        
        return $settings;
    }
    
    /**
     * Render schema content
     */
    private function renderSchemaContent(): void {
        $allSettings = $this->config->getAllSettings();
        $globalAttrs = $this->config->getAllGlobalAttrs();
        $settingsConfig = $this->buildSettingsConfig($allSettings);
        
        ?>
        <div class="bfa-schema-container">
            <h3><?php _e('Configuration Schema & Current Values', 'bandfront-player'); ?></h3>
            
            <!-- Quick Stats -->
            <div class="bfa-schema-stats">
                <div class="bfa-stat-box">
                    <span class="bfa-stat-number"><?php echo count($allSettings); ?></span>
                    <span class="bfa-stat-label"><?php _e('Total Settings', 'bandfront-player'); ?></span>
                </div>
                <div class="bfa-stat-box">
                    <span class="bfa-stat-number"><?php echo count($globalAttrs); ?></span>
                    <span class="bfa-stat-label"><?php _e('Global Settings', 'bandfront-player'); ?></span>
                </div>
                <div class="bfa-stat-box">
                    <span class="bfa-stat-number"><?php echo count(array_filter($allSettings)); ?></span>
                    <span class="bfa-stat-label"><?php _e('Non-empty Values', 'bandfront-player'); ?></span>
                </div>
            </div>
            
            <!-- All Settings Display -->
            <div class="bfa-schema-sections">
                <?php
                $grouped = $this->groupSettings($allSettings, $settingsConfig);
                
                foreach ($grouped as $groupName => $items) {
                    if (empty($items)) continue;
                    
                    $icon = $this->getGroupIcon($groupName);
                    
                    // Format items for template
                    $formatted_items = [];
                    foreach ($items as $item) {
                        $formatted_items[] = [
                            'key' => $item['key'],
                            'type' => $item['config']['type'] ?? 'unknown',
                            'config' => $item['config'],
                            'formatted_value' => $this->formatConfigValue($item['value']),
                            'formatted_default' => $this->formatConfigValue($item['config']['default'] ?? 'N/A')
                        ];
                    }
                    
                    bfp_render_schema_group($groupName, $icon, $formatted_items);
                }
                ?>
            </div>
            
            <?php bfp_render_raw_dump($allSettings); ?>
        </div>
        <?php
    }
    
    /**
     * Render file system information
     */
    public function renderFileSystemInfo(): void {
        $directories = $this->getDirectoryInfo();
        bfp_render_directory_table($directories);
        
        $file_stats = $this->getFileTypeStats();
        if (!empty($file_stats)) {
            // Format file sizes
            foreach ($file_stats as &$stats) {
                $stats['formatted_size'] = $this->formatFileSize($stats['size']);
            }
            bfp_render_file_stats_table($file_stats);
        }
    }
    
    /**
     * Get directory information
     */
    private function getDirectoryInfo(): array {
        // Use WordPress best practices for directory paths
        $upload_dir = wp_upload_dir();
        
        // Get plugin directory using WordPress functions
        $plugin_dir = plugin_dir_path(dirname(dirname(__DIR__)));
        
        $directories = [
            'Plugin Root' => $plugin_dir,
            'Plugin Assets' => $plugin_dir . 'assets/',
            'Upload Directory' => $upload_dir['basedir'] . '/',
            'Upload URL' => $upload_dir['baseurl'] . '/',
            'BFP Files' => $upload_dir['basedir'] . '/bandfront-player-files/',
            'BFP Demos' => $upload_dir['basedir'] . '/bandfront-player-files/demos/',
            'BFP Previews' => $upload_dir['basedir'] . '/bandfront-player-files/previews/',
            'BFP Temp' => $upload_dir['basedir'] . '/bandfront-player-files/temp/',
            'WP Content' => WP_CONTENT_DIR . '/',
            'WP Plugins' => WP_PLUGIN_DIR . '/',
        ];
        
        $result = [];
        foreach ($directories as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            
            // Don't scan large directories
            $skip_scan = in_array($name, ['WP Content', 'WP Plugins', 'Upload Directory']);
            $files = ($exists && !$skip_scan) ? $this->scanDirectory($path) : ['count' => 0, 'size' => 0];
            
            $result[] = [
                'name' => $name,
                'path' => $path,
                'exists' => $exists,
                'writable' => $writable,
                'file_count' => $files['count'],
                'size' => $files['size'],
                'formatted_size' => $this->formatFileSize($files['size'])
            ];
        }
        
        return $result;
    }
    
    /**
     * Group settings by category
     */
    private function groupSettings(array $allSettings, array $settingsConfig): array {
        $grouped = [
            'General' => [],
            'Player' => [],
            'Demo & Security' => [],
            'Audio Engine' => [],
            'Cloud Storage' => [],
            'Developer' => [],
            'Other' => []
        ];
        
        foreach ($allSettings as $key => $value) {
            $item = [
                'key' => $key,
                'value' => $value,
                'config' => $settingsConfig[$key] ?? ['type' => 'unknown', 'default' => null]
            ];
            
            // Categorize by key prefix
            if (strpos($key, '_bfp_player') === 0 || strpos($key, '_bfp_enable_player') === 0 || 
                strpos($key, '_bfp_merge') === 0 || strpos($key, '_bfp_single') === 0 ||
                strpos($key, '_bfp_play') === 0 || strpos($key, '_bfp_loop') === 0 ||
                strpos($key, '_bfp_fade') === 0 || strpos($key, '_bfp_on_') === 0) {
                $grouped['Player'][] = $item;
            } elseif (strpos($key, '_bfp_secure') === 0 || strpos($key, '_bfp_demo_duration_percent') === 0 || 
                      strpos($key, '_bfp_message') === 0) {
                $grouped['Demo & Security'][] = $item;
            } elseif (strpos($key, '_bfp_audio') === 0 || strpos($key, '_bfp_ffmpeg') === 0 ||
                      strpos($key, '_bfp_enable_vis') === 0) {
                $grouped['Audio Engine'][] = $item;
            } elseif (strpos($key, '_bfp_cloud') === 0 || strpos($key, '_bfp_use_custom_demos') === 0 ||
                      strpos($key, '_bfp_direct_own') === 0) {
                $grouped['Cloud Storage'][] = $item;
            } elseif (strpos($key, '_bfp_debug') === 0 || strpos($key, 'enable_db_monitoring') === 0 ||
                      strpos($key, '_bfp_dev_mode') === 0) {
                $grouped['Developer'][] = $item;
            } elseif (strpos($key, '_bfp_') === 0) {
                $grouped['General'][] = $item;
            } else {
                $grouped['Other'][] = $item;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Get icon for setting group
     */
    private function getGroupIcon(string $groupName): string {
        $icons = [
            'General' => 'dashicons-admin-generic',
            'Player' => 'dashicons-format-audio',
            'Demo & Security' => 'dashicons-shield',
            'Audio Engine' => 'dashicons-controls-volumeon',
            'Cloud Storage' => 'dashicons-cloud',
            'Developer' => 'dashicons-code-standards',
        ];
        
        return $icons[$groupName] ?? 'dashicons-admin-settings';
    }
    
    /**
     * Get product audio files with formatted data
     */
    private function getProductAudioFiles(int $product_id): array {
        $files = [];
        
        // Get basic file URLs from meta
        $meta_keys = ['_bfp_file_url', '_bfp_file_urls', '_bfp_demo_file_url', '_bfp_demo_file_urls'];
        
        foreach ($meta_keys as $key) {
            $value = get_post_meta($product_id, $key, true);
            if ($value) {
                if (is_array($value)) {
                    foreach ($value as $file_url) {
                        $file_info = $this->analyzeFileUrl($file_url, $key);
                        $file_info['formatted_size'] = $this->formatFileSize($file_info['size']);
                        $file_info['source'] = 'meta';
                        $files[] = $file_info;
                    }
                } else {
                    $file_info = $this->analyzeFileUrl($value, $key);
                    $file_info['formatted_size'] = $this->formatFileSize($file_info['size']);
                    $file_info['source'] = 'meta';
                    $files[] = $file_info;
                }
            }
        }
        
        // Get detailed file information from FileManager
        $product = wc_get_product($product_id);
        if ($product) {
            // Get all product files
            $allFiles = $this->fileManager->getAllProductFiles($product, []);
            
            // Get internal file details
            $internalFiles = $this->fileManager->getProductFilesInternal([
                'product' => $product,
                'all' => true
            ]);
            
            // Add FileManager data
            if (!empty($internalFiles)) {
                foreach ($internalFiles as $index => $file) {
                    $fileData = [
                        'path' => $file['file'] ?? '',
                        'filename' => basename($file['file'] ?? ''),
                        'type' => isset($file['play_src']) && $file['play_src'] ? 'Demo/Preview' : 'Full Audio',
                        'media_type' => $file['media_type'] ?? 'unknown',
                        'product_id' => $file['product'] ?? $product_id,
                        'index' => $index,
                        'source' => 'filemanager',
                        'exists' => false,
                        'size' => 0,
                        'formatted_size' => 'N/A'
                    ];
                    
                    // Check file existence
                    if (!empty($file['file'])) {
                        $upload_dir = wp_upload_dir();
                        $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file['file']);
                        
                        if (file_exists($local_path)) {
                            $fileData['exists'] = true;
                            $fileData['size'] = filesize($local_path);
                            $fileData['formatted_size'] = $this->formatFileSize($fileData['size']);
                        }
                    }
                    
                    $files[] = $fileData;
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Count products that have audio files
     */
    private function countProductsWithAudio(array $products): int {
        $count = 0;
        foreach ($products as $product) {
            if ($this->productHasAudio($product->ID)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Check if a product has audio files
     */
    private function productHasAudio(int $product_id): bool {
        // First check if BFP is enabled for this product
        if (!$this->config->getState('_bfp_enable_player', false, $product_id)) {
            return false;
        }
        
        // Get the product
        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }
        
        // Use FileManager to get all files for this product
        $files = $this->fileManager->getProductFilesInternal([
            'product' => $product,
            'all' => true
        ]);
        
        // If we have any files from FileManager, we have audio
        if (!empty($files)) {
            return true;
        }
        
        // Also check if product has downloadable files
        if ($product->is_downloadable()) {
            $downloads = $product->get_downloads();
            if (!empty($downloads)) {
                return true;
            }
        }
        
        // Check for own demos
        $ownDemos = intval($this->config->getState('_bfp_use_custom_demos', 0, $product_id));
        $demosList = $this->config->getState('_bfp_demos_list', [], $product_id);
        if ($ownDemos && !empty($demosList)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get total count of audio files
     */
    private function getTotalAudioFiles(): int {
        $count = 0;
        
        // Get the same products we're displaying
        $products = $this->monitor->getWooCommerceProducts(20);
        
        foreach ($products as $product_obj) {
            $product = wc_get_product($product_obj->ID);
            if (!$product) {
                continue;
            }
            
            // Skip if player not enabled
            if (!$this->config->getState('_bfp_enable_player', false, $product_obj->ID)) {
                continue;
            }
            
            // Get files using FileManager
            $files = $this->fileManager->getProductFilesInternal([
                'product' => $product,
                'all' => true
            ]);
            
            $count += count($files);
        }
        
        return $count;
    }
    
    /**
     * Analyze file URL and get information
     */
    private function analyzeFileUrl(string $url, string $meta_key): array {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $filename = basename($path);
        
        // Determine file type
        $type = 'Full Audio';
        if (strpos($meta_key, 'demo') !== false) {
            $type = 'Demo/Preview';
        }
        
        // Check if file exists locally
        $exists = false;
        $size = 0;
        
        if (!empty($url)) {
            $upload_dir = wp_upload_dir();
            $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
            
            if (file_exists($local_path)) {
                $exists = true;
                $size = filesize($local_path);
            }
        }
        
        return [
            'path' => $url,
            'filename' => $filename,
            'type' => $type,
            'exists' => $exists,
            'size' => $size
        ];
    }
    
    /**
     * Scan directory and get file information
     */
    private function scanDirectory(string $path): array {
        $count = 0;
        $size = 0;
        
        if (!is_dir($path)) {
            return ['count' => $count, 'size' => $size];
        }
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            // Limit iterations to prevent timeout
            $max_files = 1000;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $count++;
                    $size += $file->getSize();
                    
                    if ($count >= $max_files) {
                        $count = $max_files . '+';
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            // Handle permission errors silently
        }
        
        return ['count' => $count, 'size' => $size];
    }
    
    /**
     * Get file type statistics
     */
    private function getFileTypeStats(): array {
        $upload_dir = wp_upload_dir();
        $bfp_dir = $upload_dir['basedir'] . '/bandfront-player-files/';
        
        if (!is_dir($bfp_dir)) {
            return [];
        }
        
        $stats = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($bfp_dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (!isset($stats[$ext])) {
                    $stats[$ext] = ['count' => 0, 'size' => 0];
                }
                $stats[$ext]['count']++;
                $stats[$ext]['size'] += $file->getSize();
            }
        }
        
        // Sort by count descending
        uasort($stats, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return $stats;
    }
    
    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }
    
    /**
     * Format configuration value for display
     */
    private function formatConfigValue($value): string {
        if (is_bool($value)) {
            return $value ? '<span class="bfa-bool-true">✓ True</span>' : '<span class="bfa-bool-false">✗ False</span>';
        } elseif (is_null($value)) {
            return '<span class="bfa-null">null</span>';
        } elseif (is_array($value)) {
            if (empty($value)) {
                return '<span class="bfa-empty">[]</span>';
            }
            return '<div class="bfa-array"><pre>' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</pre></div>';
        } elseif (is_numeric($value)) {
            return '<span class="bfa-number">' . esc_html($value) . '</span>';
        } elseif (empty($value)) {
            return '<span class="bfa-empty">empty</span>';
        } else {
            // Check if it's a path
            if (strpos($value, '/') !== false || strpos($value, '\\') !== false) {
                return '<span class="bfa-path">' . esc_html($value) . '</span>';
            }
            return '<span class="bfa-string">' . esc_html($value) . '</span>';
        }
    }
    
    /**
     * Get value type
     */
    private function getValueType($value): string {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_string($value)) {
            return 'string';
        } elseif (is_array($value)) {
            return 'array';
        } elseif (is_null($value)) {
            return 'null';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Build settings configuration array
     */
    private function buildSettingsConfig(array $allSettings): array {
        $config = [];
        
        // Define known settings and their types/defaults
        $knownSettings = [
            '_bfp_enable_player' => ['type' => 'boolean', 'default' => true],
            '_bfp_audio_engine' => ['type' => 'string', 'default' => 'mediaelement'],
            '_bfp_player_theme' => ['type' => 'string', 'default' => 'default'],
            '_bfp_play_demos' => ['type' => 'boolean', 'default' => false],
            '_bfp_demo_duration_percent' => ['type' => 'integer', 'default' => 30],
            '_bfp_ffmpeg' => ['type' => 'boolean', 'default' => false],
            '_bfp_ffmpeg_path' => ['type' => 'string', 'default' => '/usr/bin/ffmpeg'],
            '_bfp_player_layout' => ['type' => 'string', 'default' => 'list'],
            '_bfp_unified_player' => ['type' => 'boolean', 'default' => false],
            '_bfp_group_cart_control' => ['type' => 'boolean', 'default' => false],
            '_bfp_play_all' => ['type' => 'boolean', 'default' => false],
            '_bfp_loop' => ['type' => 'boolean', 'default' => false],
            '_bfp_player_volume' => ['type' => 'float', 'default' => 0.8],
            '_bfp_enable_vis' => ['type' => 'boolean', 'default' => false],
            '_bfp_dev_mode' => ['type' => 'boolean', 'default' => false],
            'enable_db_monitoring' => ['type' => 'boolean', 'default' => false],
        ];
        
        // Build config for all settings
        foreach ($allSettings as $key => $value) {
            if (isset($knownSettings[$key])) {
                $config[$key] = $knownSettings[$key];
            } else {
                // Auto-detect type for unknown settings
                $config[$key] = [
                    'type' => $this->getValueType($value),
                    'default' => null
                ];
            }
        }
        
        return $config;
    }
    
    /**
     * Render JavaScript for Database Monitor
     */
    private function renderDatabaseMonitorScripts(): void {
        ?>
        <script type="text/javascript">
        var bfpDbMonitor = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>',
            monitoring_enabled: <?php echo $this->config->getState('enable_db_monitoring', false) ? 'true' : 'false'; ?>,
            strings: {
                confirm_clean: '<?php _e('Are you sure you want to clean all test data?', 'bandfront-player'); ?>',
                generating: '<?php _e('Generating...', 'bandfront-player'); ?>',
                cleaning: '<?php _e('Cleaning...', 'bandfront-player'); ?>',
                no_activity: '<?php _e('No database activity detected', 'bandfront-player'); ?>',
                cleared: '<?php _e('Activity log cleared', 'bandfront-player'); ?>',
                paused: '<?php _e('Paused', 'bandfront-player'); ?>',
                live: '<?php _e('Live', 'bandfront-player'); ?>',
                resume: '<?php _e('Resume', 'bandfront-player'); ?>',
                pause: '<?php _e('Pause', 'bandfront-player'); ?>',
                clear: '<?php _e('Clear', 'bandfront-player'); ?>'
            }
        };
        </script>
        <?php
    }
    
    /**
     * Render CSS for Database Monitor
     */
    private function renderDatabaseMonitorStyles(): void {
        // Styles are now loaded from db-monitor.css
        // This method kept for backwards compatibility
    }
}