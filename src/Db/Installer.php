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
    
    private static string $version = '2.0.0';
    private static string $version_option = 'bfp_db_version';
    
    /**
     * Install/Update database schema
     */
    public static function install(): void {
        error_log("[BFP] Installer::install() method called");
        error_log('Starting database installation/update');
        
        $installed_version = get_option(self::$version_option, '0.0.0');
        
        if (version_compare($installed_version, self::$version, '<')) {
            error_log("Upgrading database from {$installed_version} to " . self::$version);
            
            self::createTables();
            self::ensureDefaultSettings();
            self::updateVersion();
            
            error_log('Database installation/update completed');
        } else {
            error_log('Database already up to date: ' . $installed_version);
        }
    }
    
    /**
     * Create all plugin tables
     */
    private static function createTables(): void {
        global $wpdb;
        
        // Main player configuration table (renamed from cpmp_player to bfp_player)
        $player_table = $wpdb->prefix . 'bfp_player';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $player_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            player_name varchar(250) NOT NULL DEFAULT '',
            config longtext,
            playlist longtext,
            version varchar(20) DEFAULT '2.0.0',
            status enum('active','inactive','draft') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY player_name (player_name),
            KEY status (status),
            KEY version (version)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        error_log("Created/updated table: $player_table");
        
        // Optional: Analytics table for enhanced tracking
        $analytics_table = $wpdb->prefix . 'bfp_analytics';
        
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            player_id mediumint(9),
            product_id bigint(20),
            user_id bigint(20) DEFAULT 0,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            session_id varchar(100),
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY player_id (player_id),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            FOREIGN KEY (player_id) REFERENCES $player_table(id) ON DELETE SET NULL
        ) $charset_collate;";

        dbDelta($analytics_sql);
        
        error_log("Created/updated table: $analytics_table");
    }
    
    /**
     * Ensure default global settings are in place
     * Based on Config.php defaults
     */
    private static function ensureDefaultSettings(): void {
        error_log('Ensuring default settings');
        
        $current_settings = get_option('bfp_global_settings', []);
        
        // Default settings from Config.php
        $default_settings = [
            // Player appearance
            '_bfp_player_layout' => 'dark',
            '_bfp_player_controls' => 'default',
            '_bfp_player_title' => 1,
            '_bfp_on_cover' => 1,
            '_bfp_force_main_player_in_title' => 1,
            '_bfp_players_in_cart' => false,
            '_bfp_allow_concurrent_audio' => 0,
            
            // Access control
            '_bfp_require_login' => 0,
            '_bfp_purchased' => 0,
            '_bfp_reset_purchased_interval' => 'daily',
            '_bfp_fade_out' => 0,
            '_bfp_purchased_times_text' => '- purchased %d time(s)',
            '_bfp_demo_message' => '',
            
            // Audio processing
            '_bfp_ffmpeg' => 0,
            '_bfp_ffmpeg_path' => '',
            '_bfp_ffmpeg_watermark' => '',
            '_bfp_onload' => false,
            
            // Analytics
            '_bfp_analytics_integration' => 'ua',
            '_bfp_analytics_property' => '',
            '_bfp_analytics_api_secret' => '',
            '_bfp_enable_visualizations' => 0,
            
            // Modules
            '_bfp_modules_enabled' => [
                'audio-engine' => true,
                'cloud-engine' => true,
            ],
            
            // Development
            '_bfp_dev_mode' => 0,
            '_bfp_debug' => [
                'enabled' => false,
                'domains' => [
                    'core' => false,
                    'core-bootstrap' => false,
                    'core-config' => false,
                    'core-hooks' => false,
                    'admin' => false,
                    'audio' => false,
                    'storage' => false,
                    'ui' => false,
                    'api' => false,
                    'db' => false,
                    'utils' => false,
                    'wordpress-elements' => false,
                    'woocommerce' => false,
                ]
            ],
            
            // Database monitoring
            'enable_db_monitoring' => false,
            
            // Cloud storage settings
            '_bfp_cloud_active_tab' => 'google-drive',
            '_bfp_cloud_dropbox' => [
                'enabled' => false,
                'access_token' => '',
                'folder_path' => '/bandfront-demos',
            ],
            '_bfp_cloud_s3' => [
                'enabled' => false,
                'access_key' => '',
                'secret_key' => '',
                'bucket' => '',
                'region' => 'us-east-1',
                'path_prefix' => 'bandfront-demos/',
            ],
            '_bfp_cloud_azure' => [
                'enabled' => false,
                'account_name' => '',
                'account_key' => '',
                'container' => '',
                'path_prefix' => 'bandfront-demos/',
            ],
            
            // Overridable defaults (these can be overridden per-product)
            '_bfp_enable_player' => 1,
            '_bfp_audio_engine' => 'html5',
            '_bfp_unified_player' => 1,
            '_bfp_group_cart_control' => 0,
            '_bfp_play_all' => 0,
            '_bfp_loop' => 0,
            '_bfp_player_volume' => 1.0,
            '_bfp_play_demos' => false,
            '_bfp_demo_duration_percent' => 30,
            '_bfp_use_custom_demos' => 0,
            '_bfp_direct_demo_links' => 0,
            '_bfp_demos_list' => [],
            
            // Additional settings from Config defaults
            '_bfp_default_extension' => 0,
            '_bfp_ios_controls' => 0,
            '_bfp_disable_302' => 0,
            '_bfp_apply_to_all_players' => 0,
        ];
        
        // Merge with existing settings, keeping user customizations
        $merged_settings = array_merge($default_settings, $current_settings);
        
        update_option('bfp_global_settings', $merged_settings);
        
        // Ensure other critical options
        if (!get_option('bfp_addon_player')) {
            update_option('bfp_addon_player', 'bfp_native');
        }
        
        if (!get_option('bfp_native_addon_skin')) {
            update_option('bfp_native_addon_skin', 'modern-skin');
        }
        
        error_log('Default settings ensured');
    }
    
    /**
     * Update database version
     */
    private static function updateVersion(): void {
        update_option(self::$version_option, self::$version);
        error_log('Updated database version to: ' . self::$version);
    }
    
    /**
     * Migrate data from old table structure if it exists
     */
    public static function migrateFromOldStructure(): void {
        global $wpdb;
        
        $old_table = $wpdb->prefix . 'cpmp_player';
        $new_table = $wpdb->prefix . 'bfp_player';
        
        // Check if old table exists and has data
        $old_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table'") === $old_table;
        $new_exists = $wpdb->get_var("SHOW TABLES LIKE '$new_table'") === $new_table;
        
        if ($old_exists && $new_exists) {
            $old_count = $wpdb->get_var("SELECT COUNT(*) FROM $old_table");
            $new_count = $wpdb->get_var("SELECT COUNT(*) FROM $new_table");
            
            if ($old_count > 0 && $new_count === '0') {
                error_log("Migrating $old_count records from $old_table to $new_table");
                
                $wpdb->query("
                    INSERT INTO $new_table (player_name, config, playlist, version, created_at)
                    SELECT player_name, config, playlist, '1.0.0', NOW()
                    FROM $old_table
                ");
                
                error_log('Migration completed successfully');
            }
        }
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
            
            // Clean up old table if it exists
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cpmp_player");
            
            // Delete options
            delete_option('bfp_global_settings');
            delete_option('bfp_addon_player');
            delete_option('bfp_native_addon_skin');
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