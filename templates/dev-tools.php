<?php
/**
 * Developer Tools Template
 * 
 * This template provides database monitoring and developer tools
 * Only shown when dev mode is enabled
 *
 * @package BandfrontPlayer
 * @subpackage Views
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if dev mode is enabled
$devMode = $config->getState('_bfp_dev_mode', 0);

if (!$devMode) {
    return; // Don't render anything if dev mode is off
}

// The DbRenderer instance is provided by AdminRenderer
// No fallback needed - clean architecture!
?>

<!-- Database Monitor Tab -->
<div id="database-monitor-panel" class="bfp-tab-panel" style="display:none;">
    <h3>🗄️ <?php esc_html_e('Database Monitor', 'bandfront-player'); ?></h3>
    <?php 
    // Use the DbRenderer to render this section - always available from AdminRenderer
    $dbRenderer->renderDatabaseMonitorSection();
    ?>
</div>

<!-- Dev Tab -->
<div id="dev-panel" class="bfp-tab-panel" style="display:none;">
    <h3>🛠️ <?php esc_html_e('Developer Tools', 'bandfront-player'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Debug Mode', 'bandfront-player'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="_bfp_debug_mode" value="1" <?php checked($config->getState('_bfp_debug_mode', 0)); ?> />
                    <?php esc_html_e('Enable debug logging', 'bandfront-player'); ?>
                </label>
                <p class="description"><?php esc_html_e('Logs debug information to the WordPress debug.log file.', 'bandfront-player'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('System Information', 'bandfront-player'); ?></th>
            <td>
                <div class="bfp-system-info">
                    <h4><?php esc_html_e('Environment', 'bandfront-player'); ?></h4>
                    <ul>
                        <li>🖥️ <?php esc_html_e('PHP Version:', 'bandfront-player'); ?> <code><?php echo PHP_VERSION; ?></code></li>
                        <li>🌐 <?php esc_html_e('WordPress Version:', 'bandfront-player'); ?> <code><?php echo get_bloginfo('version'); ?></code></li>
                        <li>🛒 <?php esc_html_e('WooCommerce:', 'bandfront-player'); ?> <code><?php echo class_exists('WooCommerce') ? WC()->version : __('Not Active', 'bandfront-player'); ?></code></li>
                        <li>🎵 <?php esc_html_e('Plugin Version:', 'bandfront-player'); ?> <code>5.0.181</code></li>
                    </ul>
                    
                    <h4><?php esc_html_e('Server Configuration', 'bandfront-player'); ?></h4>
                    <ul>
                        <li>⏰ <?php esc_html_e('Max Execution Time:', 'bandfront-player'); ?> <code><?php echo ini_get('max_execution_time'); ?>s</code></li>
                        <li>💾 <?php esc_html_e('Memory Limit:', 'bandfront-player'); ?> <code><?php echo ini_get('memory_limit'); ?></code></li>
                        <li>📤 <?php esc_html_e('Upload Max Size:', 'bandfront-player'); ?> <code><?php echo ini_get('upload_max_filesize'); ?></code></li>
                        <li>📁 <?php esc_html_e('FFmpeg Available:', 'bandfront-player'); ?> <code><?php echo function_exists('shell_exec') && @shell_exec('which ffmpeg') ? __('Yes', 'bandfront-player') : __('No', 'bandfront-player'); ?></code></li>
                    </ul>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('API Endpoints', 'bandfront-player'); ?></th>
            <td>
                <div class="bfp-api-info">
                    <h4><?php esc_html_e('REST API Endpoints', 'bandfront-player'); ?></h4>
                    <ul>
                        <li>🔗 <code>/wp-json/bandfront/v1/stream/{file_id}</code> - <?php esc_html_e('Audio streaming endpoint', 'bandfront-player'); ?></li>
                        <li>🔗 <code>/wp-json/bandfront/v1/analytics/track</code> - <?php esc_html_e('Analytics tracking endpoint', 'bandfront-player'); ?></li>
                    </ul>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Shortcodes', 'bandfront-player'); ?></th>
            <td>
                <div class="bfp-shortcode-info">
                    <h4><?php esc_html_e('Available Shortcodes', 'bandfront-player'); ?></h4>
                    <ul>
                        <li><code>[bfp-player id="123"]</code> - <?php esc_html_e('Display player for specific product', 'bandfront-player'); ?></li>
                        <li><code>[bfp-playlist ids="123,456,789"]</code> - <?php esc_html_e('Display playlist of products', 'bandfront-player'); ?></li>
                    </ul>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Database Maintenance', 'bandfront-player'); ?></th>
            <td>
                <div class="bfp-db-maintenance">
                    <div class="button-group">
                        <button type="button" class="button" id="bfp-clear-caches">
                            <?php esc_html_e('Clear All Caches', 'bandfront-player'); ?>
                        </button>
                        <button type="button" class="button" id="bfp-optimize-tables">
                            <?php esc_html_e('Optimize DB Tables', 'bandfront-player'); ?>
                        </button>
                        <button type="button" class="button" id="bfp-scan-orphaned">
                            <?php esc_html_e('Scan Orphaned Data', 'bandfront-player'); ?>
                        </button>
                        <button type="button" class="button" id="bfp-export-settings">
                            <?php esc_html_e('Export Settings', 'bandfront-player'); ?>
                        </button>
                    </div>
                    <div id="bfp-maintenance-results" style="margin-top: 10px;"></div>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Developer Actions', 'bandfront-player'); ?></th>
            <td>
                <button type="button" class="button" id="bfp-export-debug-log"><?php esc_html_e('Export Debug Log', 'bandfront-player'); ?></button>
                <button type="button" class="button button-warning" id="bfp-reset-plugin"><?php esc_html_e('Reset Plugin', 'bandfront-player'); ?></button>
                <button type="button" class="button" id="bfp-run-tests"><?php esc_html_e('Run Tests', 'bandfront-player'); ?></button>
                <p class="description"><?php esc_html_e('Advanced developer tools for debugging and testing.', 'bandfront-player'); ?></p>
            </td>
        </tr>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Database maintenance handlers
    $('#bfp-clear-caches').on('click', function() {
        var $button = $(this);
        var $results = $('#bfp-maintenance-results');
        
        $button.prop('disabled', true);
        $results.html('<div class="notice notice-info inline"><p>Clearing caches...</p></div>');
        
        $.post(ajaxurl, {
            action: 'bfp_clear_caches',
            nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                $results.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
            } else {
                $results.html('<div class="notice notice-error inline"><p>' + (response.data.message || 'Error occurred') + '</p></div>');
            }
        });
    });
    
    $('#bfp-optimize-tables').on('click', function() {
        var $button = $(this);
        var $results = $('#bfp-maintenance-results');
        
        $button.prop('disabled', true);
        $results.html('<div class="notice notice-info inline"><p>Optimizing database tables...</p></div>');
        
        $.post(ajaxurl, {
            action: 'bfp_optimize_tables',
            nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                $results.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
            } else {
                $results.html('<div class="notice notice-error inline"><p>' + (response.data.message || 'Error occurred') + '</p></div>');
            }
        });
    });
    
    $('#bfp-scan-orphaned').on('click', function() {
        var $button = $(this);
        var $results = $('#bfp-maintenance-results');
        
        $button.prop('disabled', true);
        $results.html('<div class="notice notice-info inline"><p>Scanning for orphaned data...</p></div>');
        
        $.post(ajaxurl, {
            action: 'bfp_scan_orphaned',
            nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                $results.html('<div class="notice notice-info inline"><p>Found ' + response.data.orphaned_meta + ' orphaned meta entries and ' + response.data.orphaned_files + ' orphaned files. <button type="button" class="button button-small" id="bfp-clean-orphaned">Clean Now</button></p></div>');
            } else {
                $results.html('<div class="notice notice-error inline"><p>' + (response.data.message || 'Error occurred') + '</p></div>');
            }
        });
    });
    
    $(document).on('click', '#bfp-clean-orphaned', function() {
        var $button = $(this);
        var $results = $('#bfp-maintenance-results');
        
        $button.prop('disabled', true);
        $results.html('<div class="notice notice-info inline"><p>Cleaning orphaned data...</p></div>');
        
        $.post(ajaxurl, {
            action: 'bfp_clean_orphaned',
            nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>'
        }, function(response) {
            if (response.success) {
                $results.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
            } else {
                $results.html('<div class="notice notice-error inline"><p>' + (response.data.message || 'Error occurred') + '</p></div>');
            }
        });
    });
    
    $('#bfp-export-settings').on('click', function() {
        var $button = $(this);
        var $results = $('#bfp-maintenance-results');
        
        $button.prop('disabled', true);
        $results.html('<div class="notice notice-info inline"><p>Exporting settings...</p></div>');
        
        $.post(ajaxurl, {
            action: 'bfp_export_settings',
            nonce: '<?php echo wp_create_nonce('bfp_db_actions'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                $results.html('<div class="notice notice-success inline"><p>' + response.data.message + ' <a href="' + response.data.download_url + '" class="button button-small">Download</a></p></div>');
            } else {
                $results.html('<div class="notice notice-error inline"><p>' + (response.data.message || 'Error occurred') + '</p></div>');
            }
        });
    });
});
</script>