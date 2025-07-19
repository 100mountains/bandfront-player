<?php
/**
 * Database Monitor Templates
 * 
 * This file contains all HTML templates for the Database Monitor
 * to keep the DbRenderer class focused on logic and data preparation.
 *
 * @package BandfrontPlayer
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render monitoring enable checkbox
 */
function bfp_render_monitoring_checkbox(bool $monitoring_enabled): void {
    ?>
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
    <?php
}

/**
 * Render disabled notice
 */
function bfp_render_monitoring_disabled_notice(): void {
    ?>
    <div class="notice notice-info inline">
        <p><?php _e('Database monitoring is currently disabled. Enable it above and save settings to see real-time activity.', 'bandfront-player'); ?></p>
    </div>
    <?php
}

/**
 * Render sub-tabs navigation
 */
function bfp_render_db_subtabs(): void {
    ?>
    <div class="bfp-db-subtabs">
        <h3 class="nav-tab-wrapper">
            <a href="#" class="nav-tab nav-tab-active" data-subtab="monitoring"><?php _e('Monitoring', 'bandfront-player'); ?></a>
            <a href="#" class="nav-tab" data-subtab="products"><?php _e('Products', 'bandfront-player'); ?></a>
            <a href="#" class="nav-tab" data-subtab="schema"><?php _e('Schema', 'bandfront-player'); ?></a>
        </h3>
    </div>
    <?php
}

/**
 * Render test action buttons
 */
function bfp_render_test_actions(): void {
    ?>
    <div class="bfa-db-test-actions">
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
    <?php
}

/**
 * Render database activity monitor
 */
function bfp_render_activity_monitor(): void {
    ?>
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
    <?php
}

/**
 * Render stats grid
 */
