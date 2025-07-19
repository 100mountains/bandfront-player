<?php
namespace Bandfront\Db;

use Bandfront\Db\Test;
use Bandfront\Db\Clean;
use Bandfront\Utils\Debug;

// Set domain for Db
Debug::domain('db');

/**
 * Database Monitor class
 * 
 * This class handles the database monitoring functionality,
 * including AJAX actions for generating and cleaning test events.
 *
 * @package BandfrontPlayer
 * @since 2.0.0
 */
class Monitor {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add AJAX handlers for test actions - FIXED action names
        add_action('wp_ajax_bfp_generate_test_events', [$this, 'ajax_generate_test_events']);
        add_action('wp_ajax_bfp_clean_test_events', [$this, 'ajax_clean_test_events']);
        add_action('wp_ajax_bfp_get_db_activity', [$this, 'ajax_get_db_activity']);
        
        // Add maintenance AJAX handlers
        add_action('wp_ajax_bfp_clear_caches', [$this, 'ajax_clear_caches']);
        add_action('wp_ajax_bfp_optimize_tables', [$this, 'ajax_optimize_tables']);
        add_action('wp_ajax_bfp_export_settings', [$this, 'ajax_export_settings']);
        add_action('wp_ajax_bfp_scan_orphaned', [$this, 'ajax_scan_orphaned']);
        add_action('wp_ajax_bfp_clean_orphaned', [$this, 'ajax_clean_orphaned']);
    }
    
    /**
     * Render the database monitor UI
     */
    public function render() {
        // Get config instance
        $config = $GLOBALS['BandfrontAnalytics']->getConfig();
        $monitoring_enabled = $config->get('enable_db_monitoring', false);
        ?>
        <div class="wrap bfa-database-monitor" data-monitoring-enabled="<?php echo $monitoring_enabled ? 'true' : 'false'; ?>">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="bfa-monitor-grid">
                <!-- Activity Monitor -->
                <div class="bfa-monitor-section">
                    <h2><?php _e('Real-time Activity', 'bandfront-analytics'); ?></h2>
                    <div id="bfa-activity-monitor" class="bfa-activity-monitor">
                        <div class="bfa-activity-header">
                            <span class="bfa-activity-status">
                                <?php if ($monitoring_enabled): ?>
                                    <span class="bfa-traffic-status">
                                        ● Live
                                    </span>
                                <?php else: ?>
                                    <span class="bfa-traffic-status" style="color: #666;">
                                        ● Disabled
                                    </span>
                                <?php endif; ?>
                            </span>
                            <?php if ($monitoring_enabled): ?>
                                <button type="button" class="button button-small" id="bfa-pause-monitor">
                                    <?php _e('Pause', 'bandfront-analytics'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="bfa-activity-list">
                            <?php if (!$monitoring_enabled): ?>
                                <div class="bfa-no-traffic">
                                    <p><?php _e('Database monitoring is disabled. Enable it in the settings to see live activity.', 'bandfront-analytics'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Database Stats -->
                <div class="bfa-monitor-section">
                    <h2><?php _e('Database Statistics', 'bandfront-analytics'); ?></h2>
                    <div class="bfa-db-stats">
                        <?php $this->renderDatabaseStats(); ?>
                    </div>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="bfa-monitor-section">
                <h2><?php _e('Performance Metrics', 'bandfront-analytics'); ?></h2>
                <div class="bfa-performance-grid">
                    <?php $this->renderPerformanceMetrics(); ?>
                </div>
            </div>
            
            <!-- Test Actions Section -->
            <div class="bfa-test-actions" style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-radius: 5px;">
                <h2><?php _e('Test Actions', 'bandfront-analytics'); ?></h2>
                <p><?php _e('Use these tools to generate test data and verify the activity monitor is working correctly.', 'bandfront-analytics'); ?></p>
                
                <div class="button-group" style="margin-top: 15px;">
                    <button type="button" class="button button-primary" id="bfa-generate-test-events">
                        <span class="dashicons dashicons-randomize" style="vertical-align: middle;"></span>
                        <?php _e('Generate Test Events', 'bandfront-analytics'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" id="bfa-clean-test-events" style="margin-left: 10px;">
                        <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                        <?php _e('Clean Test Data', 'bandfront-analytics'); ?>
                    </button>
                    
                    <span class="spinner" style="float: none; margin-left: 10px;"></span>
                </div>
                
                <div id="bfa-test-results" style="margin-top: 15px; display: none;">
                    <div class="notice notice-info inline">
                        <p></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Fetch WooCommerce products
     */
    public function getWooCommerceProducts($limit = 10) {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $limit,
        ];
        return get_posts($args);
    }
    
    /**
     * Get product metadata for a specific product
     */
    public function getProductMetadata($product_id) {
        global $wpdb;
        
        // Get all BFP meta for this product
        $metadata = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value 
             FROM {$wpdb->postmeta} 
             WHERE post_id = %d 
             AND meta_key LIKE '_bfp_%'
             ORDER BY meta_key",
            $product_id
        ));
        
        return $metadata;
    }
    
    /**
     * Get all Config schema variables
     */
    public function getConfigSchema() {
        // This would typically read from the Config class's settingsConfig
        // For now, return a structured array of known config variables
        return [
            'global_settings' => [
                'enable_db_monitoring' => 'boolean',
                'dev_mode' => 'boolean',
                'enable_api_monitor' => 'boolean',
                'enable_maintenance' => 'boolean',
            ],
            'product_settings' => [
                '_bfp_tracks' => 'array',
                '_bfp_player_theme' => 'string',
                '_bfp_autoplay' => 'boolean',
                '_bfp_show_playlist' => 'boolean',
                '_bfp_show_artwork' => 'boolean',
                '_bfp_loop' => 'boolean',
                '_bfp_shuffle' => 'boolean',
            ]
        ];
    }

    /**
     * AJAX handler for generating test events
     */
    public function ajax_generate_test_events() {
        check_ajax_referer('bfp_db_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-analytics')]);
        }
        
        $result = Test::generateTestEvents(50); // Generate 50 test events
        
        if ($result['errors'] > 0) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Generated %d test events with %d errors', 'bandfront-analytics'),
                    $result['generated'],
                    $result['errors']
                )
            ]);
        } else {
            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully generated %d test events! <a href="#" onclick="location.reload()">Refresh page</a> to see them in the activity monitor.', 'bandfront-analytics'),
                    $result['generated']
                )
            ]);
        }
    }
    
    /**
     * AJAX handler for cleaning test events
     */
    public function ajax_clean_test_events() {
        check_ajax_referer('bfp_db_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-analytics')]);
        }
        
        $result = Clean::cleanTestEvents();
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully cleaned %d test events and %d test sessions! <a href="#" onclick="location.reload()">Refresh page</a> to update the display.', 'bandfront-analytics'),
                    $result['events_deleted'],
                    $result['sessions_cleaned']
                )
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to clean test data', 'bandfront-analytics')
            ]);
        }
    }
    
    /**
     * AJAX handler for getting database activity
     */
    public function ajax_get_db_activity() {
        check_ajax_referer('bfp_db_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-player')]);
        }
        
        global $wpdb;
        
        // Get recent postmeta changes for BFP
        $recent_activity = $wpdb->get_results("
            SELECT 
                pm.meta_id,
                pm.post_id,
                pm.meta_key,
                pm.meta_value,
                p.post_title,
                p.post_type,
                p.post_modified
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key LIKE '_bfp_%'
            ORDER BY pm.meta_id DESC
            LIMIT 50
        ");
        
        // Format for display
        $activity = [];
        foreach ($recent_activity as $item) {
            $type = 'update';
            if (strpos($item->meta_key, '_bfp_file_') !== false) {
                $type = 'file';
            } elseif (strpos($item->meta_key, '_bfp_demo_') !== false) {
                $type = 'demo';
            } elseif (strpos($item->meta_key, '_bfp_play_') !== false) {
                $type = 'play';
            }
            
            $activity[] = [
                'time' => human_time_diff(strtotime($item->post_modified), current_time('timestamp')) . ' ago',
                'type' => $type,
                'object' => sprintf('%s #%d', $item->post_type, $item->post_id),
                'value' => $item->post_title ?: 'Untitled',
                'user' => $item->meta_key,
            ];
        }
        
        wp_send_json_success(['activity' => $activity]);
    }
    
    /**
     * AJAX handler for clearing caches
     */
    public function ajax_clear_caches() {
        check_ajax_referer('bfp_db_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-player')]);
        }
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bfp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bfp_%'");
        
        // Update last cache clear time
        update_option('_bfp_last_cache_clear', time());
        
        // Clear object cache
        wp_cache_flush();
        
        wp_send_json_success(['message' => __('All caches cleared successfully!', 'bandfront-player')]);
    }
    
    /**
     * AJAX handler for optimizing tables
     */
    public function ajax_optimize_tables() {
        check_ajax_referer('bfp_db_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-player')]);
        }
        
        global $wpdb;
        
        // Optimize postmeta table
        $wpdb->query("OPTIMIZE TABLE {$wpdb->postmeta}");
        
        // Optimize options table
        $wpdb->query("OPTIMIZE TABLE {$wpdb->options}");
        
        wp_send_json_success(['message' => __('Database tables optimized successfully!', 'bandfront-player')]);
    }
    
    /**
     * AJAX handler for exporting settings
     */
    public function ajax_export_settings() {
        check_ajax_referer('bfp_db_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-player')]);
        }
        
        // Get all plugin settings
        $settings = get_option('bfp_global_settings', []);
        
        // Create export data
        $export_data = [
            'plugin' => 'bandfront-player',
            'version' => BFP_VERSION,
            'export_date' => current_time('mysql'),
            'settings' => $settings
        ];
        
        // Create temporary file
        $upload_dir = wp_upload_dir();
        $filename = 'bfp-settings-export-' . date('Y-m-d-His') . '.json';
        $filepath = $upload_dir['basedir'] . '/' . $filename;
        
        file_put_contents($filepath, json_encode($export_data, JSON_PRETTY_PRINT));
        
        // Return download URL
        wp_send_json_success([
            'message' => __('Settings exported successfully!', 'bandfront-player'),
            'download_url' => $upload_dir['baseurl'] . '/' . $filename
        ]);
    }
    
    /**
     * AJAX handler for scanning orphaned data
     */
    public function ajax_scan_orphaned() {
        check_ajax_referer('bfp_db_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-player')]);
        }
        
        global $wpdb;
        
        // Count orphaned meta entries
        $orphaned_meta = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_bfp_%' 
            AND post_id NOT IN (SELECT ID FROM {$wpdb->posts})
        ");
        
        // Count orphaned demo files
        $orphaned_files = 0;
        $upload_dir = wp_upload_dir();
        $demo_dir = $upload_dir['basedir'] . '/bandfront-player-files/';
        
        if (is_dir($demo_dir)) {
            $files = glob($demo_dir . '*.mp3');
            foreach ($files as $file) {
                // Check if file is referenced in any post meta
                $filename = basename($file);
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                     WHERE meta_value LIKE %s",
                    '%' . $wpdb->esc_like($filename) . '%'
                ));
                
                if (!$exists) {
                    $orphaned_files++;
                }
            }
        }
        
        wp_send_json_success([
            'orphaned_meta' => $orphaned_meta,
            'orphaned_files' => $orphaned_files
        ]);
    }
    
    /**
     * AJAX handler for cleaning orphaned data
     */
    public function ajax_clean_orphaned() {
        check_ajax_referer('bfp_db_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-player')]);
        }
        
        global $wpdb;
        
        // Delete orphaned meta
        $meta_deleted = $wpdb->query("
            DELETE FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_bfp_%' 
            AND post_id NOT IN (SELECT ID FROM {$wpdb->posts})
        ");
        
        // Delete orphaned files
        $files_deleted = 0;
        $upload_dir = wp_upload_dir();
        $demo_dir = $upload_dir['basedir'] . '/bandfront-player-files/';
        
        if (is_dir($demo_dir)) {
            $files = glob($demo_dir . '*.mp3');
            foreach ($files as $file) {
                $filename = basename($file);
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                     WHERE meta_value LIKE %s",
                    '%' . $wpdb->esc_like($filename) . '%'
                ));
                
                if (!$exists) {
                    if (unlink($file)) {
                        $files_deleted++;
                    }
                }
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(
                __('Cleaned up %d orphaned metadata entries and %d orphaned files.', 'bandfront-player'),
                $meta_deleted,
                $files_deleted
            )
        ]);
    }
    
    /**
     * Render database statistics
     */
    private function renderDatabaseStats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bfa_events';
        
        // Get table stats
        $total_events = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $today_events = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE DATE(timestamp) = %s",
            current_time('Y-m-d')
        ));
        
        // Get table size
        $table_size = $wpdb->get_var($wpdb->prepare(
            "SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) 
             FROM information_schema.TABLES 
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_name
        ));
        
        ?>
        <div class="bfa-stats-grid">
            <div class="bfa-stat-item">
                <span class="bfa-stat-label"><?php _e('Total Events', 'bandfront-analytics'); ?></span>
                <span class="bfa-stat-value"><?php echo number_format($total_events); ?></span>
            </div>
            <div class="bfa-stat-item">
                <span class="bfa-stat-label"><?php _e('Today\'s Events', 'bandfront-analytics'); ?></span>
                <span class="bfa-stat-value"><?php echo number_format($today_events); ?></span>
            </div>
            <div class="bfa-stat-item">
                <span class="bfa-stat-label"><?php _e('Table Size', 'bandfront-analytics'); ?></span>
                <span class="bfa-stat-value"><?php echo $table_size; ?> MB</span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render performance metrics
     */
    private function renderPerformanceMetrics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bfa_events';
        
        // Get average query time (simplified)
        $start = microtime(true);
        $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $query_time = round((microtime(true) - $start) * 1000, 2);
        
        // Get event rate
        $events_last_hour = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        $events_per_minute = round($events_last_hour / 60, 2);
        
        ?>
        <div class="bfa-metric-item">
            <span class="bfa-metric-label"><?php _e('Avg Query Time', 'bandfront-analytics'); ?></span>
            <span class="bfa-metric-value"><?php echo $query_time; ?> ms</span>
        </div>
        <div class="bfa-metric-item">
            <span class="bfa-metric-label"><?php _e('Events/Minute', 'bandfront-analytics'); ?></span>
            <span class="bfa-metric-value"><?php echo $events_per_minute; ?></span>
        </div>
        <?php
    }
}