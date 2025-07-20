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

// Get current debug settings - handle the new structure
$debugSettings = $config->getState('_bfp_debug', [
    'enabled' => false,
    'domains' => []
]);

// Extract enabled and domains from the settings
$debugEnabled = $debugSettings['enabled'] ?? false;
$debugDomains = $debugSettings['domains'] ?? [];

// Define domains with labels and groups
$domainGroups = [
    'Core' => [
        'core' => __('Core (All)', 'bandfront-player'),
        'core-bootstrap' => __('Bootstrap', 'bandfront-player'),
        'core-config' => __('Config', 'bandfront-player'),
        'core-hooks' => __('Hooks', 'bandfront-player'),
    ],
    'Components' => [
        'admin' => __('Admin', 'bandfront-player'),
        'audio' => __('Audio', 'bandfront-player'),
        'storage' => __('Storage', 'bandfront-player'),
        'ui' => __('UI', 'bandfront-player'),
        'api' => __('REST API', 'bandfront-player'),
    ],
    'Other' => [
        'db' => __('Database', 'bandfront-player'),
        'utils' => __('Utilities', 'bandfront-player'),
        'wordpress-elements' => __('WP Elements', 'bandfront-player'),
        'woocommerce' => __('WooCommerce', 'bandfront-player'),
    ]
];
?>

<!-- Database Monitor Tab -->
<div id="database-monitor-panel" class="bfp-tab-panel" style="display:none;">
    <h3>🗄️ <?php esc_html_e('Database Monitor', 'bandfront-player'); ?></h3>
    <?php 
    // Use the DbRenderer to render this section - always available from AdminRenderer
    // Note: We keep the header here so it's visible even when monitoring is disabled
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
                    <input type="checkbox" id="debug_enabled" name="_bfp_debug[enabled]" value="1" <?php checked($debugEnabled, true); ?> />
                    <?php esc_html_e('Enable debug logging', 'bandfront-player'); ?>
                </label>
                <p class="description"><?php esc_html_e('Master switch for all debug logging. Individual domains can be enabled below.', 'bandfront-player'); ?></p>
                
                <div id="debug-domains" style="<?php echo $debugEnabled ? 'margin-top: 20px;' : 'display:none; margin-top: 20px;'; ?>">
                    <h4><?php esc_html_e('Debug Domains', 'bandfront-player'); ?></h4>
                    <p class="description"><?php esc_html_e('Enable logging for specific areas of the plugin:', 'bandfront-player'); ?></p>
                    
                    <?php foreach ($domainGroups as $groupName => $domains): ?>
                    <div style="margin-top: 15px;">
                        <strong><?php echo esc_html($groupName); ?></strong>
                        <div class="debug-domains-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-top: 8px; margin-left: 20px;">
                            <?php foreach ($domains as $domain => $label): ?>
                            <label style="display: flex; align-items: center;">
                                <input type="checkbox" 
                                       name="_bfp_debug[domains][<?php echo esc_attr($domain); ?>]" 
                                       value="1" 
                                       <?php checked(!empty($debugDomains[$domain]), true); ?> />
                                <span style="margin-left: 5px;">
                                    <?php echo esc_html($label); ?>
                                    <?php if ($domain === 'core'): ?>
                                    <small style="color: #666;">(enables all core-*)</small>
                                    <?php endif; ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" class="button" id="enable-all-domains"><?php esc_html_e('Enable All', 'bandfront-player'); ?></button>
                        <button type="button" class="button" id="disable-all-domains"><?php esc_html_e('Disable All', 'bandfront-player'); ?></button>
                        <button type="button" class="button" id="enable-core-domains"><?php esc_html_e('Enable Core Only', 'bandfront-player'); ?></button>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                        <h5 style="margin-top: 0;"><?php esc_html_e('Usage Examples:', 'bandfront-player'); ?></h5>
                        <pre style="background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px;">// Domain-specific methods (automatic domain detection):