function bfp_render_stats_grid(array $stats): void {
    ?>
    <div class="bfa-stats-grid">
        <?php foreach ($stats as $stat): ?>
        <div class="bfa-stat-item">
            <span class="bfa-stat-label"><?php echo esc_html($stat['label']); ?></span>
            <span class="bfa-stat-value"><?php echo esc_html($stat['value']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Render performance metrics
 */
function bfp_render_performance_metrics(array $metrics): void {
    ?>
    <?php foreach ($metrics as $metric): ?>
    <div class="bfa-metric-item">
        <span class="bfa-metric-label"><?php echo esc_html($metric['label']); ?></span>
        <span class="bfa-metric-value"><?php echo esc_html($metric['value']); ?></span>
    </div>
    <?php endforeach; ?>
    <?php
}

/**
 * Render monitor grid section
 */
function bfp_render_monitor_grid(array $db_stats, array $perf_metrics): void {
    ?>
    <div class="bfa-monitor-grid">
        <!-- Database Stats -->
        <div class="bfa-monitor-section">
            <h3><?php _e('Database Statistics', 'bandfront-player'); ?></h3>
            <div class="bfa-db-stats">
                <?php bfp_render_stats_grid($db_stats); ?>
            </div>
        </div>
        
        <!-- Performance Metrics -->
        <div class="bfa-monitor-section">
            <h3><?php _e('Performance Metrics', 'bandfront-player'); ?></h3>
            <div class="bfa-performance-grid">
                <?php bfp_render_performance_metrics($perf_metrics); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render product stats boxes
 */
function bfp_render_product_stats(int $total_products, int $with_audio, int $total_files): void {
    ?>
    <div class="bfa-schema-stats">
        <div class="bfa-stat-box">
            <span class="bfa-stat-number"><?php echo $total_products; ?></span>
            <span class="bfa-stat-label"><?php _e('Products Found', 'bandfront-player'); ?></span>
        </div>
        <div class="bfa-stat-box">
            <span class="bfa-stat-number"><?php echo $with_audio; ?></span>
            <span class="bfa-stat-label"><?php _e('With Audio', 'bandfront-player'); ?></span>
        </div>
        <div class="bfa-stat-box">
            <span class="bfa-stat-number"><?php echo $total_files; ?></span>
            <span class="bfa-stat-label"><?php _e('Total Audio Files', 'bandfront-player'); ?></span>
        </div>
    </div>
    <?php
}

/**
 * Render product list header
 */
function bfp_render_product_header(int $product_id, string $title, bool $has_audio): void {
    ?>
    <h4 class="bfa-section-header bfa-collapsible" data-target="product-<?php echo $product_id; ?>">
        <span class="dashicons dashicons-<?php echo $has_audio ? 'format-audio' : 'products'; ?>"></span>
        <?php echo esc_html($title); ?>
        <span class="bfa-count">#<?php echo $product_id; ?></span>
        <span class="bfa-toggle dashicons dashicons-arrow-down-alt2"></span>
    </h4>
    <?php
}

/**
 * Render product information table
 */
function bfp_render_product_info_table(array $product_info): void {
    ?>
    <h5><?php _e('Product Information', 'bandfront-player'); ?></h5>
    <table class="bfa-config-table">
        <tbody>
            <?php foreach ($product_info as $key => $value): ?>
            <tr>
                <td class="bfa-key"><code><?php echo esc_html($key); ?></code></td>
                <td class="bfa-value">
                    <?php if ($key === 'permalink' && !empty($value)): ?>
                        <a href="<?php echo esc_url($value); ?>" target="_blank"><?php echo esc_html($value); ?></a>
                    <?php else: ?>
                        <?php echo wp_kses_post($value); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Render BFP settings table
 */
function bfp_render_bfp_settings_table(array $settings): void {
    ?>
    <h5 style="margin-top: 20px;"><?php _e('Bandfront Player Settings', 'bandfront-player'); ?></h5>
    <?php if (empty($settings)): ?>
        <p class="bfa-no-settings"><?php _e('No Bandfront Player settings found for this product.', 'bandfront-player'); ?></p>
    <?php else: ?>
        <table class="bfa-config-table">
            <thead>
                <tr>
                    <th><?php _e('Setting Key', 'bandfront-player'); ?></th>
                    <th><?php _e('Value', 'bandfront-player'); ?></th>
                    <th><?php _e('Type', 'bandfront-player'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($settings as $setting): ?>
                <tr>
                    <td class="bfa-key">
                        <code><?php echo esc_html($setting['key']); ?></code>
                    </td>
                    <td class="bfa-value"><?php echo $setting['formatted_value']; ?></td>
                    <td class="bfa-type">
                        <span class="bfa-type-badge bfa-type-<?php echo esc_attr($setting['type']); ?>">
                            <?php echo esc_html($setting['type']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}

/**
 * Render audio files table
 */
function bfp_render_audio_files_table(array $audio_files): void {
    if (empty($audio_files)) return;
    ?>
    <h5 style="margin-top: 20px;"><?php _e('Audio Files', 'bandfront-player'); ?></h5>
    <table class="bfa-config-table">
        <thead>
            <tr>
                <th><?php _e('File', 'bandfront-player'); ?></th>
                <th><?php _e('Location', 'bandfront-player'); ?></th>
                <th><?php _e('Size', 'bandfront-player'); ?></th>
                <th><?php _e('Type', 'bandfront-player'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($audio_files as $file): ?>
            <tr>
                <td class="bfa-key">
                    <code><?php echo esc_html(basename($file['path'])); ?></code>
                </td>
                <td class="bfa-path" style="word-break: break-all;">
                    <?php echo esc_html($file['path']); ?>
                </td>
                <td class="bfa-value">
                    <?php echo $file['exists'] ? esc_html($file['formatted_size']) : '<span class="bfa-empty">Missing</span>'; ?>
                </td>
                <td class="bfa-value">
                    <?php echo esc_html($file['type']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Render enhanced audio files table with FileManager data
 */
function bfp_render_audio_files_enhanced(array $audio_files): void {
    if (empty($audio_files)) {
        ?>
        <h5 style="margin-top: 20px;"><?php _e('Audio Files', 'bandfront-player'); ?></h5>
        <p class="bfa-no-settings"><?php _e('No audio files found for this product.', 'bandfront-player'); ?></p>
        <?php
        return;
    }
    
    // Separate files by source
    $filesBySource = [
        'filemanager' => [],
        'meta' => []
    ];
    
    foreach ($audio_files as $file) {
        $source = $file['source'] ?? 'meta';
        $filesBySource[$source][] = $file;
    }
    ?>
    <h5 style="margin-top: 20px;"><?php _e('Audio Files', 'bandfront-player'); ?></h5>
    
    <?php if (!empty($filesBySource['filemanager'])): ?>
    <h6 style="margin-top: 15px; margin-bottom: 10px;"><?php _e('Files from FileManager (Active)', 'bandfront-player'); ?></h6>
    <table class="bfa-config-table">
        <thead>
            <tr>
                <th><?php _e('Index', 'bandfront-player'); ?></th>
                <th><?php _e('File', 'bandfront-player'); ?></th>
                <th><?php _e('Type', 'bandfront-player'); ?></th>
                <th><?php _e('Media Type', 'bandfront-player'); ?></th>
                <th><?php _e('Size', 'bandfront-player'); ?></th>
                <th><?php _e('Status', 'bandfront-player'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filesBySource['filemanager'] as $file): ?>
            <tr>
                <td class="bfa-key">
                    <code><?php echo esc_html($file['index'] ?? 'N/A'); ?></code>
                </td>
                <td class="bfa-path" style="word-break: break-all;">
                    <code><?php echo esc_html($file['filename']); ?></code>
                    <br>
                    <small style="color: #666;"><?php echo esc_html($file['path']); ?></small>
                </td>
                <td class="bfa-value">
                    <span class="bfa-type-badge" style="background: <?php echo $file['type'] === 'Demo/Preview' ? '#ff6b6b' : '#4ecdc4'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                        <?php echo esc_html($file['type']); ?>
                    </span>
                </td>
                <td class="bfa-value">
                    <code><?php echo esc_html($file['media_type']); ?></code>
                </td>
                <td class="bfa-value">
                    <?php echo $file['exists'] ? esc_html($file['formatted_size']) : '<span class="bfa-empty">N/A</span>'; ?>
                </td>
                <td class="bfa-value">
                    <?php if ($file['exists']): ?>
                        <span class="bfa-bool-true">✓ Found</span>
                    <?php else: ?>
                        <span class="bfa-bool-false">✗ Missing</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    
    <?php if (!empty($filesBySource['meta'])): ?>
    <h6 style="margin-top: 15px; margin-bottom: 10px;"><?php _e('Files from Metadata', 'bandfront-player'); ?></h6>
    <table class="bfa-config-table">
        <thead>
            <tr>
                <th><?php _e('File', 'bandfront-player'); ?></th>
                <th><?php _e('Type', 'bandfront-player'); ?></th>
                <th><?php _e('Size', 'bandfront-player'); ?></th>
                <th><?php _e('Status', 'bandfront-player'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filesBySource['meta'] as $file): ?>
            <tr>
                <td class="bfa-path" style="word-break: break-all;">
                    <code><?php echo esc_html($file['filename']); ?></code>
                    <br>
                    <small style="color: #666;"><?php echo esc_html($file['path']); ?></small>
                </td>
                <td class="bfa-value">
                    <?php echo esc_html($file['type']); ?>
                </td>
                <td class="bfa-value">
                    <?php echo $file['exists'] ? esc_html($file['formatted_size']) : '<span class="bfa-empty">N/A</span>'; ?>
                </td>
                <td class="bfa-value">
                    <?php if ($file['exists']): ?>
                        <span class="bfa-bool-true">✓ Found</span>
                    <?php else: ?>
                        <span class="bfa-bool-false">✗ Missing</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php
}

/**
 * Render BFP settings in a collapsible section
 */
function bfp_render_bfp_settings_collapsible(array $settings): void {
    $unique_id = 'bfp-settings-' . uniqid();
    ?>
    <div class="bfp-collapsible-section" style="margin-top: 20px;">
        <input type="checkbox" id="<?php echo esc_attr($unique_id); ?>" class="bfp-collapsible-toggle" style="display: none;">
        <label for="<?php echo esc_attr($unique_id); ?>" class="bfp-collapsible-header" style="cursor: pointer; display: flex; align-items: center; justify-content: space-between; padding: 10px 0;">
            <span><?php _e('Bandfront Player Settings', 'bandfront-player'); ?> <small style="color: #666;">(<?php echo count($settings); ?> overrides)</small></span>
            <span class="dashicons dashicons-arrow-down-alt2" style="transition: transform 0.3s;"></span>
        </label>
        <?php if (empty($settings)): ?>
            <p class="bfa-no-settings"><?php _e('No Bandfront Player settings found for this product.', 'bandfront-player'); ?></p>
        <?php else: ?>
            <div class="bfp-collapsible-content" style="max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                <div style="padding-top: 10px;">
                    <table class="bfa-config-table">
                        <thead>
                            <tr>
                                <th><?php _e('Setting Key', 'bandfront-player'); ?></th>
                                <th><?php _e('Value', 'bandfront-player'); ?></th>
                                <th><?php _e('Type', 'bandfront-player'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($settings as $setting): ?>
                            <tr>
                                <td class="bfa-key">
                                    <code><?php echo esc_html($setting['key']); ?></code>
                                </td>
                                <td class="bfa-value"><?php echo $setting['formatted_value']; ?></td>
                                <td class="bfa-type">
                                    <span class="bfa-type-badge bfa-type-<?php echo esc_attr($setting['type']); ?>">
                                        <?php echo esc_html($setting['type']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render file system section
 */
function bfp_render_filesystem_section(): void {
    ?>
    <div class="bfa-schema-section" style="margin-top: 30px;">
        <h4 class="bfa-section-header bfa-collapsible" data-target="filesystem-info">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('File System & Directories', 'bandfront-player'); ?>
            <span class="bfa-toggle dashicons dashicons-arrow-down-alt2"></span>
        </h4>
        <div id="filesystem-info" class="bfa-schema-table" style="display: none;">
            <!-- Content will be filled by DbRenderer::renderFileSystemInfo() -->
        </div>
    </div>
    <?php
}

/**
 * Render directory structure table
 */
function bfp_render_directory_table(array $directories): void {
    ?>
    <h5><?php _e('Directory Structure', 'bandfront-player'); ?></h5>
    <table class="bfa-config-table">
        <thead>
            <tr>
                <th><?php _e('Directory', 'bandfront-player'); ?></th>
                <th><?php _e('Path', 'bandfront-player'); ?></th>
                <th><?php _e('Status', 'bandfront-player'); ?></th>
                <th><?php _e('Contents', 'bandfront-player'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($directories as $dir): ?>
            <tr>
                <td class="bfa-key">
                    <code><?php echo esc_html($dir['name']); ?></code>
                </td>
                <td class="bfa-path" style="word-break: break-all;">
                    <?php echo esc_html($dir['path']); ?>
                </td>
                <td class="bfa-value">
                    <?php if ($dir['exists']): ?>
                        <?php if ($dir['writable']): ?>
                            <span class="bfa-bool-true">✓ Writable</span>
                        <?php else: ?>
                            <span class="bfa-bool-false">✗ Read-only</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="bfa-empty">Missing</span>
                    <?php endif; ?>
                </td>
                <td class="bfa-value">
                    <?php if ($dir['exists'] && $dir['file_count'] > 0): ?>
                        <?php echo sprintf('%s files (%s)', $dir['file_count'], esc_html($dir['formatted_size'])); ?>
                    <?php elseif ($dir['exists']): ?>
                        <span class="bfa-empty">Empty</span>
                    <?php else: ?>
                        <span class="bfa-empty">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Render file type statistics table
 */
function bfp_render_file_stats_table(array $file_stats): void {
    if (empty($file_stats)) return;
    ?>
    <h5 style="margin-top: 20px;"><?php _e('File Types Overview', 'bandfront-player'); ?></h5>
    <table class="bfa-config-table">
        <thead>
            <tr>
                <th><?php _e('Extension', 'bandfront-player'); ?></th>
                <th><?php _e('Count', 'bandfront-player'); ?></th>
                <th><?php _e('Total Size', 'bandfront-player'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($file_stats as $ext => $stats): ?>
            <tr>
                <td class="bfa-key"><code>.<?php echo esc_html($ext); ?></code></td>
                <td class="bfa-value"><?php echo number_format($stats['count']); ?></td>
                <td class="bfa-value"><?php echo esc_html($stats['formatted_size']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Render schema section group
 */
function bfp_render_schema_group(string $group_name, string $icon, array $items): void {
    ?>
    <div class="bfa-schema-section">
        <h4 class="bfa-section-header">
            <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
            <?php echo esc_html($group_name); ?>
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
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="bfa-key">
                            <code><?php echo esc_html($item['key']); ?></code>
                            <?php if (!empty($item['config']['label'])): ?>
                                <span class="bfa-label"><?php echo esc_html($item['config']['label']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="bfa-value"><?php echo $item['formatted_value']; ?></td>
                        <td class="bfa-type">
                            <span class="bfa-type-badge bfa-type-<?php echo esc_attr($item['type']); ?>">
                                <?php echo esc_html($item['type']); ?>
                            </span>
                        </td>
                        <td class="bfa-default">
                            <?php echo $item['formatted_default']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render raw configuration dump
 */
function bfp_render_raw_dump(array $all_settings): void {
    ?>
    <div class="bfa-schema-section">
        <h4 class="bfa-section-header bfa-collapsible" data-target="raw-values">
            <span class="dashicons dashicons-editor-code"></span>
            <?php _e('Raw Configuration Dump', 'bandfront-player'); ?>
            <span class="bfa-toggle dashicons dashicons-arrow-down-alt2"></span>
        </h4>
        <div id="raw-values" class="bfa-schema-raw" style="display: none;">
            <pre class="bfa-code-block"><?php 
                echo esc_html(json_encode($all_settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            ?></pre>
        </div>
    </div>
    <?php
}
