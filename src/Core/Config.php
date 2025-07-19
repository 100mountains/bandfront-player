<?php
declare(strict_types=1);

namespace Bandfront\Core;

use Bandfront\Utils\Debug;

// Set domain for Core
Debug::domain('core-config');

/**
 * Configuration and State Management
 * 
 * Provides context-aware state management with automatic inheritance:
 * Product Setting → Global Setting → Default Value
 * 
 * @package Bandfront\Core
 * @since 2.0.0
 */
class Config {
   
   private array $productsAttrs = [];
   private array $globalAttrs = [];
   private array $playerLayouts = ['dark', 'light', 'custom'];
   private array $playerControls = ['button', 'all', 'default'];
   private array $defaults = [];

   private array $overridableSettings = [
       '_bfp_enable_player' => false,
       '_bfp_audio_engine' => 'html5',
       '_bfp_single_player' => 0,
       '_bfp_merge_in_grouped' => 0,
       '_bfp_play_all' => 0,
       '_bfp_loop' => 0,
       '_bfp_player_volume' => 1.0,
       '_bfp_secure_player' => false,
       '_bfp_file_percent' => 50,
       '_bfp_own_demos' => 0,
       '_bfp_direct_own_demos' => 0,
       '_bfp_demos_list' => [],
   ];

   private array $globalOnlySettings = [
       '_bfp_player_layout' => 'dark',
       '_bfp_player_controls' => 'default',
       '_bfp_player_title' => 1,
       '_bfp_on_cover' => 1,
       '_bfp_force_main_player_in_title' => 1,
       '_bfp_players_in_cart' => false,
       '_bfp_play_simultaneously' => 0,
       '_bfp_registered_only' => 0,
       '_bfp_purchased' => 0,
       '_bfp_reset_purchased_interval' => 'daily',
       '_bfp_fade_out' => 0,
       '_bfp_purchased_times_text' => '- purchased %d time(s)',
       '_bfp_message' => '',
       '_bfp_ffmpeg' => 0,
       '_bfp_ffmpeg_path' => '',
       '_bfp_ffmpeg_watermark' => '',
       '_bfp_onload' => false,
       '_bfp_analytics_integration' => 'ua',
       '_bfp_analytics_property' => '',
       '_bfp_analytics_api_secret' => '',
       '_bfp_enable_visualizations' => 0,
       '_bfp_modules_enabled' => [
           'audio-engine' => true,
           'cloud-engine' => true,
       ],
       '_bfp_dev_mode' => 0,
       '_bfp_debug' => [
           'enabled' => false,
           'domains' => [
               'admin' => false,
               'bootstrap' => false,
               'ui' => false,
               'filemanager' => false,
               'audio' => false,
               'api' => false,
           ]
       ],
   ];

   // Add runtime state storage
   private array $runtimeState = [
       '_bfp_purchased_product_flag' => false,
       '_bfp_force_purchased_flag' => 0,
       '_bfp_current_user_downloads' => null,
   ];

   /**
    * Constructor - No dependencies needed
    */
   public function __construct() {
       // Initialize settings and structure
       $this->init();
   }