Debug::admin('Processing admin request', ['action' => $action]);
Debug::ui('Rendering player', ['product_id' => $productId]);
Debug::storage('Uploading file', ['filename' => $filename]);
Debug::api('API request received', ['endpoint' => '/stream']);

// Or set domain at file level:
Debug::domain('admin');
Debug::log('Processing request', ['data' => $data]);</pre>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('System Information', 'bandfront-player'); ?></th>
            <td>
                <div class="bfp-system-info" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <h4 style="margin-top: 0;"><?php esc_html_e('Environment', 'bandfront-player'); ?></h4>
                        <ul style="margin: 0; padding-left: 20px;">
                            <li>🖥️ <?php esc_html_e('PHP Version:', 'bandfront-player'); ?> <code><?php echo PHP_VERSION; ?></code></li>
                            <li>🌐 <?php esc_html_e('WordPress Version:', 'bandfront-player'); ?> <code><?php echo get_bloginfo('version'); ?></code></li>
                            <li>🛒 <?php esc_html_e('WooCommerce:', 'bandfront-player'); ?> <code><?php echo class_exists('WooCommerce') ? WC()->version : __('Not Active', 'bandfront-player'); ?></code></li>
                            <li>🎵 <?php esc_html_e('Plugin Version:', 'bandfront-player'); ?> <code><?php 
                                // Get plugin version the WordPress way
                                if (!function_exists('get_plugin_data')) {
                                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                                }
                                $plugin_data = get_plugin_data(BFP_PLUGIN_PATH);
                                echo esc_html($plugin_data['Version'] ?? '0.1');
                            ?></code></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 style="margin-top: 0;"><?php esc_html_e('Server Configuration', 'bandfront-player'); ?></h4>
                        <ul style="margin: 0; padding-left: 20px;">
                            <li>⏰ <?php esc_html_e('Max Execution Time:', 'bandfront-player'); ?> <code><?php echo ini_get('max_execution_time'); ?>s</code></li>
                            <li>💾 <?php esc_html_e('Memory Limit:', 'bandfront-player'); ?> <code><?php echo ini_get('memory_limit'); ?></code></li>
                            <li>📤 <?php esc_html_e('Upload Max Size:', 'bandfront-player'); ?> <code><?php echo ini_get('upload_max_filesize'); ?></code></li>
                            <li>📁 <?php esc_html_e('FFmpeg Available:', 'bandfront-player'); ?> <code><?php echo function_exists('shell_exec') && @shell_exec('which ffmpeg') ? __('Yes', 'bandfront-player') : __('No', 'bandfront-player'); ?></code></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Add responsive behavior for mobile -->
                <style>
                @media (max-width: 768px) {
                    .bfp-system-info {
                        grid-template-columns: 1fr !important;
                    }
                }
                </style>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('API Endpoints', 'bandfront-player'); ?></th>
            <td>
                <div class="bfp-api-info">
                    <h4><?php esc_html_e('REST API Endpoints', 'bandfront-player'); ?></h4>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <p style="margin: 0 0 10px 0;"><strong><?php esc_html_e('Base URL:', 'bandfront-player'); ?></strong> <code><?php echo esc_url(home_url('/wp-json/bandfront-player/v1')); ?></code></p>
                        <p style="margin: 0;"><strong><?php esc_html_e('Namespace:', 'bandfront-player'); ?></strong> <code>bandfront-player/v1</code></p>
                    </div>
                    
                    <h5><?php esc_html_e('Available Endpoints:', 'bandfront-player'); ?></h5>
                    <ul style="margin-bottom: 20px;">
                        <li>
                            <strong>🎵 Stream Audio</strong><br>
                            <code>GET /stream/{product_id}/{file_id}</code><br>
                            <small><?php esc_html_e('Streams audio files with range support. Requires valid nonce.', 'bandfront-player'); ?></small>
                        </li>
                        <li style="margin-top: 10px;">
                            <strong>📊 Track Playback</strong><br>
                            <code>POST /track</code><br>
                            <small><?php esc_html_e('Records playback events (play, pause, ended). Accepts JSON payload with event details.', 'bandfront-player'); ?></small>
                        </li>
                    </ul>
                    
                    <h4><?php esc_html_e('AJAX Endpoints', 'bandfront-player'); ?></h4>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <p style="margin: 0 0 10px 0;"><strong><?php esc_html_e('WooCommerce AJAX:', 'bandfront-player'); ?></strong> <code><?php echo esc_url(home_url('/?wc-ajax=')); ?></code></p>
                        <p style="margin: 0;"><strong><?php esc_html_e('Admin AJAX:', 'bandfront-player'); ?></strong> <code><?php echo esc_url(admin_url('admin-ajax.php')); ?></code></p>
                    </div>
                    
                    <h5><?php esc_html_e('Player Control Actions:', 'bandfront-player'); ?></h5>
                    <ul style="margin-bottom: 20px;">
                        <li><code>bfp_track_play</code> - <?php esc_html_e('Track play event', 'bandfront-player'); ?></li>
                        <li><code>bfp_track_pause</code> - <?php esc_html_e('Track pause event', 'bandfront-player'); ?></li>
                        <li><code>bfp_track_ended</code> - <?php esc_html_e('Track ended event', 'bandfront-player'); ?></li>
                        <li><code>bfp_next_track</code> - <?php esc_html_e('Load next track in playlist', 'bandfront-player'); ?></li>
                        <li><code>bfp_previous_track</code> - <?php esc_html_e('Load previous track in playlist', 'bandfront-player'); ?></li>
                    </ul>
                    
                    <h5><?php esc_html_e('WooCommerce Integration:', 'bandfront-player'); ?></h5>
                    <ul>
                        <li><code>get_refreshed_fragments</code> - <?php esc_html_e('Updates cart fragments (used for dynamic cart updates)', 'bandfront-player'); ?></li>
                    </ul>
                    
                    <div style="background: #e8f4f8; padding: 15px; border-radius: 4px; margin-top: 20px;">
                        <p style="margin: 0;"><strong>💡 <?php esc_html_e('Debug Tip:', 'bandfront-player'); ?></strong> <?php esc_html_e('Enable debug mode and check the Network tab in browser DevTools to see API calls in action.', 'bandfront-player'); ?></p>
                    </div>
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
    
    <!-- Add save notice for debug settings -->
    <div class="notice notice-info inline" style="margin-top: 20px;">
        <p><?php esc_html_e('Remember to save your settings using the "Save Settings" button at the bottom of the page to persist debug configuration changes.', 'bandfront-player'); ?></p>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Debug domain toggle functionality
    $('#debug_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#debug-domains').slideDown();
        } else {
            $('#debug-domains').slideUp();
        }
    });
    
    // Enable all domains
    $('#enable-all-domains').on('click', function() {
        $('input[name^="_bfp_debug[domains]"]').prop('checked', true);
    });
    
    // Disable all domains
    $('#disable-all-domains').on('click', function() {
        $('input[name^="_bfp_debug[domains]"]').prop('checked', false);
    });
    
    // Enable core domains only
    $('#enable-core-domains').on('click', function() {
        $('input[name^="_bfp_debug[domains]"]').prop('checked', false);
        $('input[name="_bfp_debug[domains][core]"]').prop('checked', true);
        $('input[name="_bfp_debug[domains][core-bootstrap]"]').prop('checked', true);
        $('input[name="_bfp_debug[domains][core-config]"]').prop('checked', true);
        $('input[name="_bfp_debug[domains][core-hooks]"]').prop('checked', true);
    });
    
    // When 'core' is checked, also check all core-* domains
    $('input[name="_bfp_debug[domains][core]"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('input[name^="_bfp_debug[domains][core-"]').prop('checked', true);
        }
    });
    
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