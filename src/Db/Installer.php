<?php
declare(strict_types=1);

namespace Bandfront\Db;

use Bandfront\Utils\Debug;

// Set domain for Db
Debug::domain('db');
error_log("[BFP] Installer.php file loaded");

/**
 * Database Installer
 * 
 * Handles database table creation, updates, and schema versioning
 * following WordPress 2025 best practices
 * 
 * @package Bandfront\Db
 * @since 2.0.0
 */
class Installer {
    
    private static string $version = '2.5.0';
    private static string $version_option = 'bfp_db_version';
    
    /**
     * Install/Update database schema
     */
    public static function install(): void {
        error_log("[BFP] Installer::install() method called");
        
        $installed_version = get_option(self::$version_option, '0.0.0');
        
        if (version_compare($installed_version, self::$version, '<')) {
            error_log("Upgrading database from {$installed_version} to " . self::$version);
            
            // Run migrations if needed
            if (version_compare($installed_version, '2.4.0', '<')) {
                self::migrateToNestedDemos();
            }
            
            // Migrate from complex nested to simple structure
            if (version_compare($installed_version, '2.5.0', '<')) {
                self::migrateToSimpleStructure();
            }
            
            // Ensure core settings exist
            self::ensureCoreSettings();
            
            self::createTables();
            self::updateVersion();
        } else {
            error_log('Database already up to date: ' . $installed_version);
        }
    }
    
    /**
     * Migrate to new nested demos structure
     */
    private static function migrateToNestedDemos(): void {
        error_log('Migrating to nested demos structure');
        
        $global_settings = get_option('bfp_global_settings', []);
        
        // Only migrate if old structure exists and new doesn't
        if (!isset($global_settings['_bfp_demos']) && 
            (isset($global_settings['_bfp_play_demos']) || 
             isset($global_settings['_bfp_demo_duration_percent']) ||
             isset($global_settings['_bfp_fade_out']))) {
            
            // Create new nested structure
            $global_settings['_bfp_demos'] = [
                'global' => [
                    'enabled' => isset($global_settings['_bfp_play_demos']) ? (bool)$global_settings['_bfp_play_demos'] : false,
                    'duration_percent' => isset($global_settings['_bfp_demo_duration_percent']) ? 
                        max(1, min(100, (int)$global_settings['_bfp_demo_duration_percent'])) : 50,
                    'demo_fade' => isset($global_settings['_bfp_fade_out']) ? 
                        max(0, min(10, (float)$global_settings['_bfp_fade_out'])) : 0,
                    'demo_filetype' => 'mp3', // Default for migrated settings
                    'demo_start_time' => 0,
                    'message' => isset($global_settings['_bfp_demo_message']) ? 
                        sanitize_textarea_field($global_settings['_bfp_demo_message']) : '',
                ],
                'product' => [
                    'use_custom' => false,
                    'skip_processing' => false,
                    'demos_list' => []
                ]
            ];
            
            // Remove old settings
            unset(
                $global_settings['_bfp_play_demos'],
                $global_settings['_bfp_demo_duration_percent'], 
                $global_settings['_bfp_fade_out'],
                $global_settings['_bfp_demo_message'],
                $global_settings['_bfp_use_custom_demos'],
                $global_settings['_bfp_direct_demo_links'],
                $global_settings['_bfp_demos_list']
            );
            
            update_option('bfp_global_settings', $global_settings);
            error_log('BFP: Migrated demo settings to nested _bfp_demos structure');
        }
        
        // Migrate product-level demo settings
        self::migrateProductDemoSettings();
    }
    