   /**
    * Initialize default settings and structure
    */
   private function init(): void {
       // Default values for settings (don't reassign the arrays above)
       $this->defaults = [
           '_bfp_registered_only' => 0,
           '_bfp_purchased' => 0,
           '_bfp_reset_purchased_interval' => 'daily',
           '_bfp_fade_out' => 0,
           '_bfp_purchased_times_text' => 'Purchased %d time(s)',
           '_bfp_ffmpeg' => 0,
           '_bfp_ffmpeg_path' => '',
           '_bfp_ffmpeg_watermark' => '',
           '_bfp_enable_player' => 1,
           '_bfp_players_in_cart' => 0,
           '_bfp_player_layout' => 'dark',
           '_bfp_player_volume' => 1,
           '_bfp_single_player' => 0,
           '_bfp_secure_player' => 0,
           '_bfp_player_controls' => 'default',
           '_bfp_file_percent' => 30,
           '_bfp_merge_in_grouped' => 0,
           '_bfp_play_all' => 0,
           '_bfp_loop' => 0,
           '_bfp_on_cover' => 0,
           '_bfp_message' => '',
           '_bfp_default_extension' => 0,
           '_bfp_force_main_player_in_title' => 0,
           '_bfp_ios_controls' => 0,
           '_bfp_onload' => 0,
           '_bfp_disable_302' => 0,
           '_bfp_analytics_integration' => 'ua',
           '_bfp_analytics_property' => '',
           '_bfp_analytics_api_secret' => '',
           '_bfp_apply_to_all_players' => 0,
           '_bfp_audio_engine' => 'html5',
           '_bfp_enable_visualizations' => 0,
           '_bfp_own_demos' => 0,
           '_bfp_direct_own_demos' => 0,
           '_bfp_demos_list' => [],
           '_bfp_dev_mode' => 0,
           '_bfp_debug' => [
               'enabled' => false,
               'domains' => [
                   'admin' => false,
                   'bootstrap' => false,
                   'ui' => false,
                   'filemanager' => false,
                   'audio' => false,
                   'api' => false,
               ]
           ],
           '_bfp_cloud_active_tab' => 'google-drive',
           // Debug category settings
           '_bfp_debug_mode' => 0,
           '_bfp_debug_admin' => 0,
           '_bfp_debug_bootstrap' => 0,
           '_bfp_debug_ui' => 0,
           '_bfp_debug_filemanager' => 0,
           '_bfp_debug_audio' => 0,
           '_bfp_debug_api' => 0,
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
       ];
   }

   /**
    * Get a single state/setting value with inheritance handling
    * 
    * @param string $key Setting key
    * @param mixed $default Default value if not found
    * @param int|null $productId Product ID for context-aware retrieval
    * @return mixed Setting value
    */
   public function getState(string $key, mixed $default = null, ?int $productId = null): mixed {
       // Check runtime state first
       if (isset($this->runtimeState[$key])) {
           return $this->runtimeState[$key];
       }
       
       if ($default === null) {
           $default = $this->getDefaultValue($key);
       }

       if ($this->isGlobalOnly($key)) {
           return $this->getGlobalAttr($key, $default);
       }

       if ($productId && $this->isOverridable($key)) {
           if (isset($this->productsAttrs[$productId][$key])) {
               $value = $this->productsAttrs[$productId][$key];
               if ($this->isValidOverride($value, $key)) {
                   return apply_filters('bfp_state_value', $value, $key, $productId, 'product');
               }
           }

           if (metadata_exists('post', $productId, $key)) {
               $value = get_post_meta($productId, $key, true);

               $this->productsAttrs[$productId] ??= [];
               $this->productsAttrs[$productId][$key] = $value;

               if ($this->isValidOverride($value, $key)) {
                   return apply_filters('bfp_state_value', $value, $key, $productId, 'product');
               }
           }
       }

       $globalValue = $this->getGlobalAttr($key, $default);
       return $globalValue;
   }

   private function isValidOverride(mixed $value, string $key): bool {
       if ($key === '_bfp_audio_engine') {
           return !empty($value) &&
                  $value !== 'global' &&
                  in_array($value, ['mediaelement', 'wavesurfer', 'html5']);
       } elseif (in_array($key, ['_bfp_enable_player', '_bfp_secure_player', '_bfp_merge_in_grouped',
                                '_bfp_single_player', '_bfp_play_all', '_bfp_loop', '_bfp_own_demos',
                                '_bfp_direct_own_demos'])) {
           return $value === '1' || $value === 1 || $value === true;
       } elseif ($key === '_bfp_file_percent') {
           return is_numeric($value) && $value >= 0 && $value <= 100;
       } elseif ($key === '_bfp_player_volume') {
           return is_numeric($value) && $value >= 0 && $value <= 1;
       } elseif (is_numeric($value)) {
           return true;
       } elseif (is_array($value)) {
           return !empty($value);
       } else {
           return !empty($value) && $value !== 'global' && $value !== 'default';
       }
   }

   private function getDefaultValue(string $key): mixed {
       return $this->defaults[$key] ?? false;
   }

   private function isGlobalOnly(string $key): bool {
       return isset($this->globalOnlySettings[$key]);
   }

