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
        
        
        <!-- Sub-tabs for Database Monitor -->
        <div class="bfp-db-subtabs">
            <h3 class="nav-tab-wrapper">
                <a href="#" class="nav-tab nav-tab-active" data-subtab="monitoring"><?php _e('Monitoring', 'bandfront-player'); ?></a>
                <a href="#" class="nav-tab" data-subtab="products"><?php _e('Products', 'bandfront-player'); ?></a>
                <a href="#" class="nav-tab" data-subtab="schema"><?php _e('Schema', 'bandfront-player'); ?></a>
            </h3>
        </div>

        <!-- Monitoring Tab Content -->
        <div class="bfp-subtab-content" id="monitoring-subtab" style="display: block;">
            <?php if (!$monitoring_enabled): ?>
                <div class="notice notice-info inline">
                    <p><?php _e('Database monitoring is currently disabled. Enable it above and save settings to see real-time activity.', 'bandfront-player'); ?></p>
                </div>
            <?php else: ?>
                <?php $this->renderMonitoringContent(); ?>
            <?php endif; ?>
        </div>

        <!-- Products Tab Content -->
        <div class="bfp-subtab-content" id="products-subtab" style="display: none;">
            <?php $this->renderProductsContent(); ?>
        </div>

        <!-- Schema Tab Content -->
        <div class="bfp-subtab-content" id="schema-subtab" style="display: none;">
            <?php $this->renderSchemaContent(); ?>
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
            
            // Sub-tab navigation
            $('.bfp-db-subtabs .nav-tab').on('click', function(e) {
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                e.preventDefault();
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                var targetTab = $(this).data('subtab');
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                // Remove active class from all tabs
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                $('.bfp-db-subtabs .nav-tab').removeClass('nav-tab-active');
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                // Add active class to clicked tab
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                $(this).addClass('nav-tab-active');
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                // Hide all tab content
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                $('.bfp-subtab-content').hide();
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                // Show target tab content
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
                $('#' + targetTab + '-subtab').show();
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
            });
            
            // Collapsible sections in Schema tab
            $(document).on('click', '.bfa-collapsible', function() {
                var $header = $(this);
                var targetId = $header.data('target');
                var $target = $('#' + targetId);
                
                $target.slideToggle(300);
                $header.toggleClass('expanded');
            });
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
            
            /* Schema Tab Styles */
            .bfa-schema-container {
                padding: 20px;
            }
            
            .bfa-schema-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .bfa-stat-box {
                background: #f6f7f7;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
            }
            
            .bfa-stat-number {
                display: block;
                font-size: 32px;
                font-weight: 600;
                color: #2271b1;
                margin-bottom: 5px;
            }
            
            .bfa-stat-label {
                color: #666;
                font-size: 14px;
            }
            
            .bfa-schema-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                margin-bottom: 20px;
                overflow: hidden;
            }
            
            .bfa-section-header {
                background: #f6f7f7;
                border-bottom: 1px solid #c3c4c7;
                padding: 15px 20px;
                margin: 0;
                font-size: 16px;
                cursor: default;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .bfa-section-header .bfa-count {
                color: #666;
                font-size: 14px;
                font-weight: normal;
            }
            
            .bfa-section-header.bfa-collapsible {
                cursor: pointer;
                user-select: none;
            }
            
            .bfa-section-header.bfa-collapsible:hover {
                background: #eaeaea;
            }
            
            .bfa-toggle {
                margin-left: auto;
                transition: transform 0.3s ease;
            }
            
            .bfa-section-header.expanded .bfa-toggle {
                transform: rotate(180deg);
            }
            
            .bfa-schema-table {
                padding: 20px;
            }
            
            .bfa-config-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .bfa-config-table th {
                text-align: left;
                padding: 10px;
                background: #f6f7f7;
                border-bottom: 2px solid #c3c4c7;
                font-weight: 600;
            }
            
            .bfa-config-table td {
                padding: 10px;
                border-bottom: 1px solid #f0f0f1;
                vertical-align: top;
            }
            
            .bfa-config-table tr:hover {
                background: #f9f9f9;
            }
            
            .bfa-key code {
                background: #eef;
                padding: 3px 8px;
                border-radius: 3px;
                font-family: monospace;
                font-size: 13px;
            }
            
            .bfa-label {
                display: block;
                font-size: 12px;
                color: #666;
                margin-top: 4px;
            }
            
            .bfa-type-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            
            .bfa-type-boolean { background: #e3f2fd; color: #1565c0; }
            .bfa-type-string { background: #e8f5e9; color: #2e7d32; }
            .bfa-type-integer { background: #fff3e0; color: #e65100; }
            .bfa-type-array { background: #f3e5f5; color: #6a1b9a; }
            .bfa-type-unknown { background: #efebe9; color: #5d4037; }
            
            .bfa-bool-true { color: #46b450; font-weight: 600; }
            .bfa-bool-false { color: #dc3232; font-weight: 600; }
            .bfa-null { color: #999; font-style: italic; }
            .bfa-empty { color: #999; font-style: italic; }
            .bfa-number { color: #e65100; font-weight: 600; }
            .bfa-string { color: #2e7d32; }
            .bfa-path { 
                background: #f0f0f1; 
                padding: 3px 6px; 
                border-radius: 3px;
                font-family: monospace;
                font-size: 12px;
            }
            
            .bfa-array {
                display: block;
                background: #f6f7f7;
                padding: 10px;
                border-radius: 3px;
                font-size: 12px;
                white-space: pre-wrap;
                overflow-x: auto;
                max-width: 400px;
                font-family: monospace;
            }
            
            .bfa-schema-raw {
                padding: 20px;
            }
            
            .bfa-code-block {
                background: #23282d;
                color: #87c540;
                padding: 20px;
                border-radius: 4px;
                overflow-x: auto;
                font-family: monospace;
                font-size: 13px;
                line-height: 1.6;
                max-height: 600px;
                overflow-y: auto;
            }
            
            .bfa-no-settings {
                color: #666;
                font-style: italic;
                padding: 20px;
                text-align: center;
            }
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
    
    /**
     * Render monitoring content
     */
    private function renderMonitoringContent(): void {
        // Render the current monitoring UI components
        ?>
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
        <?php
    }
    
    /**
     * Render products content
     */
    private function renderProductsContent(): void {
        $products = $this->monitor->getWooCommerceProducts(10);

        if (empty($products)) {
            echo '<p>' . __('No products found.', 'bandfront-player') . '</p>';
            return;
        }

        echo '<ul class="product-list">';
        foreach ($products as $product) {
            $product_id = $product->ID;
            $product_title = get_the_title($product_id);
            $product_price = get_post_meta($product_id, '_price', true);

            echo '<li>';
            echo '<a href="#" class="product-link" data-product-id="' . esc_attr($product_id) . '">';
            echo esc_html($product_title) . ' - ' . wc_price($product_price);
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Render schema content
     */
    /**
     * Render schema content with comprehensive Config dump
     */
    private function renderSchemaContent(): void {
        // Get all configuration data
        $allSettings = $this->config->getAllSettings();
        $generalSettings = $this->config->getGeneralSettings();
        $globalAttrs = $this->config->getAllGlobalAttrs();
        
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
                // Group settings by prefix for better organization
                $grouped = [
                    'General' => [],
                    'Player' => [],
                    'Demo & Security' => [],
                    'Audio Engine' => [],
                    'Cloud Storage' => [],
                    'Developer' => [],
                    'Other' => []
                ];
                
                foreach ($generalSettings as $key => $config) {
                    $value = $this->config->getState($key);
                    $item = [
                        'key' => $key,
                        'value' => $value,
                        'config' => $config
                    ];
                    
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
                
                // Display each group
                foreach ($grouped as $groupName => $items) {
                    if (empty($items)) continue;
                    
                    $icon = '';
                    switch($groupName) {
                        case 'General': $icon = 'dashicons-admin-generic'; break;
                        case 'Player': $icon = 'dashicons-format-audio'; break;
                        case 'Demo & Security': $icon = 'dashicons-shield'; break;
                        case 'Audio Engine': $icon = 'dashicons-controls-volumeon'; break;
                        case 'Cloud Storage': $icon = 'dashicons-cloud'; break;
                        case 'Developer': $icon = 'dashicons-code-standards'; break;
                        default: $icon = 'dashicons-admin-settings';
                    }
                    ?>
                    <div class="bfa-schema-section">
                        <h4 class="bfa-section-header">
                            <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                            <?php echo esc_html($groupName); ?>
                            <span class="bfa-count">(<?php echo count($items); ?>)</span>
                        </h4>
                        <div class="bfa-schema-table">
                            <table class="bfa-config-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Setting', 'bandfront-player'); ?></th>
                                        <th><?php _e('Current Value', 'bandfront-player'); ?></th>
                                        <th><?php _e('Type', 'bandfront-player'); ?></th>
                                        <th><?php _e('Default', 'bandfront-player'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): 
                                        $displayValue = $this->formatConfigValue($item['value']);
                                        $type = $item['config']['type'] ?? 'unknown';
                                        $default = $item['config']['default'] ?? 'N/A';
                                    ?>
                                    <tr>
                                        <td class="bfa-key">
                                            <code><?php echo esc_html($item['key']); ?></code>
                                            <?php if (!empty($item['config']['label'])): ?>
                                                <span class="bfa-label"><?php echo esc_html($item['config']['label']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="bfa-value"><?php echo $displayValue; ?></td>
                                        <td class="bfa-type">
                                            <span class="bfa-type-badge bfa-type-<?php echo esc_attr($type); ?>">
                                                <?php echo esc_html($type); ?>
                                            </span>
                                        </td>
                                        <td class="bfa-default">
                                            <?php echo $this->formatConfigValue($default); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- Raw dump -->
            <div class="bfa-schema-section">
                <h4 class="bfa-section-header bfa-collapsible" data-target="raw-values">
                    <span class="dashicons dashicons-editor-code"></span>
                    <?php _e('Raw Configuration Dump', 'bandfront-player'); ?>
                    <span class="bfa-toggle dashicons dashicons-arrow-down-alt2"></span>
                </h4>
                <div id="raw-values" class="bfa-schema-raw" style="display: none;">
                    <pre class="bfa-code-block"><?php 
                        echo esc_html(json_encode($allSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    ?></pre>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Format configuration value for display
     */
    private function formatConfigValue($value): string {
        if (is_bool($value)) {
            return $value ? '<span class="bfa-bool-true">✓ true</span>' : '<span class="bfa-bool-false">✗ false</span>';
        } elseif (is_array($value)) {
            if (empty($value)) {
                return '<span class="bfa-empty">[ empty ]</span>';
            }
            return '<code class="bfa-array">' . esc_html(json_encode($value, JSON_PRETTY_PRINT)) . '</code>';
        } elseif (is_null($value)) {
            return '<span class="bfa-null">null</span>';
        } elseif ($value === '') {
            return '<span class="bfa-empty">""</span>';
        } elseif (is_numeric($value)) {
            return '<span class="bfa-number">' . esc_html($value) . '</span>';
        } else {
            // Check if it's a path or URL
            if (strpos($value, '/') !== false || strpos($value, '\\') !== false) {
                return '<code class="bfa-path">' . esc_html($value) . '</code>';
            }
            return '<span class="bfa-string">"' . esc_html($value) . '"</span>';
        }
    }
}