    /**
     * Migrate from complex nested structure to simple two-setting approach
     */
    private static function migrateToSimpleStructure(): void {
        error_log('Migrating from nested to simple demo structure');
        
        $global_settings = get_option('bfp_global_settings', []);
        
        // If we have the nested structure, flatten it
        if (isset($global_settings['_bfp_demos']) && is_array($global_settings['_bfp_demos'])) {
            $nested = $global_settings['_bfp_demos'];
            
            // Extract global demo settings to flat structure
            if (isset($nested['global']) && is_array($nested['global'])) {
                $global_settings['_bfp_demos'] = [
                    'enabled' => $nested['global']['enabled'] ?? false,
                    'duration_percent' => $nested['global']['duration_percent'] ?? 50,
                    'demo_fade' => $nested['global']['demo_fade'] ?? 0,
                    'demo_filetype' => $nested['global']['demo_filetype'] ?? 'mp3',
                    'demo_start_time' => $nested['global']['demo_start_time'] ?? 0,
                    'message' => $nested['global']['message'] ?? '',
                ];
            } else {
                // Create default if global section missing
                $global_settings['_bfp_demos'] = [
                    'enabled' => false,
                    'duration_percent' => 50,
                    'demo_fade' => 0,
                    'demo_filetype' => 'mp3',
                    'demo_start_time' => 0,
                    'message' => '',
                ];
            }
            
            update_option('bfp_global_settings', $global_settings);
            error_log('BFP: Migrated global demos to simple flat structure');
        }
        
        // Migrate product-level settings
        self::migrateProductDemoSettingsToSimple();
    }
    
    /**
     * Migrate product-level demo settings to simple structure
     */
    private static function migrateProductDemoSettingsToSimple(): void {
        global $wpdb;
        
        // Get all products with nested demo settings
        $products = $wpdb->get_results("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_bfp_demos'
        ");
        
        foreach ($products as $product) {
            $product_id = $product->post_id;
            $nested_demos = get_post_meta($product_id, '_bfp_demos', true);
            
            if (is_array($nested_demos) && isset($nested_demos['product'])) {
                // Extract product settings to new structure
                $product_demos = [
                    'use_custom' => $nested_demos['product']['use_custom'] ?? false,
                    'skip_processing' => $nested_demos['product']['skip_processing'] ?? false,
                    'demos_list' => $nested_demos['product']['demos_list'] ?? []
                ];
                
                // Save as new separate setting
                update_post_meta($product_id, '_bfp_product_demos', $product_demos);
                
                // Also migrate any global overrides at product level
                if (isset($nested_demos['global']) && is_array($nested_demos['global'])) {
                    $global_overrides = $nested_demos['global'];
                    // Only save non-empty overrides
                    if (!empty(array_filter($global_overrides))) {
                        update_post_meta($product_id, '_bfp_demos', $global_overrides);
                    } else {
                        // Remove empty override
                        delete_post_meta($product_id, '_bfp_demos');
                    }
                } else {
                    // Remove the old nested structure
                    delete_post_meta($product_id, '_bfp_demos');
                }
                
                error_log("Migrated demo settings for product {$product_id} to simple structure");
            }
        }
    }
    
    /**
     * Migrate product-level demo settings to nested structure
     */
    private static function migrateProductDemoSettings(): void {
        global $wpdb;
        
        // Get all products with old demo settings
        $products = $wpdb->get_results("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key IN ('_bfp_play_demos', '_bfp_use_custom_demos', '_bfp_demos_list')
        ");
        
        foreach ($products as $product) {
            $product_id = $product->post_id;
            
            // Get existing nested structure or create new
            $demos = get_post_meta($product_id, '_bfp_demos', true);
            if (!is_array($demos)) {
                $demos = [
                    'global' => [],
                    'product' => []
                ];
            }
            
            // Migrate old product settings
            $use_custom = get_post_meta($product_id, '_bfp_use_custom_demos', true);
            $skip_processing = get_post_meta($product_id, '_bfp_direct_demo_links', true);
            $demos_list = get_post_meta($product_id, '_bfp_demos_list', true);
            
            if ($use_custom || $skip_processing || !empty($demos_list)) {
                $demos['product'] = [
                    'use_custom' => (bool)$use_custom,
                    'skip_processing' => (bool)$skip_processing,
                    'demos_list' => is_array($demos_list) ? $demos_list : []
                ];
                
                update_post_meta($product_id, '_bfp_demos', $demos);
                error_log("Migrated demo settings for product {$product_id}");
            }
        }
    }
    
    /**
     * Ensure core settings exist with defaults
     */
    private static function ensureCoreSettings(): void {
        $global_settings = get_option('bfp_global_settings', []);
        
        $defaults = [
            '_bfp_enable_player' => true,
            '_bfp_player_layout' => 'minimal',
            '_bfp_player_controls' => 'standard',
            '_bfp_button_theme' => 'custom',
            '_bfp_audio_engine' => 'html5',
            '_bfp_player_on_cover' => false,
            '_bfp_show_navigation_buttons' => true,
            '_bfp_show_purchasers' => true,
            '_bfp_max_purchasers_display' => 10,
            '_bfp_demos' => [
                'enabled' => false,
                'duration_percent' => 50,
                'demo_fade' => 0,
                'demo_filetype' => 'mp3',
                'demo_start_time' => 0,
                'message' => '',
            ]
        ];
        
        foreach ($defaults as $key => $default_value) {
            if (!isset($global_settings[$key])) {
                $global_settings[$key] = $default_value;
            }
        }
        
        update_option('bfp_global_settings', $global_settings);
    }
    
    /**
     * Create database tables
     */
    private static function createTables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Player configurations table
        $player_table = $wpdb->prefix . 'bfp_player';
        $player_sql = "CREATE TABLE $player_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            player_name varchar(250) NOT NULL,
            config longtext,
            playlist longtext,
            version varchar(20) DEFAULT '2.0.0',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY player_name (player_name),
            KEY created_at (created_at),
            KEY version (version)
        ) $charset_collate;";
        
        dbDelta($player_sql);
        
        // Analytics table
        $analytics_table = $wpdb->prefix . 'bfp_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            product_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            track_id varchar(255) DEFAULT NULL,
            track_name varchar(255) DEFAULT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            session_id varchar(128) DEFAULT NULL,
            referrer varchar(255) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY timestamp (timestamp),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        dbDelta($analytics_sql);
        
        error_log('Tables created/updated successfully');
    }
    