   private function isOverridable(string $key): bool {
       return isset($this->overridableSettings[$key]);
   }

   private function getGlobalAttr(string $key, mixed $default = null): mixed {
       if (empty($this->globalAttrs)) {
           $this->globalAttrs = get_option('bfp_global_settings', []);
       }
       
       if (!isset($this->globalAttrs[$key])) {
           $defaultValue = $this->getDefaultValue($key) !== false ? 
                          $this->getDefaultValue($key) : $default;
           $this->globalAttrs[$key] = $defaultValue;
       }
       
       return apply_filters('bfp_global_attr', $this->globalAttrs[$key], $key);
   }

   public function getAllSettings(?int $productId = null): array {
       $settings = [];
       $allKeys = array_merge(
           array_keys($this->globalOnlySettings),
           array_keys($this->overridableSettings)
       );

       foreach ($allKeys as $key) {
           $settings[$key] = $this->getState($key, null, $productId);
       }

       return apply_filters('bfp_all_settings', $settings, $productId);
   }

   /**
    * Bulk get multiple settings efficiently
    */
   public function getStates(array $keys, ?int $productId = null): array {
       $values = [];
       
       foreach ($keys as $key) {
           $values[$key] = $this->getState($key, null, $productId);
       }
       
       return $values;
   }
   
   /**
    * Update state value
    */
   public function updateState(string $key, mixed $value, ?int $productId = null): void {
       // Handle runtime state
       if (isset($this->runtimeState[$key])) {
           $this->runtimeState[$key] = $value;
           return;
       }
       
       if ($productId && $this->isOverridable($key)) {
           update_post_meta($productId, $key, $value);
           // Clear cache
           if (isset($this->productsAttrs[$productId][$key])) {
               $this->productsAttrs[$productId][$key] = $value;
           }
       } elseif (!$productId || $this->isGlobalOnly($key)) {
           $this->globalAttrs[$key] = $value;
           // Update in database will be handled by save method
       }
   }
   
   /**
    * Delete state value (remove override)
    */
   public function deleteState(string $key, int $productId): void {
       if ($this->isOverridable($key)) {
           delete_post_meta($productId, $key);
           // Clear cache
           if (isset($this->productsAttrs[$productId][$key])) {
               unset($this->productsAttrs[$productId][$key]);
           }
       }
   }
   
   /**
    * Save all global settings to database
    */
   public function saveGlobalSettings(): void {
       update_option('bfp_global_settings', $this->globalAttrs);
   }
   
