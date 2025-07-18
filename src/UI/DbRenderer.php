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
        // Get the monitoring setting - this one doesn't use _bfp_ prefix
        $monitoring_enabled = $this->config->getState('enable_db_monitoring', false);
        
        // Convert to boolean for consistency
        $monitoring_enabled = (bool) $monitoring_enabled;
        
        ?>
        
        <!-- Simple checkbox like all the others -->
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="enable_db_monitoring"><?php _e('Enable Database Monitoring', 'bandfront-player'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           id="enable_db_monitoring" 
                           name="enable_db_monitoring" 
                           value="1" 
                           <?php checked($monitoring_enabled, true); ?> />
                    <p class="description"><?php _e('Turn on real-time database activity monitoring', 'bandfront-player'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php if (!$monitoring_enabled): ?>
            <div class="notice notice-info inline">
                <p><?php _e('Database monitoring is currently disabled. Enable it above and save settings to see real-time activity.', 'bandfront-player'); ?></p>
            </div>
        <?php else: ?>
        
        <!-- Test Action Buttons -->
        <div class="bfa-db-test-actions" style="margin-bottom: 20px; padding: 15px; background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <button type="button" class="button button-primary" id="bfa-test-events">
                    <span class="dashicons dashicons-randomize" style="margin-top: 3px;"></span>
                    <?php _e('Generate Test Events', 'bandfront-player'); ?>
                </button>
                
                <button type="button" class="button" id="bfa-clean-events">
                    <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                    <?php _e('Clean Test Data', 'bandfront-player'); ?>
                </button>
                
                <span class="spinner" style="float: none; visibility: hidden;"></span>
                <span class="bfa-test-message" style="margin-left: 10px; color: #3c434a;"></span>
            </div>
        </div>
        
        <!-- Database Activity Monitor -->
        <div class="bfa-api-monitor">
            <h3><?php _e('Database Activity Monitor', 'bandfront-player'); ?></h3>
            <div class="bfa-traffic-box" id="bfa-db-activity">
                <div class="bfa-traffic-header">
                    <span class="bfa-traffic-status">● <?php _e('Live', 'bandfront-player'); ?></span>
                    <button type="button" class="button button-small" id="bfa-clear-db-activity">
                        <?php _e('Clear', 'bandfront-player'); ?>
                    </button>
                </div>
                <div class="bfa-traffic-log" id="bfa-db-activity-log">
                    <div class="bfa-traffic-empty"><?php _e('Waiting for database activity...', 'bandfront-player'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Database Stats and Performance Grid -->
        <div class="bfa-monitor-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            <!-- Database Stats -->
            <div class="bfa-monitor-section">
                <h3><?php _e('Database Statistics', 'bandfront-player'); ?></h3>
                <div class="bfa-db-stats">
                    <?php $this->renderDatabaseStats(); ?>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="bfa-monitor-section">
                <h3><?php _e('Performance Metrics', 'bandfront-player'); ?></h3>
                <div class="bfa-performance-grid">
                    <?php $this->renderPerformanceMetrics(); ?>
                </div>
            </div>
        </div>
        
        <!-- Database Schema Section -->
        <div class="bfa-api-endpoints" style="margin-top: 30px;">
            <h3><?php esc_html_e('Database Schema', 'bandfront-player'); ?></h3>
            <?php $this->renderDatabaseSchema(); ?>
        </div>
        
        <?php endif; // monitoring_enabled ?>
        
        <?php $this->renderDatabaseMonitorScripts(); ?>
        <?php $this->renderDatabaseMonitorStyles(); ?>
        
        <?php
        // Debug output for troubleshooting
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<!-- BFP Debug -->';
            echo '<!-- monitoring_enabled: ' . var_export($monitoring_enabled, true) . ' -->';
            echo '<!-- raw config value: ' . var_export($this->config->getState('enable_db_monitoring', 'NOT_SET'), true) . ' -->';
            echo '<!-- all global attrs: ' . var_export($this->config->getAllGlobalAttrs(), true) . ' -->';
            echo '<!-- End BFP Debug -->';
        }
    }
    
    /**
     * Render database statistics
     */
    private function renderDatabaseStats(): void {
        global $wpdb;
        
        // Get postmeta stats for player data
        $player_meta_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_bfp_%'
        ");
        
        // Get today's activity (simplified since we don't have events table)
        $today_updates = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_bfp_%' 
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_modified >= CURDATE()
            )
        ");
        
        // Get table sizes
        $table_info = $wpdb->get_row("
            SELECT 
                ROUND((data_length + index_length) / 1024 / 1024, 2) AS total_size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = '" . DB_NAME . "'
            AND table_name = '{$wpdb->postmeta}'
        ");
        
        ?>
        <div class="bfa-stats-grid">
            <div class="bfa-stat-item">
                <span class="bfa-stat-label"><?php _e('Player Meta Entries', 'bandfront-player'); ?></span>
                <span class="bfa-stat-value"><?php echo number_format($player_meta_count); ?></span>
            </div>
            <div class="bfa-stat-item">
                <span class="bfa-stat-label"><?php _e('Today\'s Updates', 'bandfront-player'); ?></span>
                <span class="bfa-stat-value"><?php echo number_format($today_updates); ?></span>
            </div>
            <div class="bfa-stat-item">
                <span class="bfa-stat-label"><?php _e('Postmeta Table Size', 'bandfront-player'); ?></span>
                <span class="bfa-stat-value"><?php echo $table_info->total_size_mb ?? '0'; ?> MB</span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render performance metrics
     */
    private function renderPerformanceMetrics(): void {
        global $wpdb;
        
        // Test query performance
        $start = microtime(true);
        $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE '_bfp_%'");
        $query_time = round((microtime(true) - $start) * 1000, 2);
        
        // Get cache stats
        $transient_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_bfp_%'
        ");
        
        ?>
        <div class="bfa-metric-item">
            <span class="bfa-metric-label"><?php _e('Avg Query Time', 'bandfront-player'); ?></span>
            <span class="bfa-metric-value"><?php echo $query_time; ?> ms</span>
        </div>
        <div class="bfa-metric-item">
            <span class="bfa-metric-label"><?php _e('Active Transients', 'bandfront-player'); ?></span>
            <span class="bfa-metric-value"><?php echo number_format($transient_count); ?></span>
        </div>
        <?php
    }
    
    /**
     * Render Database Monitor specific scripts
     */
    private function renderDatabaseMonitorScripts(): void {
        $monitoring_enabled = $this->config->getState('enable_db_monitoring', false);
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Test Events Handler
            $('#bfa-test-events').on('click', function() {
                var $button = $(this);
                var $spinner = $('.bfa-db-test-actions .spinner');
                var $message = $('.bfa-test-message');
                
                $button.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $message.text('');
                
                $.post(ajaxurl, {
                    action: 'bfp_generate_test_events',
                    nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>'
                }, function(response) {
                    $button.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                    
                    if (response.success) {
                        $message.html('<span style="color: #46b450;">' + response.data.message + '</span>');
                        // Trigger refresh of activity monitor
                        if (typeof window.bfpDbMonitor !== 'undefined') {
                            window.bfpDbMonitor.loadActivity();
                        }
                    } else {
                        $message.html('<span style="color: #dc3232;">' + (response.data.message || 'Error generating test events') + '</span>');
                    }
                    
                    // Clear message after 5 seconds
                    setTimeout(function() {
                        $message.fadeOut(function() {
                            $(this).text('').show();
                        });
                    }, 5000);
                });
            });
            
            // Clean Events Handler
            $('#bfa-clean-events').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to delete all test data? This cannot be undone.', 'bandfront-player'); ?>')) {
                    return;
                }
                
                var $button = $(this);
                var $spinner = $('.bfa-db-test-actions .spinner');
                var $message = $('.bfa-test-message');
                
                $button.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $message.text('');
                
                $.post(ajaxurl, {
                    action: 'bfp_clean_test_events',
                    nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>'
                }, function(response) {
                    $button.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                    
                    if (response.success) {
                        $message.html('<span style="color: #46b450;">' + response.data.message + '</span>');
                        // Trigger refresh
                        if (typeof window.bfpDbMonitor !== 'undefined') {
                            window.bfpDbMonitor.loadActivity();
                        }
                    } else {
                        $message.html('<span style="color: #dc3232;">' + (response.data.message || 'Error cleaning test data') + '</span>');
                    }
                    
                    setTimeout(function() {
                        $message.fadeOut(function() {
                            $(this).text('').show();
                        });
                    }, 5000);
                });
            });
            
            <?php if ($monitoring_enabled): ?>
            // Database Activity Monitor - only init if monitoring is enabled
            window.bfpDbMonitor = {
                interval: null,
                paused: false,
                
                init: function() {
                    var self = this;
                    
                    // Load initial data
                    this.loadActivity();
                    
                    // Set up auto-refresh
                    this.interval = setInterval(function() {
                        if (!self.paused) {
                            self.loadActivity();
                        }
                    }, 5000);
                    
                    // Clear button
                    $('#bfa-clear-db-activity').on('click', function() {
                        $('#bfa-db-activity-log').html('<div class="bfa-traffic-empty"><?php _e('Cleared. Waiting for new activity...', 'bandfront-player'); ?></div>');
                    });
                },
                
                loadActivity: function() {
                    $.post(ajaxurl, {
                        action: 'bfp_get_db_activity',
                        nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>'
                    }, function(response) {
                        if (response.success && response.data.activity) {
                            var $log = $('#bfa-db-activity-log');
                            $log.empty();
                            
                            if (response.data.activity.length === 0) {
                                $log.html('<div class="bfa-traffic-empty"><?php _e('No recent database activity...', 'bandfront-player'); ?></div>');
                            } else {
                                response.data.activity.forEach(function(event) {
                                    var $entry = $('<div class="bfa-traffic-entry"></div>');
                                    $entry.append('<span class="bfa-traffic-time">' + event.time + '</span>');
                                    $entry.append('<span class="bfa-traffic-method bfa-method-' + event.type + '">' + event.type.toUpperCase() + '</span>');
                                    $entry.append('<span class="bfa-traffic-route">' + event.object + '</span>');
                                    if (event.value) {
                                        $entry.append('<span class="bfa-traffic-value">= ' + event.value + '</span>');
                                    }
                                    $entry.append('<span class="bfa-traffic-user">' + event.user + '</span>');
                                    $log.append($entry);
                                });
                            }
                        }
                    });
                }
            };
            
            // Initialize if on the database monitor section
            if ($('#bfa-db-activity-log').length) {
                window.bfpDbMonitor.init();
            }
            <?php endif; ?>
        });
        </script>
        <?php
    }
    
    /**
     * Render Database Monitor CSS
     */
    private function renderDatabaseMonitorStyles(): void {
        ?>
        <style>
            /* Database Monitor Styles */
            .bfa-monitor-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-top: 30px;
            }
            
            .bfa-monitor-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
            }
            
            .bfa-monitor-section h3 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #1d2327;
            }
            
            /* Stats Grid */
            .bfa-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .bfa-stat-item {
                text-align: center;
                padding: 15px;
                background: #f6f7f7;
                border-radius: 4px;
            }
            
            .bfa-stat-label {
                display: block;
                font-size: 12px;
                color: #666;
                margin-bottom: 5px;
            }
            
            .bfa-stat-value {
                display: block;
                font-size: 24px;
                font-weight: 600;
                color: #2271b1;
            }
            
            /* Performance Metrics */
            .bfa-performance-grid {
                display: grid;
                gap: 10px;
            }
            
            .bfa-metric-item {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                background: #f6f7f7;
                border-radius: 4px;
            }
            
            .bfa-metric-label {
                color: #666;
            }
            
            .bfa-metric-value {
                font-weight: 600;
                color: #2271b1;
            }
            
            /* Activity Monitor */
            .bfa-traffic-box {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                max-height: 400px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            
            .bfa-traffic-header {
                padding: 10px 15px;
                background: #f6f7f7;
                border-bottom: 1px solid #c3c4c7;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .bfa-traffic-status {
                color: #46b450;
                font-weight: 600;
            }
            
            .bfa-traffic-log {
                flex: 1;
                overflow-y: auto;
                padding: 10px;
            }
            
            .bfa-traffic-empty {
                text-align: center;
                color: #666;
                padding: 40px 20px;
            }
            
            .bfa-traffic-entry {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px 5px;
                border-bottom: 1px solid #f0f0f0;
                font-size: 13px;
            }
            
            .bfa-traffic-entry:last-child {
                border-bottom: none;
            }
            
            .bfa-traffic-time {
                color: #666;
                min-width: 80px;
            }
            
            .bfa-traffic-method {
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                min-width: 60px;
                text-align: center;
            }
            
            .bfa-method-get, .bfa-method-view, .bfa-method-play { background: #e8f5e9; color: #2e7d32; }
            .bfa-method-post, .bfa-method-create { background: #e3f2fd; color: #1565c0; }
            .bfa-method-put, .bfa-method-update { background: #fff3e0; color: #e65100; }
            .bfa-method-delete { background: #ffebee; color: #c62828; }
            .bfa-method-file { background: #f3e5f5; color: #6a1b9a; }
            .bfa-method-demo { background: #e0f2f1; color: #00695c; }
            
            .bfa-traffic-route {
                flex: 1;
                color: #2271b1;
                font-family: monospace;
            }
            
            .bfa-traffic-value {
                color: #666;
                font-style: italic;
            }
            
            .bfa-traffic-user {
                color: #666;
                font-size: 12px;
            }
            
            /* Database Schema Styles */
            .bfa-endpoints-list {
                margin-top: 20px;
            }
            
            .bfa-endpoint-item {
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 10px;
            }
            
            .bfa-endpoint-route {
                font-family: monospace;
                color: #333;
            }
            
            .bfa-endpoint-route code {
                background: #eef;
                padding: 2px 6px;
                border-radius: 3px;
            }
            
            .bfa-endpoint-item h4 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #1d2327;
            }
            
            .bfa-api-endpoints .description {
                font-size: 13px;
                color: #666;
                margin-bottom: 10px;
            }
        </style>
        <?php
    }
    
    /**
     * Render database schema information
     */
    private function renderDatabaseSchema(): void {
        global $wpdb;
        
        // Get postmeta information for BFP
        $meta_keys = $wpdb->get_col("
            SELECT DISTINCT meta_key 
            FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_bfp_%'
            ORDER BY meta_key
        ");
        
        ?>
        <div class="bfa-endpoints-list">
            <p class="description">
                <?php 
                printf(
                    esc_html__('Found %d unique BFP meta keys in database', 'bandfront-player'), 
                    count($meta_keys)
                ); 
                ?>
            </p>
            
            <div class="bfa-endpoint-item bfa-database-table">
                <div class="bfa-endpoint-route">
                    <code><?php echo esc_html($wpdb->postmeta); ?></code>
                    <span class="description"><?php esc_html_e('WordPress Post Metadata Table', 'bandfront-player'); ?></span>
                </div>
                
                <?php if (!empty($meta_keys)): ?>
                    <div class="bfa-table-fields">
                        <h4><?php esc_html_e('BFP Meta Keys', 'bandfront-player'); ?></h4>
                        <div class="bfa-meta-keys-grid">
                            <?php foreach ($meta_keys as $key): ?>
                                <?php
                                $count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                                    $key
                                ));
                                ?>
                                <div class="bfa-meta-key-item">
                                    <code><?php echo esc_html($key); ?></code>
                                    <span class="bfa-meta-count">(<?php echo number_format($count); ?>)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            .bfa-meta-keys-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 10px;
                margin-top: 10px;
            }
            .bfa-meta-key-item {
                display: flex;
                justify-content: space-between;
                padding: 5px 10px;
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
            }
            .bfa-meta-count {
                color: #666;
                font-size: 12px;
            }
        </style>
        <?php
    }
}