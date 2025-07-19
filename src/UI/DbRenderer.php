<?php
namespace Bandfront\UI;

use Bandfront\Core\Config;
use Bandfront\Db\Monitor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Database Monitor UI
 */
class DbRenderer {
    
    private Config $config;
    private Monitor $monitor;
    
    public function __construct(Config $config, Monitor $monitor) {
        $this->config = $config;
        $this->monitor = $monitor;
    }
    
    /**
     * Render the complete Database Monitor section
     * This is used in the dev-tools.php template
     */
    public function renderDatabaseMonitorSection(): void {
        // Include template functions
        require_once dirname(dirname(__DIR__)) . '/templates/db-templates.php';
        
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
            
            <?php bfp_render_filesystem_section(); ?>
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
        
        // Get and render BFP settings
        $bfp_settings = $this->getProductBfpSettings($product_id);
        bfp_render_bfp_settings_table($bfp_settings);
        
        // Get and render audio files
        $audio_files = $this->getProductAudioFiles($product_id);
        bfp_render_audio_files_table($audio_files);
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
        $upload_dir = wp_upload_dir();
        $plugin_dir = plugin_dir_path(dirname(dirname(__DIR__)) . '/bandfront-player.php');
        
        $directories = [
            'Plugin Root' => $plugin_dir,
            'Plugin Assets' => $plugin_dir . 'assets/',
            'Upload Directory' => $upload_dir['basedir'] . '/',
            'BFP Files' => $upload_dir['basedir'] . '/bandfront-player-files/',
            'BFP Demos' => $upload_dir['basedir'] . '/bandfront-player-files/demos/',
            'BFP Previews' => $upload_dir['basedir'] . '/bandfront-player-files/previews/',
            'BFP Temp' => $upload_dir['basedir'] . '/bandfront-player-files/temp/',
        ];
        
        $result = [];
        foreach ($directories as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $files = $exists ? $this->scanDirectory($path) : ['count' => 0, 'size' => 0];
            
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
            } elseif (strpos($key, '_bfp_secure') === 0 || strpos($key, '_bfp_file_percent') === 0 || 
                      strpos($key, '_bfp_message') === 0) {
                $grouped['Demo & Security'][] = $item;
            } elseif (strpos($key, '_bfp_audio') === 0 || strpos($key, '_bfp_ffmpeg') === 0 ||
                      strpos($key, '_bfp_enable_vis') === 0) {
                $grouped['Audio Engine'][] = $item;
            } elseif (strpos($key, '_bfp_cloud') === 0 || strpos($key, '_bfp_own_demos') === 0 ||
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
        $meta_keys = ['_bfp_file_url', '_bfp_file_urls', '_bfp_demo_file_url', '_bfp_demo_file_urls'];
        
        foreach ($meta_keys as $key) {
            $value = get_post_meta($product_id, $key, true);
            if ($value) {
                if (is_array($value)) {
                    foreach ($value as $file_url) {
                        $file_info = $this->analyzeFileUrl($file_url, $key);
                        $file_info['formatted_size'] = $this->formatFileSize($file_info['size']);
                        $files[] = $file_info;
                    }
                } else {
                    $file_info = $this->analyzeFileUrl($value, $key);
                    $file_info['formatted_size'] = $this->formatFileSize($file_info['size']);
                    $files[] = $file_info;
                }
            }
        }
        
        return $files;
    }
}