   /**
    * Get all settings for admin forms with proper formatting
    */
   public function getAdminFormSettings(): array {
       // Define all settings with their defaults
       $settingsConfig = [
           // FFmpeg settings
           'ffmpeg' => ['key' => '_bfp_ffmpeg', 'type' => 'bool'],
           'ffmpeg_path' => ['key' => '_bfp_ffmpeg_path', 'type' => 'string'],
           'ffmpeg_watermark' => ['key' => '_bfp_ffmpeg_watermark', 'type' => 'string'],
           
           // Troubleshooting settings
           'force_main_player_in_title' => ['key' => '_bfp_force_main_player_in_title', 'type' => 'int'],
           'troubleshoot_onload' => ['key' => '_bfp_onload', 'type' => 'bool'],
           
           // Player settings
           'enable_player' => ['key' => '_bfp_enable_player', 'type' => 'bool'],
           'players_in_cart' => ['key' => '_bfp_players_in_cart', 'type' => 'bool'],
           'player_style' => ['key' => '_bfp_player_layout', 'type' => 'string'],
           'volume' => ['key' => '_bfp_player_volume', 'type' => 'float'],
           'player_controls' => ['key' => '_bfp_player_controls', 'type' => 'string'],
           'single_player' => ['key' => '_bfp_single_player', 'type' => 'bool'],
           'secure_player' => ['key' => '_bfp_secure_player', 'type' => 'bool'],
           'file_percent' => ['key' => '_bfp_file_percent', 'type' => 'int'],
           'player_title' => ['key' => '_bfp_player_title', 'type' => 'int'],
           'merge_grouped' => ['key' => '_bfp_merge_in_grouped', 'type' => 'int'],
           'play_simultaneously' => ['key' => '_bfp_play_simultaneously', 'type' => 'int'],
           'play_all' => ['key' => '_bfp_play_all', 'type' => 'int'],
           'loop' => ['key' => '_bfp_loop', 'type' => 'int'],
           'on_cover' => ['key' => '_bfp_on_cover', 'type' => 'int'],
           
           // Analytics settings - NOTE: Playback analytics now sent via REST API to external analytics plugin
           'analytics_integration' => ['key' => '_bfp_analytics_integration', 'type' => 'string'],
           'analytics_property' => ['key' => '_bfp_analytics_property', 'type' => 'string'],
           'analytics_api_secret' => ['key' => '_bfp_analytics_api_secret', 'type' => 'string'],
           
           // General settings
           'message' => ['key' => '_bfp_message', 'type' => 'string'],
           'registered_only' => ['key' => '_bfp_registered_only', 'type' => 'int'],
           'purchased' => ['key' => '_bfp_purchased', 'type' => 'int'],
           'reset_purchased_interval' => ['key' => '_bfp_reset_purchased_interval', 'type' => 'string'],
           'fade_out' => ['key' => '_bfp_fade_out', 'type' => 'int'],
           'purchased_times_text' => ['key' => '_bfp_purchased_times_text', 'type' => 'string'],
           'apply_to_all_players' => ['key' => '_bfp_apply_to_all_players', 'type' => 'int'],
           'dev_mode' => ['key' => '_bfp_dev_mode', 'type' => 'int'],  // Add dev mode
           
           // Audio engine settings
           'audio_engine' => ['key' => '_bfp_audio_engine', 'type' => 'string'],
           'enable_visualizations' => ['key' => '_bfp_enable_visualizations', 'type' => 'int'],
       ];
       
       // Get all keys
       $keys = [];
       foreach ($settingsConfig as $config) {
           $keys[] = $config['key'];
       }
       
       // Bulk fetch
       $rawSettings = $this->getStates($keys);
       
       // Format settings with the _bfp_ prefix for form compatibility
       $formattedSettings = [];
       foreach ($settingsConfig as $name => $config) {
           $value = $rawSettings[$config['key']] ?? null;
           
           // Apply type casting
           switch ($config['type']) {
               case 'bool':
                   $value = (bool) $value;
                   break;
               case 'int':
                   $value = intval($value);
                   break;
               case 'float':
                   $value = floatval($value);
                   break;
               case 'trim_int':
                   $value = intval(trim($value));
                   break;
               case 'string':
               default:
                   $value = (string) $value;
                   break;
           }
           
           // Use the full key with _bfp_ prefix for form field names
           $formattedSettings[$config['key']] = $value;
       }
       
       // Force on_cover to 1
       $formattedSettings['_bfp_on_cover'] = 1;
       
       return $formattedSettings;
   }
   
   /**
    * Get minimal player state for frontend/runtime use
    */
   public function getPlayerState(?int $productId = null): array {
       // Define the essential player settings needed for runtime
       $playerKeys = [
           '_bfp_enable_player',
           '_bfp_player_layout',
           '_bfp_player_controls',
           '_bfp_player_volume',
           '_bfp_single_player',
           '_bfp_secure_player',
           '_bfp_file_percent',
           '_bfp_play_all',
           '_bfp_loop',
           '_bfp_audio_engine',
           '_bfp_merge_in_grouped',
       ];
       
       // Use bulk fetch for efficiency
       $playerState = $this->getStates($playerKeys, $productId);
       
       // Apply any runtime-specific filters
       return apply_filters('bfp_player_state', $playerState, $productId);
   }
   
   /**
    * Update global attributes cache
    */
   public function updateGlobalAttrs(array $attrs): void {
       $this->globalAttrs = $attrs;
   }
   
   /**
    * Clear product attributes cache
    */
   public function clearProductAttrsCache(?int $productId = null): void {
       if ($productId === null) {
           $this->productsAttrs = [];
       } else {
           unset($this->productsAttrs[$productId]);
       }
   }
   
   /**
    * Get all global attributes
    */
   public function getAllGlobalAttrs(): array {
       if (empty($this->globalAttrs)) {
           $this->globalAttrs = get_option('bfp_global_settings', []);
       }
       return $this->globalAttrs;
   }
   