    /**
     * Update database version
     */
    private static function updateVersion(): void {
        update_option(self::$version_option, self::$version);
        error_log('Database version updated to ' . self::$version);
    }
    
    /**
     * Run activation tasks
     */
    public static function activate(): void {
        error_log('[BFP] Plugin activation triggered');
        
        // Set activation timestamp
        update_option('bandfront_player_activated', time());
        
        // Run install/update
        self::install();
        
        // Clear any cached data
        wp_cache_flush();
        
        error_log('[BFP] Plugin activation completed');
    }
    
    /**
     * Run deactivation tasks
     */
    public static function deactivate(): void {
        error_log('[BFP] Plugin deactivation triggered');
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bfp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bfp_%'");
        
        error_log('[BFP] Plugin deactivation completed');
    }
    
    /**
     * Uninstall - Clean up database
     */
    public static function uninstall(): void {
        global $wpdb;
        
        // Only drop tables if user confirms data deletion
        if (get_option('bfp_delete_data_on_uninstall', false)) {
            error_log('Starting database cleanup');
            
            // Drop tables
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bfp_analytics");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bfp_player");
            
            // Delete options
            delete_option('bfp_global_settings');
            delete_option(self::$version_option);
            delete_option('bandfront_player_activated');
            
            // Clean up transients
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bfp_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bfp_%'");
            
            // Clean up postmeta
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_bfp_%'");
            
            error_log('Database cleanup completed');
        } else {
            error_log('Database cleanup skipped - user data preserved');
        }
    }
    
    /**
     * Migrate from old plugin structure (alias for migration compatibility)
     */
    public static function migrateFromOldStructure(): void {
        // This method is called by BfpActivation.php for backward compatibility
        // The actual migration logic is now handled in install() method
        error_log('[BFP] migrateFromOldStructure() called - migration handled by install()');
    }
    
    /**
     * Get database status for admin
     */
    public static function getStatus(): array {
        global $wpdb;
        
        $status = [
            'version' => get_option(self::$version_option, 'Not installed'),
            'latest_version' => self::$version,
            'tables' => [],
            'options_count' => 0,
            'postmeta_count' => 0,
        ];
        
        // Check tables
        $tables = [
            'bfp_player' => $wpdb->prefix . 'bfp_player',
            'bfp_analytics' => $wpdb->prefix . 'bfp_analytics',
        ];
        
        foreach ($tables as $key => $table_name) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_name") : 0;
            
            $status['tables'][$key] = [
                'exists' => $exists,
                'count' => (int)$count,
                'name' => $table_name
            ];
        }
        
        // Count options and postmeta
        $status['options_count'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'bfp_%' OR option_name LIKE '_bfp_%'");
        $status['postmeta_count'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE '_bfp_%'");
        
        return $status;
    }
}
