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
        // Enqueue the external JavaScript file
        wp_enqueue_script(
            'bfp-db-monitor',
            plugins_url('assets/js/db-monitor.js', dirname(dirname(__DIR__)) . '/bandfront-player.php'),
            ['jquery'],
            BFP_VERSION,
            true
        );
        
        // Localize script with necessary data
        wp_localize_script('bfp-db-monitor', 'bfpDbMonitor', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bfp_db_actions'),
            'monitoring_enabled' => $this->config->getState('enable_db_monitoring', false),
            'strings' => [
                'no_activity' => __('No recent database activity...', 'bandfront-player'),
                'cleared' => __('Cleared. Waiting for new activity...', 'bandfront-player'),
                'confirm_clean' => __('Are you sure you want to delete all test data? This cannot be undone.', 'bandfront-player'),
                'pause' => __('Pause', 'bandfront-player'),
                'resume' => __('Resume', 'bandfront-player'),
                'live' => __('Live', 'bandfront-player'),
                'paused' => __('Paused', 'bandfront-player'),
                'cleaning' => __('Cleaning...', 'bandfront-player'),
                'optimizing' => __('Optimizing...', 'bandfront-player'),
                'exporting' => __('Exporting...', 'bandfront-player'),
                'scanning' => __('Scanning...', 'bandfront-player'),
            ]
        ]);
    }
    
    /**
     * Render Database Monitor CSS
     */
    private function renderDatabaseMonitorStyles(): void {
        // Enqueue the external CSS file
        wp_enqueue_style(
            'bfp-db-monitor',
            plugins_url('assets/css/db-monitor.css', dirname(dirname(__DIR__)) . '/bandfront-player.php'),
            [],
            BFP_VERSION
        );
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
    * Render schema content with comprehensive Config dump
    */
   private function renderSchemaContent(): void {
       // Get all configuration data using existing Config methods
       $allSettings = $this->config->getAllSettings();
       $globalAttrs = $this->config->getAllGlobalAttrs();
       
       // Extract the settings configuration from the Config class
       // We'll build this from the allSettings and known keys
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
               
               foreach ($allSettings as $key => $value) {
                   $item = [
                       'key' => $key,
                       'value' => $value,
                       'config' => $settingsConfig[$key] ?? ['type' => 'unknown', 'default' => null]
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
   
   /**
    * Build settings configuration from known settings
    */
   private function buildSettingsConfig(array $allSettings): array {
       $config = [];
       
       // Known type mappings
       $typeMap = [
           // Boolean settings
           '_bfp_enable_player' => ['type' => 'boolean', 'default' => true, 'label' => 'Enable Player'],
           '_bfp_secure_player' => ['type' => 'boolean', 'default' => false, 'label' => 'Secure Player'],
           '_bfp_merge_in_grouped' => ['type' => 'boolean', 'default' => false, 'label' => 'Merge in Grouped'],
           '_bfp_single_player' => ['type' => 'boolean', 'default' => false, 'label' => 'Single Player Mode'],
           '_bfp_play_all' => ['type' => 'boolean', 'default' => false, 'label' => 'Play All Tracks'],
           '_bfp_loop' => ['type' => 'boolean', 'default' => false, 'label' => 'Loop Playback'],
           '_bfp_fade_out' => ['type' => 'boolean', 'default' => false, 'label' => 'Fade Out'],
           '_bfp_registered_only' => ['type' => 'boolean', 'default' => false, 'label' => 'Registered Users Only'],
           '_bfp_purchased' => ['type' => 'boolean', 'default' => false, 'label' => 'Purchased Only'],
           '_bfp_players_in_cart' => ['type' => 'boolean', 'default' => false, 'label' => 'Show Players in Cart'],
           '_bfp_on_cover' => ['type' => 'boolean', 'default' => false, 'label' => 'Show on Cover'],
           '_bfp_ffmpeg' => ['type' => 'boolean', 'default' => false, 'label' => 'Enable FFmpeg'],
           '_bfp_own_demos' => ['type' => 'boolean', 'default' => false, 'label' => 'Use Own Demos'],
           '_bfp_direct_own_demos' => ['type' => 'boolean', 'default' => false, 'label' => 'Direct Demo Links'],
           '_bfp_debug_mode' => ['type' => 'boolean', 'default' => false, 'label' => 'Debug Mode'],
           '_bfp_dev_mode' => ['type' => 'boolean', 'default' => false, 'label' => 'Developer Mode'],
           '_bfp_enable_vis' => ['type' => 'boolean', 'default' => false, 'label' => 'Enable Visualizer'],
           'enable_db_monitoring' => ['type' => 'boolean', 'default' => false, 'label' => 'Database Monitoring'],
           
           // String settings
           '_bfp_audio_engine' => ['type' => 'string', 'default' => 'html5', 'label' => 'Audio Engine'],
           '_bfp_player_layout' => ['type' => 'string', 'default' => 'dark', 'label' => 'Player Layout'],
           '_bfp_player_controls' => ['type' => 'string', 'default' => 'modern', 'label' => 'Player Controls'],
           '_bfp_message' => ['type' => 'string', 'default' => '', 'label' => 'Custom Message'],
           '_bfp_purchased_times_text' => ['type' => 'string', 'default' => '', 'label' => 'Purchase Times Text'],
           '_bfp_analytics_integration' => ['type' => 'string', 'default' => '', 'label' => 'Analytics Integration'],
           '_bfp_ffmpeg_path' => ['type' => 'string', 'default' => '', 'label' => 'FFmpeg Path'],
           '_bfp_ffmpeg_watermark' => ['type' => 'string', 'default' => '', 'label' => 'Watermark File'],
           
           // Integer settings
           '_bfp_file_percent' => ['type' => 'integer', 'default' => 30, 'label' => 'Preview Percentage'],
           '_bfp_reset_purchased_interval' => ['type' => 'integer', 'default' => 0, 'label' => 'Reset Interval'],
           
           // Float settings
           '_bfp_player_volume' => ['type' => 'float', 'default' => 0.8, 'label' => 'Default Volume'],
           
           // Array settings
           '_bfp_cloud_dropbox' => ['type' => 'array', 'default' => [], 'label' => 'Dropbox Settings'],
           '_bfp_cloud_s3' => ['type' => 'array', 'default' => [], 'label' => 'S3 Settings'],
           '_bfp_cloud_azure' => ['type' => 'array', 'default' => [], 'label' => 'Azure Settings'],
       ];
       
       // Build config for all settings
       foreach ($allSettings as $key => $value) {
           if (isset($typeMap[$key])) {
               $config[$key] = $typeMap[$key];
           } else {
               // Infer type from value
               $type = 'string';
               if (is_bool($value) || $value === '1' || $value === '0' || $value === 1 || $value === 0) {
                   $type = 'boolean';
               } elseif (is_array($value)) {
                   $type = 'array';
               } elseif (is_numeric($value)) {
                   $type = strpos($value, '.') !== false ? 'float' : 'integer';
               }
               
               $config[$key] = [
                   'type' => $type,
                   'default' => null,
                   'label' => $this->humanizeKey($key)
               ];
           }
       }
       
       return $config;
   }
   
   /**
    * Convert setting key to human-readable label
    */
   private function humanizeKey(string $key): string {
       // Remove prefix
       $key = str_replace(['_bfp_', '_'], ' ', $key);
       
       // Capitalize words
       return ucwords(trim($key));
   }
}