   /**
    * Get available player layouts
    */
   public function getPlayerLayouts(): array {
       return $this->playerLayouts;
   }
   
   /**
    * Get available player controls
    */
   public function getPlayerControls(): array {
       return $this->playerControls;
   }
   
   /**
    * Check if a module is enabled
    */
   public function isModuleEnabled(string $moduleName): bool {
       $modulesEnabled = $this->getState('_bfp_modules_enabled');
       return isset($modulesEnabled[$moduleName]) ? $modulesEnabled[$moduleName] : false;
   }
   
   /**
    * Enable or disable a module
    */
   public function setModuleState(string $moduleName, bool $enabled): void {
       $modulesEnabled = $this->getState('_bfp_modules_enabled');
       $modulesEnabled[$moduleName] = $enabled;
       $this->updateState('_bfp_modules_enabled', $modulesEnabled);
   }
   
   /**
    * Get all available modules and their states
    */
   public function getAllModules(): array {
       return $this->getState('_bfp_modules_enabled');
   }
   
   /**
    * Get supported post types
    * 
    * @return array Post types
    */
   public function getPostTypes(): array {
       return apply_filters('bfp_post_types', ['product']);
   }
   
   /**
    * Check if smart play context should show player
    * 
    * @param int $productId Product ID
    * @return bool
    */
   public function smartPlayContext(int $productId): bool {
       // Always show on single product pages
       if (function_exists('is_product') && is_product()) {
           return true;
       }
       
       // Get shop display settings
       $enablePlayer = $this->getState('_bfp_enable_player', true, $productId);
       $onCover = $this->getState('_bfp_on_cover', true); // Default to true for shop pages
       $playerControls = $this->getState('_bfp_player_controls', 'default');
       
       // Shop/archive pages logic
       if ((function_exists('is_shop') && is_shop()) || 
           (function_exists('is_product_category') && is_product_category()) ||
           (function_exists('is_product_tag') && is_product_tag())) {
           
           // For smart controls (default), respect on_cover setting
           // For button controls, also respect on_cover setting
           // For all controls, always show regardless of on_cover
           if ($playerControls === 'all') {
               return $enablePlayer;
           } else {
               return $enablePlayer && $onCover;
           }
       }
       
       // Don't show in other contexts by default
       return false;
   }
   
   /**
    * Get debug configuration
    * @return array
    */
   public function getDebugConfig(): array {
       return $this->getState('_bfp_debug', $this->defaults['_bfp_debug']);
   }

   /**
    * Check if debug is enabled for a specific domain
    * @param string $domain The debug domain
    * @return bool
    */
   public function isDebugEnabled(string $domain = ''): bool {
       $debug = $this->getDebugConfig();
       
       // Check if debug is enabled globally
       if (!$debug['enabled']) {
           return false;
       }
       
       // If no domain specified, return global state
       if (empty($domain)) {
           return true;
       }
       
       // Check specific domain
       $domain = strtolower($domain);
       return isset($debug['domains'][$domain]) && $debug['domains'][$domain];
   }

   /**
    * Update debug configuration
    * @param bool $enabled Global debug state
    * @param array $domains Domain states
    */
   public function updateDebugConfig(bool $enabled, array $domains): void {
       $this->updateState('_bfp_debug', [
           'enabled' => $enabled,
           'domains' => $domains
       ]);
   }
   
