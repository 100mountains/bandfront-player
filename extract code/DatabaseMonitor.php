<?php
namespace bfa\Admin;

use bfa\Utils\DbTest;
use bfa\Utils\DbClean;

class DatabaseMonitor {
    
    public function __construct() {
        // Add AJAX handlers for test actions
        add_action('wp_ajax_bfa_generate_test_events', [$this, 'ajax_generate_test_events']);
        add_action('wp_ajax_bfa_clean_test_events', [$this, 'ajax_clean_test_events']);
    }
    
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
     * AJAX handler for generating test events
     */
    public function ajax_generate_test_events() {
        check_ajax_referer('bfa_test_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-analytics')]);
        }
        
        $result = DbTest::generateTestEvents(50); // Generate 50 test events
        
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
        check_ajax_referer('bfa_test_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'bandfront-analytics')]);
        }
        
        $result = DbClean::cleanTestEvents();
        
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