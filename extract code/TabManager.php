<?php
namespace bfa\UI;

use bfa\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages tabbed interface for admin pages
 */
class TabManager {
    
    private Plugin $plugin;
    private array $tabs = [];
    private string $currentTab = '';
    
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Register a tab
     */
    public function registerTab(string $id, string $title, callable $callback, int $priority = 10): void {
        $this->tabs[$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback,
            'priority' => $priority,
        ];
    }
    
    /**
     * Get current active tab
     */
    public function getCurrentTab(): string {
        if (empty($this->currentTab)) {
            $this->currentTab = sanitize_key($_GET['tab'] ?? '');
            if (empty($this->currentTab) || !isset($this->tabs[$this->currentTab])) {
                $tabKeys = array_keys($this->getSortedTabs());
                $this->currentTab = reset($tabKeys);
            }
        }
        return $this->currentTab;
    }
    
    /**
     * Get sorted tabs by priority
     */
    private function getSortedTabs(): array {
        uasort($this->tabs, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        return $this->tabs;
    }
    
    /**
     * Render tab navigation
     */
    public function renderNavigation(string $baseUrl): void {
        $tabs = $this->getSortedTabs();
        $currentTab = $this->getCurrentTab();
        
        echo '<nav class="nav-tab-wrapper bfa-nav-tab-wrapper">';
        foreach ($tabs as $tab) {
            $url = add_query_arg('tab', $tab['id'], $baseUrl);
            $class = ($tab['id'] === $currentTab) ? 'nav-tab nav-tab-active' : 'nav-tab';
            printf(
                '<a href="%s" class="%s">%s</a>',
                esc_url($url),
                esc_attr($class),
                esc_html($tab['title'])
            );
        }
        echo '</nav>';
    }
    
    /**
     * Render current tab content
     */
    public function renderContent(): void {
        $currentTab = $this->getCurrentTab();
        if (isset($this->tabs[$currentTab]['callback'])) {
            call_user_func($this->tabs[$currentTab]['callback']);
        }
    }
    
    /**
     * Check if a tab exists
     */
    public function hasTab(string $id): bool {
        return isset($this->tabs[$id]);
    }
}
