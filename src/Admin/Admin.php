<?php
declare(strict_types=1);

namespace Bandfront\Admin;

use Bandfront\Core\Config;
use Bandfront\Storage\FileManager;
use Bandfront\UI\AdminRenderer;
use Bandfront\Utils\Debug;

/**
 * Admin functionality coordinator
 * 
 * @package Bandfront\Admin
 * @since 2.0.0
 */
class Admin {
    
    private Config $config;
    private FileManager $fileManager;
    private AdminRenderer $renderer;
    private Settings $settings;
    private ProductMeta $productMeta;
    
    /**
     * Constructor
     */
    public function __construct(Config $config, FileManager $fileManager, AdminRenderer $renderer) {
        Debug::log('Admin.php:init', [
            'step' => 'start',
            'config' => get_class($config),
            'fileManager' => get_class($fileManager),
            'renderer' => get_class($renderer)
        ]); // DEBUG-REMOVE

        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->renderer = $renderer;
        
        // Create sub-components
        $this->settings = new Settings($config, $fileManager, $renderer);
        $this->productMeta = new ProductMeta($config, $fileManager);
        
        // Don't load templates in constructor - defer to adminInit
        Debug::log('Admin.php:init', [
            'step' => 'end',
            'sub_components_created' => true
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Admin initialization
     */
    public function adminInit(): void {
        Debug::log('Admin.php:adminInit', [
            'step' => 'setup',
            'action' => 'Initializing Bandfront Player admin',
            'woocommerce_installed' => class_exists('woocommerce')
        ]); // DEBUG-REMOVE

        // Don't load templates here - wait for proper hook

        // Check if WooCommerce is installed or not
        if (!class_exists('woocommerce')) {
            Debug::log('Admin.php: WooCommerce not installed, exiting adminInit()', []); // DEBUG-REMOVE
            return;
        }

        Debug::log('Admin.php: Clearing expired transients', []); // DEBUG-REMOVE
        $this->fileManager->clearExpiredTransients();

        // Register metabox through ProductMeta
        $this->productMeta->registerMetabox();

        Debug::log('Admin.php:adminInit', [
            'step' => 'done',
            'action' => 'Bandfront Player admin initialization complete'
        ]); // DEBUG-REMOVE
    }
    
    /**
     * Enqueue admin styles and scripts
     */
    public function enqueueAdminAssets(): void {
        Debug::log('Admin.php: Entering enqueueAdminAssets()', []); // DEBUG-REMOVE
        
        // Load view templates that may enqueue assets
        $this->loadViewTemplates();
        
        // Enqueue admin styles for our settings page
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_bandfront-player-settings') {
            wp_enqueue_style('bfp-admin', plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/style-admin.css', [], BFP_VERSION);
            wp_enqueue_style('bfp-admin-notices', plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/admin-notices.css', [], BFP_VERSION);
        }
        
        Debug::log('Admin.php: Exiting enqueueAdminAssets()', []); // DEBUG-REMOVE
    }
    
    /**
     * Load view templates that register hooks
     */
    private function loadViewTemplates(): void {
        Debug::log('Admin.php: Entering loadViewTemplates()', []); // DEBUG-REMOVE
        
        // Include audio engine settings template (registers hooks)
        Debug::log('Admin.php: Including audio-engine-settings.php', []); // DEBUG-REMOVE
        $templatePath = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/audio-engine-settings.php';
        
        if (file_exists($templatePath)) {
            require_once $templatePath;
        } else {
            Debug::log('Admin.php: audio-engine-settings.php not found at: ' . $templatePath, []); // DEBUG-REMOVE
        }
        
        Debug::log('Admin.php: Exiting loadViewTemplates()', []); // DEBUG-REMOVE
    }

    /**
     * Add admin menu - delegates to Settings
     */
    public function menuLinks(): void {
        Debug::log('Admin.php:menuLinks() called, delegating to Settings', []); // DEBUG-REMOVE
        $this->settings->registerMenu();
    }

    /**
     * Settings page callback - delegates to Settings
     */
    public function settingsPage(): void {
        Debug::log('Admin.php:settingsPage() called, delegating to Settings', []); // DEBUG-REMOVE
        $this->settings->renderPage();
    }

    /**
     * Save post meta data - delegates to ProductMeta
     */
    public function savePost(int $postId, \WP_Post $post, bool $update): void {
        Debug::log('Admin.php:savePost() called, delegating to ProductMeta', []); // DEBUG-REMOVE
        $this->productMeta->savePost($postId, $post, $update);
    }

    /**
     * After delete post callback
     */
    public function afterDeletePost(int $postId, \WP_Post $postObj): void {
        Debug::log('Admin.php: Entering afterDeletePost()', ['postId' => $postId]); // DEBUG-REMOVE
        $this->fileManager->deletePost($postId);
        Debug::log('Admin.php: Exiting afterDeletePost()', ['postId' => $postId]); // DEBUG-REMOVE
    }

    /**
     * Show admin notices - delegates to Settings
     */
    public function showAdminNotices(): void {
        Debug::log('Admin.php:showAdminNotices() called, delegating to Settings', []); // DEBUG-REMOVE
        $this->settings->showAdminNotices();
    }
    
    /**
     * AJAX handler for saving settings - delegates to Settings
     */
    public function ajaxSaveSettings(): void {
        Debug::log('Admin.php:ajaxSaveSettings() called', []); // DEBUG-REMOVE
        
        // Verify nonce
        if (!isset($_POST['bfp_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfp_nonce'])), 'bfp_updating_plugin_settings')) {
            Debug::log('Admin.php: AJAX security check failed', []); // DEBUG-REMOVE
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh the page and try again.', 'bandfront-player')
            ]);
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            Debug::log('Admin.php: AJAX permission check failed', []); // DEBUG-REMOVE
            wp_send_json_error([
                'message' => __('You do not have permission to change these settings.', 'bandfront-player')
            ]);
        }
        
        // Save settings using Settings component
        Debug::log('Admin.php: Saving global settings via AJAX', []); // DEBUG-REMOVE
        $messages = $this->settings->saveGlobalSettings();
        
        // Send success response with detailed message
        if (!empty($messages) && $messages['type'] === 'success') {
            Debug::log('Admin.php: AJAX settings save success', ['messages' => $messages]); // DEBUG-REMOVE
            wp_send_json_success([
                'message' => $messages['message'],
                'details' => isset($messages['details']) ? $messages['details'] : []
            ]);
        } else {
            Debug::log('Admin.php: AJAX settings save error', ['messages' => $messages]); // DEBUG-REMOVE
            wp_send_json_error([
                'message' => isset($messages['message']) ? $messages['message'] : __('An error occurred while saving settings.', 'bandfront-player')
            ]);
        }
    }
}
