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

// Helper functions for stats
function bfp_getProductsWithAudioCount() {
    global $wpdb;
    $count = $wpdb->get_var("
        SELECT COUNT(DISTINCT post_id) 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_bfp_enable_player' 
        AND meta_value = '1'
    ");
    return $count ?: 0;
}

function bfp_getDemoFilesCount() {
    $upload_dir = wp_upload_dir();
    $demo_dir = $upload_dir['basedir'] . '/bandfront-player-files/';
    if (is_dir($demo_dir)) {
        $files = glob($demo_dir . '*.mp3');
        return count($files);
    }
    return 0;
}

function bfp_getCacheSize() {
    global $wpdb;
    $size = $wpdb->get_var("
        SELECT SUM(LENGTH(option_value)) 
        FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_bfp_%'
    ");
    return $size ? size_format($size) : '0 B';
}

function bfp_getLastCacheClear() {
    $last_clear = get_option('_bfp_last_cache_clear', 0);
    return $last_clear ? human_time_diff($last_clear) . ' ' . __('ago', 'bandfront-player') : __('Never', 'bandfront-player');
}
?>

<!-- Database Monitor Tab -->
<div id="database-monitor-panel" class="bfp-tab-panel" style="display:none;">
    <h3>🗄️ <?php esc_html_e('Database Monitor', 'bandfront-player'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Database Tables', 'bandfront-player'); ?></th>
            <td>
                <p class="description"><?php esc_html_e('Monitor and manage plugin database tables and cached data.', 'bandfront-player'); ?></p>
                <div class="bfp-database-stats">
                    <h4><?php esc_html_e('Plugin Data Statistics', 'bandfront-player'); ?></h4>
                    <ul>
                        <li>📊 <?php esc_html_e('Total Products with Audio:', 'bandfront-player'); ?> <strong><?php echo esc_html(bfp_getProductsWithAudioCount()); ?></strong></li>
                        <li>🎵 <?php esc_html_e('Total Demo Files:', 'bandfront-player'); ?> <strong><?php echo esc_html(bfp_getDemoFilesCount()); ?></strong></li>
                        <li>💾 <?php esc_html_e('Cache Size:', 'bandfront-player'); ?> <strong><?php echo esc_html(bfp_getCacheSize()); ?></strong></li>
                        <li>⏱️ <?php esc_html_e('Last Cache Clear:', 'bandfront-player'); ?> <strong><?php echo esc_html(bfp_getLastCacheClear()); ?></strong></li>
                    </ul>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Database Maintenance', 'bandfront-player'); ?></th>
            <td>
                <button type="button" class="button" id="bfp-clear-caches"><?php esc_html_e('Clear All Caches', 'bandfront-player'); ?></button>
                <button type="button" class="button" id="bfp-optimize-tables"><?php esc_html_e('Optimize Tables', 'bandfront-player'); ?></button>
                <button type="button" class="button" id="bfp-export-settings"><?php esc_html_e('Export Settings', 'bandfront-player'); ?></button>
                <p class="description"><?php esc_html_e('Perform database maintenance operations.', 'bandfront-player'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Orphaned Data', 'bandfront-player'); ?></th>
            <td>
                <p class="description"><?php esc_html_e('Check for and clean up orphaned demo files and metadata.', 'bandfront-player'); ?></p>
                <button type="button" class="button" id="bfp-scan-orphaned"><?php esc_html_e('Scan for Orphaned Data', 'bandfront-player'); ?></button>
                <div id="bfp-orphaned-results" style="margin-top: 10px; display: none;">
                    <p class="bfp-scan-results"></p>
                </div>
            </td>
        </tr>
    </table>
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