   /**
    * Define all configuration settings with their properties
    */
   private function defineSettings(): void {
       $this->settings = [
           // Playback settings
           '_bfp_enable_player' => [
               'default' => 1,
               'type' => 'boolean',
               'label' => __('Enable Player', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_audio_engine' => [
               'default' => 'html5',
               'type' => 'string',
               'label' => __('Audio Engine', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_single_player' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Single Player Mode', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_merge_in_grouped' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Merge in Grouped Player', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_play_all' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Play All in Playlist', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_loop' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Loop Playlist', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_player_volume' => [
               'default' => 1.0,
               'type' => 'float',
               'label' => __('Player Volume', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_secure_player' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Secure Player Links', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_file_percent' => [
               'default' => 50,
               'type' => 'int',
               'label' => __('File Percent for Streaming', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_own_demos' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Own Demos Only', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_direct_own_demos' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Direct Own Demos', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_demos_list' => [
               'default' => [],
               'type' => 'array',
               'label' => __('Demos List', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           
           // Player appearance settings
           '_bfp_player_layout' => [
               'default' => 'dark',
               'type' => 'string',
               'label' => __('Player Layout', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_player_controls' => [
               'default' => 'default',
               'type' => 'string',
               'label' => __('Player Controls', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_player_title' => [
               'default' => 1,
               'type' => 'boolean',
               'label' => __('Show Player Title', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_on_cover' => [
               'default' => 1,
               'type' => 'boolean',
               'label' => __('Show On Cover', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_force_main_player_in_title' => [
               'default' => 1,
               'type' => 'boolean',
               'label' => __('Force Main Player in Title', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           
           // Purchase and registration settings
           '_bfp_registered_only' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Registered Users Only', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_purchased' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Must Be Purchased', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_reset_purchased_interval' => [
               'default' => 'daily',
               'type' => 'string',
               'label' => __('Reset Purchased Interval', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_fade_out' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Fade Out on Purchase', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_purchased_times_text' => [
               'default' => 'Purchased %d time(s)',
               'type' => 'string',
               'label' => __('Purchased Times Text', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           
           // Message and notification settings
           '_bfp_message' => [
               'default' => '',
               'type' => 'string',
               'label' => __('Message', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           
           // FFmpeg settings
           '_bfp_ffmpeg' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Enable FFmpeg', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_ffmpeg_path' => [
               'default' => '',
               'type' => 'string',
               'label' => __('FFmpeg Path', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_ffmpeg_watermark' => [
               'default' => '',
               'type' => 'string',
               'label' => __('FFmpeg Watermark', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           
           // Analytics settings
           '_bfp_analytics_integration' => [
               'default' => 'ua',
               'type' => 'string',
               'label' => __('Analytics Integration', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_analytics_property' => [
               'default' => '',
               'type' => 'string',
               'label' => __('Analytics Property', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_analytics_api_secret' => [
               'default' => '',
               'type' => 'string',
               'label' => __('Analytics API Secret', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           
           // Cloud settings
           '_bfp_cloud_active_tab' => [
               'default' => 'google-drive',
               'type' => 'string',
               'label' => __('Active Cloud Tab', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_cloud_dropbox' => [
               'default' => [
                   'enabled' => false,
                   'access_token' => '',
                   'folder_path' => '/bandfront-demos',
               ],
               'type' => 'array',
               'label' => __('Dropbox Cloud Settings', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_cloud_s3' => [
               'default' => [
                   'enabled' => false,
                   'access_key' => '',
                   'secret_key' => '',
                   'bucket' => '',
                   'region' => 'us-east-1',
                   'path_prefix' => 'bandfront-demos/',
               ],
               'type' => 'array',
               'label' => __('S3 Cloud Settings', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           '_bfp_cloud_azure' => [
               'default' => [
                   'enabled' => false,
                   'account_name' => '',
                   'account_key' => '',
                   'container' => '',
                   'path_prefix' => 'bandfront-demos/',
               ],
               'type' => 'array',
               'label' => __('Azure Cloud Settings', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           
           // Developer settings
           '_bfp_dev_mode' => [
               'default' => 0,
               'type' => 'boolean',
               'label' => __('Developer Mode', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => true,
           ],
           'enable_db_monitoring' => [
               'default' => 1,
               'type' => 'boolean', 
               'label' => __('Enable Database Monitoring', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => false, // Shown in dev tools only
           ],

              // Debug settings 
           '_bfp_debug' => [
               'default' => [
                   'enabled' => true,
                   'domains' => [
                       'admin' => true,
                       'bootstrap' => true,
                       'ui' => true,
                       'filemanager' => true,
                       'audio' => true,
                       'api' => true,
                   ]
               ],
               'type' => 'array',
               'label' => __('Debug Configuration', 'bandfront-player'),
               'global_only' => true,
               'show_in_admin' => false, // Shown in dev tools only
           ],
       ];
   }
}