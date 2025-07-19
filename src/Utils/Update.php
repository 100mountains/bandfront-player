<?php
namespace Bandfront\Utils;

use Bandfront\Plugin;
use Bandfront\Utils\Debug;

// Set domain for Utils
Debug::domain('utils');

/**
 * Auto Updater for Bandfront Player
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Auto Updater Class
 * Handles plugin auto-update functionality
 */
class Update {
    
    private string $currentVersion;
    private string $updatePath;
    private string $pluginSlug;
    private string $slug;
    private Plugin $mainPlugin;
    
    /**
     * Constructor
     */
    public function __construct(Plugin $mainPlugin) {
        $this->mainPlugin = $mainPlugin;
    }
    
    /**
     * Initialize the auto-updater
     */
    public function init(): void {
        add_action('admin_init', [$this, 'setupAutoUpdate'], 1);
    }
    
    /**
     * Setup auto update
     */
    public function setupAutoUpdate(): void {
        $pluginData = get_plugin_data(BFP_PLUGIN_PATH);
        $pluginVersion = $pluginData['Version'];
        $pluginSlug = BFP_PLUGIN_BASE_NAME;
        
        $this->currentVersion = $pluginVersion;
        $this->updatePath = '';
        $this->pluginSlug = $pluginSlug;
        
        list($t1, $t2) = explode('/', $pluginSlug);
        $this->slug = str_replace('.php', '', $t2);
    }
    
    /**
     * Check for updates
     */
    public function checkUpdate($transient): object {
        return $transient;
    }
    
    /**
     * Check plugin information
     */
    public function checkInfo(): bool {
        return false;
    }
    
    /**
     * Allow external host
     */
    public function allowExternalHost(): bool {
        return true;
    }
    
    /**
     * Get remote version
     */
    public function getRemoteVersion(): string|false {
        return false;
    }
    
    /**
     * Get remote information
     */
    public function getRemoteInformation(): array|false {
        return false;
    }
}