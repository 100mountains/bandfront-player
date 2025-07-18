<?php
namespace Bandfront\UI;

use Bandfront\Core\Config;
use Bandfront\Db\Monitor;  // Changed from Admin\DbMonitor\DbMonitor

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Database Monitor UI
 */
class DbRenderer {
    
    private Config $config;
    private Monitor $monitor;  // Changed from DbMonitor
    
    public function __construct(Config $config, Monitor $monitor) {
        $this->config = $config;
        $this->monitor = $monitor;
    }
    
    /**
     * Render the Database Monitor tab content
     */
    public function renderDatabaseMonitorTab(): void {
        $config = $this->plugin->getConfig();
        $database = $this->plugin->getDatabase();
        ?>
        <h2><?php esc_html_e('Database Monitor', 'bandfront-analytics'); ?></h2>
        
        <!-- Test Action Buttons -->
        <div class="bfa-db-test-actions" style="margin-bottom: 20px; padding: 15px; background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <button type="button" class="button button-primary" id="bfa-test-events">
                    <span class="dashicons dashicons-randomize" style="margin-top: 3px;"></span>
                    <?php _e('Generate Test Events', 'bandfront-analytics'); ?>
                </button>
                
                <button type="button" class="button" id="bfa-clean-events">
                    <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                    <?php _e('Clean Test Data', 'bandfront-analytics'); ?>
                </button>
                
                <span class="spinner" style="float: none; visibility: hidden;"></span>
                <span class="bfa-test-message" style="margin-left: 10px; color: #3c434a;"></span>
            </div>
        </div>
        
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php _e('Live Monitoring', 'bandfront-analytics'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_db_monitoring" value="1" <?php checked($config->get('enable_db_monitoring', true)); ?>>
                        <?php _e('Enable database activity monitoring', 'bandfront-analytics'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <!-- Database Activity Monitor -->
        <div class="bfa-api-monitor">
            <h3><?php _e('Database Activity Monitor', 'bandfront-analytics'); ?></h3>
            <div class="bfa-traffic-box" id="bfa-db-activity">
                <div class="bfa-traffic-header">
                    <span class="bfa-traffic-status">● <?php _e('Live', 'bandfront-analytics'); ?></span>
                    <button type="button" class="button button-small" id="bfa-clear-db-activity">
                        <?php _e('Clear', 'bandfront-analytics'); ?>
                    </button>
                </div>
                <div class="bfa-traffic-log" id="bfa-db-activity-log">
                    <div class="bfa-traffic-empty"><?php _e('Waiting for database activity...', 'bandfront-analytics'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Database Schema Section -->
        <div class="bfa-api-endpoints">
            <h3><?php esc_html_e('Database Schema', 'bandfront-analytics'); ?></h3>
            <?php $this->renderDatabaseSchema(); ?>
        </div>
        
        <?php $this->renderDatabaseMonitorScripts(); ?>
        <?php
    }
    
    /**
     * Render database schema information
     */
    private function renderDatabaseSchema(): void {
        global $wpdb;
        
        // Get actual analytics tables
        $tables = [
            'bfa_events' => __('Analytics Events', 'bandfront-analytics'),
            'bfa_stats' => __('Aggregated Statistics', 'bandfront-analytics'),
        ];
        
        ?>
        <div class="bfa-endpoints-list">
            <p class="description">
                <?php 
                printf(
                    esc_html__('Total tables: %d', 'bandfront-analytics'), 
                    count($tables)
                ); 
                ?>
            </p>
            
            <?php foreach ($tables as $table => $description): ?>
                <?php
                $full_table_name = $wpdb->prefix . $table;
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") === $full_table_name;
                
                if ($table_exists) {
                    $columns = $wpdb->get_results("SHOW FULL COLUMNS FROM `{$full_table_name}`");
                    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `{$full_table_name}`");
                    $indexes = $wpdb->get_results("SHOW INDEX FROM `{$full_table_name}`");
                } else {
                    $columns = [];
                    $row_count = 0;
                    $indexes = [];
                }
                ?>
                <div class="bfa-endpoint-item bfa-database-table">
                    <div class="bfa-endpoint-route">
                        <code><?php echo esc_html($full_table_name); ?></code>
                        <span class="description"><?php echo esc_html($description); ?></span>
                    </div>
                    <div class="bfa-endpoint-methods">
                        <?php if ($table_exists): ?>
                            <span class="bfa-method-badge bfa-method-get">
                                <?php echo number_format($row_count); ?> <?php esc_html_e('rows', 'bandfront-analytics'); ?>
                            </span>
                        <?php else: ?>
                            <span class="bfa-method-badge" style="background: #dc3232;">
                                <?php esc_html_e('Table not found', 'bandfront-analytics'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($table_exists && !empty($columns)): ?>
                        <!-- Detailed field list -->
                        <div class="bfa-table-fields">
                            <h4><?php esc_html_e('Table Structure', 'bandfront-analytics'); ?></h4>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Field', 'bandfront-analytics'); ?></th>
                                        <th><?php esc_html_e('Type', 'bandfront-analytics'); ?></th>
                                        <th><?php esc_html_e('Null', 'bandfront-analytics'); ?></th>
                                        <th><?php esc_html_e('Key', 'bandfront-analytics'); ?></th>
                                        <th><?php esc_html_e('Default', 'bandfront-analytics'); ?></th>
                                        <th><?php esc_html_e('Extra', 'bandfront-analytics'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($columns as $column): ?>
                                        <tr>
                                            <td><code><?php echo esc_html($column->Field); ?></code></td>
                                            <td><small><?php echo esc_html($column->Type); ?></small></td>
                                            <td><?php echo esc_html($column->Null); ?></td>
                                            <td>
                                                <?php if ($column->Key === 'PRI'): ?>
                                                    <span class="bfa-key-badge bfa-key-primary">PRIMARY</span>
                                                <?php elseif ($column->Key === 'UNI'): ?>
                                                    <span class="bfa-key-badge bfa-key-unique">UNIQUE</span>
                                                <?php elseif ($column->Key === 'MUL'): ?>
                                                    <span class="bfa-key-badge bfa-key-index">INDEX</span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo esc_html($column->Default ?? 'NULL'); ?></small></td>
                                            <td><small><?php echo esc_html($column->Extra); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (!empty($indexes)): ?>
                                <h5><?php esc_html_e('Indexes', 'bandfront-analytics'); ?></h5>
                                <div class="bfa-index-list">
                                    <?php 
                                    $index_groups = [];
                                    foreach ($indexes as $index) {
                                        $index_groups[$index->Key_name][] = $index->Column_name;
                                    }
                                    ?>
                                    <?php foreach ($index_groups as $index_name => $columns): ?>
                                        <div class="bfa-index-item">
                                            <code><?php echo esc_html($index_name); ?></code>
                                            <span class="description">(<?php echo esc_html(implode(', ', $columns)); ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Table size info -->
                            <?php
                            $table_info = $wpdb->get_row("
                                SELECT 
                                    ROUND(data_length / 1024 / 1024, 2) AS data_size_mb,
                                    ROUND(index_length / 1024 / 1024, 2) AS index_size_mb
                                FROM information_schema.tables
                                WHERE table_schema = DATABASE()
                                AND table_name = '{$full_table_name}'
                            ");
                            ?>
                            <?php if ($table_info): ?>
                                <div class="bfa-table-meta">
                                    <span><?php esc_html_e('Data Size:', 'bandfront-analytics'); ?> <?php echo esc_html($table_info->data_size_mb); ?> MB</span>
                                    <span><?php esc_html_e('Index Size:', 'bandfront-analytics'); ?> <?php echo esc_html($table_info->index_size_mb); ?> MB</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render Database Monitor specific scripts
     */
    private function renderDatabaseMonitorScripts(): void {
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
                    action: 'bfa_generate_test_events',
                    nonce: '<?php echo wp_create_nonce('bfa_test_actions'); ?>'
                }, function(response) {
                    $button.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                    
                    if (response.success) {
                        $message.html('<span style="color: #46b450;">' + response.data.message + '</span>');
                        // Trigger refresh of activity monitor if it exists
                        if (typeof loadDbActivity === 'function') {
                            loadDbActivity();
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
                if (!confirm('<?php _e('Are you sure you want to delete all test data? This cannot be undone.', 'bandfront-analytics'); ?>')) {
                    return;
                }
                
                var $button = $(this);
                var $spinner = $('.bfa-db-test-actions .spinner');
                var $message = $('.bfa-test-message');
                
                $button.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $message.text('');
                
                $.post(ajaxurl, {
                    action: 'bfa_clean_test_events',
                    nonce: '<?php echo wp_create_nonce('bfa_test_actions'); ?>'
                }, function(response) {
                    $button.prop('disabled', false);
                    $spinner.css('visibility', 'hidden');
                    
                    if (response.success) {
                        $message.html('<span style="color: #46b450;">' + response.data.message + '</span>');
                        // Trigger refresh of activity monitor if it exists
                        if (typeof loadDbActivity === 'function') {
                            loadDbActivity();
                        }
                    } else {
                        $message.html('<span style="color: #dc3232;">' + (response.data.message || 'Error cleaning test data') + '</span>');
                    }
                    
                    // Clear message after 5 seconds
                    setTimeout(function() {
                        $message.fadeOut(function() {
                            $(this).text('').show();
                        });
                    }, 5000);
                });
            });
            
            // Initialize database activity monitoring
            if ($('#bfa-db-activity-log').length) {
                var activityPaused = false;
                var activityInterval;
                
                function loadDbActivity() {
                    if (activityPaused) return;
                    
                    $.post(ajaxurl, {
                        action: 'bfa_get_db_activity',
                        nonce: '<?php echo wp_create_nonce('bfa_ajax'); ?>'
                    }, function(response) {
                        if (response.success && response.data.activity) {
                            var $log = $('#bfa-db-activity-log');
                            $log.empty();
                            
                            if (response.data.activity.length === 0) {
                                $log.html('<div class="bfa-traffic-empty"><?php _e('No recent database activity...', 'bandfront-analytics'); ?></div>');
                            } else {
                                response.data.activity.forEach(function(event) {
                                    var $entry = $('<div class="bfa-traffic-entry"></div>');
                                    $entry.append('<span class="bfa-traffic-time">' + event.time + '</span>');
                                    $entry.append('<span class="bfa-traffic-method bfa-method-' + event.type + '">' + event.type.toUpperCase() + '</span>');
                                    $entry.append('<span class="bfa-traffic-route">' + event.object + '</span>');
                                    $entry.append('<span class="bfa-traffic-user">' + event.referrer + '</span>');
                                    $log.append($entry);
                                });
                            }
                        }
                    });
                }
                
                // Load initial data
                loadDbActivity();
                
                // Set up auto-refresh
                activityInterval = setInterval(loadDbActivity, 5000);
                
                // Clear button handler
                $('#bfa-clear-db-activity').on('click', function() {
                    $('#bfa-db-activity-log').html('<div class="bfa-traffic-empty"><?php _e('Cleared. Waiting for new activity...', 'bandfront-analytics'); ?></div>');
                });
                
                // Pause/resume handler
                $('#bfa-pause-monitor').on('click', function() {
                    activityPaused = !activityPaused;
                    $(this).text(activityPaused ? '<?php _e('Resume', 'bandfront-analytics'); ?>' : '<?php _e('Pause', 'bandfront-analytics'); ?>');
                    $('.bfa-traffic-status').text(activityPaused ? '● <?php _e('Paused', 'bandfront-analytics'); ?>' : '● <?php _e('Live', 'bandfront-analytics'); ?>');
                    if (activityPaused) {
                        $('.bfa-traffic-status').css('color', '#666');
                    } else {
                        $('.bfa-traffic-status').css('color', '#46b450');
                        loadDbActivity(); // Refresh immediately on resume
                    }
                });
            }
        });
        </script>
        
        <style>
            .bfa-database-table {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #ddd;
            }
            .bfa-table-fields {
                margin-top: 15px;
                background: #f9f9f9;
                padding: 15px;
                border-radius: 4px;
            }
            .bfa-table-fields h4 {
                margin-top: 0;
                margin-bottom: 10px;
                color: #23282d;
            }
            .bfa-table-fields h5 {
                margin-top: 15px;
                margin-bottom: 10px;
                color: #23282d;
            }
            .bfa-key-badge {
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
            }
            .bfa-key-primary {
                background: #0073aa;
                color: white;
            }
            .bfa-key-unique {
                background: #46b450;
                color: white;
            }
            .bfa-key-index {
                background: #826eb4;
                color: white;
            }
            .bfa-index-list {
                background: white;
                padding: 10px;
                border-radius: 3px;
                border: 1px solid #e5e5e5;
            }
            .bfa-index-item {
                padding: 5px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            .bfa-index-item:last-child {
                border-bottom: none;
            }
            .bfa-table-meta {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid #e5e5e5;
                color: #666;
                font-size: 13px;
            }
            .bfa-table-meta span {
                margin-right: 15px;
            }
        </style>
        <?php
    }
}
