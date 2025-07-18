<?php
namespace bfa\Admin;

use bfa\Plugin;
use bfa\UI\SettingsRenderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface for Bandfront Analytics
 */
class Admin {
    
    private Plugin $plugin;
    private ?SettingsRenderer $settingsRenderer = null;
    // private Dashboard $dashboard;
    // private Reports $reports;
    // private Settings $settings;
    // Remove DatabaseMonitor from here - it will be handled in SettingsRenderer
    
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
        
        // Initialize admin components only if they exist
        // $this->dashboard = new Dashboard();
        // $this->reports = new Reports();
        // $this->settings = new Settings();
        // Remove DatabaseMonitor initialization
        
        $this->initHooks();
    }
    
    /**
     * Initialize admin hooks
     */
    private function initHooks(): void {
        add_action('admin_menu', [$this, 'addMenuPages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_ajax_bfa_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_bfa_get_api_traffic', [$this, 'ajaxGetApiTraffic']);
        add_action('wp_ajax_bfa_get_db_activity', [$this, 'ajaxGetDbActivity']);
        add_action('wp_ajax_bfa_generate_test_events', [$this, 'ajaxGenerateTestEvents']);
        add_action('wp_ajax_bfa_clean_test_events', [$this, 'ajaxCleanTestEvents']);
    }
    
    /**
     * Add admin menu pages
     */
    public function addMenuPages(): void {
        // Main analytics page
        add_menu_page(
            __('Analytics', 'bandfront-analytics'),
            __('Analytics', 'bandfront-analytics'),
            'manage_options',
            'bandfront-analytics',
            [$this, 'renderAnalyticsPage'],
            'dashicons-chart-bar',
            25
        );
        
        // Dashboard submenu (points to main page)
        add_submenu_page(
            'bandfront-analytics',
            __('Dashboard', 'bandfront-analytics'),
            __('Dashboard', 'bandfront-analytics'),
            'manage_options',
            'bandfront-analytics'
        );
        
        // Reports submenu - only if class exists
        // if (class_exists('bfa\Admin\Reports')) {
        //     add_submenu_page(
        //         'bandfront-analytics',
        //         __('Reports', 'bandfront-analytics'),
        //         __('Reports', 'bandfront-analytics'),
        //         'manage_options',
        //         'bandfront-analytics-reports',
        //         [$this->reports, 'render']
        //     );
        // }
        
        // Play Analytics submenu
        add_submenu_page(
            'bandfront-analytics',
            __('Play Analytics', 'bandfront-analytics'),
            __('Play Analytics', 'bandfront-analytics'),
            'manage_options',
            'bandfront-play-analytics',
            [$this, 'renderPlayAnalyticsPage']
        );
        
        // Member Analytics submenu
        add_submenu_page(
            'bandfront-analytics',
            __('Member Analytics', 'bandfront-analytics'),
            __('Member Analytics', 'bandfront-analytics'),
            'manage_options',
            'bandfront-member-analytics',
            [$this, 'renderMemberAnalyticsPage']
        );
        
        // Remove Database Monitor submenu - it's in settings
        
        // Settings submenu - render inline for now
        add_submenu_page(
            'bandfront-analytics',
            __('Analytics Settings', 'bandfront-analytics'),
            __('Settings', 'bandfront-analytics'),
            'manage_options',
            'bandfront-analytics-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets(string $hook): void {
        // Check if we're on any analytics page
        if (!strpos($hook, 'bandfront-analytics') && !strpos($hook, 'bandfront-play-analytics') && !strpos($hook, 'bandfront-member-analytics')) {
            return;
        }
        
        // Enqueue Chart.js
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0'
        );
        
        // Enqueue admin styles
        wp_enqueue_style(
            'bfa-admin',
            BFA_PLUGIN_URL . 'assets/css/admin.css',
            [],
            BFA_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'bfa-admin',
            BFA_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'chart-js'],
            BFA_VERSION,
            true
        );
        
        // Add settings-specific script
        if ($hook === 'analytics_page_bandfront-analytics-settings') {
            wp_enqueue_script(
                'bfa-settings',
                BFA_PLUGIN_URL . 'assets/js/settings.js',
                ['jquery'],
                BFA_VERSION,
                true
            );
        }
        
        wp_localize_script('bfa-admin', 'bfaAdmin', [
            'apiUrl' => rest_url('bandfront-analytics/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxNonce' => wp_create_nonce('bfa_ajax'),
        ]);
    }
    
    /**
     * Render analytics dashboard page
     */
    public function renderAnalyticsPage(): void {
        // Render inline for now instead of using Dashboard class
        $database = $this->plugin->getDatabase();
        $quickStats = $database->getQuickStats();
        
        ?>
        <div class="wrap bfa-analytics-wrap">
            <h1><?php echo esc_html__('Analytics Dashboard', 'bandfront-analytics'); ?></h1>
            
            <!-- Quick Stats - Now with 5 boxes -->
            <div class="bfa-stats-grid bfa-stats-grid-5">
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">👁️</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($quickStats['today_views']); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Views Today', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">🎵</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($quickStats['today_plays'] ?? 0); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Plays Today', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">👥</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($quickStats['today_visitors']); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Visitors Today', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">🟢</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($quickStats['active_users']); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Active Now', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <?php
                    $change = $quickStats['yesterday_views'] > 0 ? 
                              round((($quickStats['today_views'] - $quickStats['yesterday_views']) / $quickStats['yesterday_views']) * 100, 1) : 0;
                    $changeClass = $change >= 0 ? 'positive' : 'negative';
                    ?>
                    <div class="bfa-stat-icon">📈</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value bfa-change-<?php echo $changeClass; ?>">
                            <?php echo ($change >= 0 ? '+' : '') . $change; ?>%
                        </div>
                        <div class="bfa-stat-label"><?php esc_html_e('vs Yesterday', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Date Range Selector -->
            <div class="bfa-controls">
                <select id="bfa-date-range" class="bfa-date-range">
                    <option value="7"><?php esc_html_e('Last 7 days', 'bandfront-analytics'); ?></option>
                    <option value="30"><?php esc_html_e('Last 30 days', 'bandfront-analytics'); ?></option>
                    <option value="90"><?php esc_html_e('Last 90 days', 'bandfront-analytics'); ?></option>
                </select>
            </div>
            
            <!-- Main Chart -->
            <div class="bfa-chart-container">
                <canvas id="bfa-main-chart"></canvas>
            </div>
            
            <!-- Top Content -->
            <div class="bfa-top-content">
                <h2><?php esc_html_e('Top Content', 'bandfront-analytics'); ?></h2>
                <div id="bfa-top-posts">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render play analytics page
     */
    public function renderPlayAnalyticsPage(): void {
        $database = $this->plugin->getDatabase();
        $musicStats = $database->getMusicStats(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
        
        ?>
        <div class="wrap bfa-analytics-wrap">
            <h1><?php echo esc_html__('Play Analytics', 'bandfront-analytics'); ?></h1>
            
            <!-- Music Stats Summary -->
            <div class="bfa-stats-grid">
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">▶️</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($musicStats['total_plays']); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Total Plays', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">🎵</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($musicStats['unique_tracks']); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Unique Tracks', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">⏱️</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo gmdate("i:s", $musicStats['avg_duration']); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Avg. Play Duration', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Date Range Selector -->
            <div class="bfa-controls">
                <select id="bfa-play-date-range" class="bfa-date-range">
                    <option value="7"><?php esc_html_e('Last 7 days', 'bandfront-analytics'); ?></option>
                    <option value="30"><?php esc_html_e('Last 30 days', 'bandfront-analytics'); ?></option>
                    <option value="90"><?php esc_html_e('Last 90 days', 'bandfront-analytics'); ?></option>
                </select>
            </div>
            
            <!-- Play Chart -->
            <div class="bfa-chart-container">
                <canvas id="bfa-play-chart"></canvas>
            </div>
            
            <!-- Top Played Tracks -->
            <div class="bfa-top-content">
                <h2><?php esc_html_e('Top Played Tracks', 'bandfront-analytics'); ?></h2>
                <div id="bfa-top-tracks">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render member analytics page
     */
    public function renderMemberAnalyticsPage(): void {
        $database = $this->plugin->getDatabase();
        $memberStats = $database->getMemberStats(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
        
        ?>
        <div class="wrap bfa-analytics-wrap">
            <h1><?php echo esc_html__('Member Analytics', 'bandfront-analytics'); ?></h1>
            
            <!-- Member Stats Summary -->
            <div class="bfa-stats-grid">
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">👥</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($memberStats['total_members'] ?? 0); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Total Members', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">🆕</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($memberStats['new_members_week'] ?? 0); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('New This Week', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">⚡</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($memberStats['active_members'] ?? 0); ?></div>
                        <div class="bfa-stat-label"><?php esc_html_e('Active Members', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
                
                <div class="bfa-stat-card">
                    <div class="bfa-stat-icon">📊</div>
                    <div class="bfa-stat-content">
                        <div class="bfa-stat-value"><?php echo number_format($memberStats['engagement_rate'] ?? 0); ?>%</div>
                        <div class="bfa-stat-label"><?php esc_html_e('Engagement Rate', 'bandfront-analytics'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Date Range Selector -->
            <div class="bfa-controls">
                <select id="bfa-member-date-range" class="bfa-date-range">
                    <option value="7"><?php esc_html_e('Last 7 days', 'bandfront-analytics'); ?></option>
                    <option value="30"><?php esc_html_e('Last 30 days', 'bandfront-analytics'); ?></option>
                    <option value="90"><?php esc_html_e('Last 90 days', 'bandfront-analytics'); ?></option>
                </select>
            </div>
            
            <!-- Member Growth Chart -->
            <div class="bfa-chart-container">
                <h3><?php esc_html_e('Member Growth', 'bandfront-analytics'); ?></h3>
                <canvas id="bfa-member-chart"></canvas>
            </div>
            
            <!-- Two Column Layout -->
            <div class="bfa-two-column-layout">
                <!-- Member Activity -->
                <div class="bfa-column">
                    <div class="bfa-content-box">
                        <h2><?php esc_html_e('Member Activity', 'bandfront-analytics'); ?></h2>
                        <div id="bfa-member-activity">
                            <?php if (class_exists('BandfrontMembers')) : ?>
                                <!-- Will be populated via AJAX -->
                            <?php else : ?>
                                <div class="bfa-placeholder-box">
                                    <div class="bfa-placeholder-icon">🔌</div>
                                    <h4><?php esc_html_e('Connect Bandfront Members', 'bandfront-analytics'); ?></h4>
                                    <p><?php esc_html_e('Install and activate the Bandfront Members plugin to see detailed member activity analytics.', 'bandfront-analytics'); ?></p>
                                    <a href="<?php echo admin_url('plugin-install.php?s=bandfront+members&tab=search&type=term'); ?>" class="button button-primary">
                                        <?php esc_html_e('Install Members Plugin', 'bandfront-analytics'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Membership Tiers -->
                <div class="bfa-column">
                    <div class="bfa-content-box">
                        <h2><?php esc_html_e('Membership Tiers', 'bandfront-analytics'); ?></h2>
                        <div id="bfa-membership-tiers">
                            <?php if (class_exists('BandfrontMembers')) : ?>
                                <!-- Will be populated via AJAX -->
                            <?php else : ?>
                                <div class="bfa-placeholder-box">
                                    <div class="bfa-placeholder-icon">🎯</div>
                                    <h4><?php esc_html_e('Membership Tier Analytics', 'bandfront-analytics'); ?></h4>
                                    <p><?php esc_html_e('Track member distribution across different tiers, conversion rates, and tier-specific engagement metrics.', 'bandfront-analytics'); ?></p>
                                    <ul class="bfa-feature-list">
                                        <li>📈 Tier growth tracking</li>
                                        <li>💰 Revenue per tier</li>
                                        <li>🔄 Upgrade/downgrade patterns</li>
                                        <li>⏱️ Average tier duration</li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Member Content Engagement -->
            <div class="bfa-content-box">
                <h2><?php esc_html_e('Member Content Engagement', 'bandfront-analytics'); ?></h2>
                <div id="bfa-member-content-engagement">
                    <?php if (class_exists('BandfrontMembers')) : ?>
                        <!-- Will be populated via AJAX -->
                    <?php else : ?>
                        <div class="bfa-placeholder-box bfa-placeholder-box-wide">
                            <div class="bfa-placeholder-icon">📊</div>
                            <h4><?php esc_html_e('Content Performance Insights', 'bandfront-analytics'); ?></h4>
                            <p><?php esc_html_e('Understand how members interact with your exclusive content.', 'bandfront-analytics'); ?></p>
                            <div class="bfa-feature-grid">
                                <div class="bfa-feature-item">
                                    <span class="bfa-feature-icon">📄</span>
                                    <strong><?php esc_html_e('Most Viewed Content', 'bandfront-analytics'); ?></strong>
                                    <p><?php esc_html_e('Track which member-only posts get the most engagement', 'bandfront-analytics'); ?></p>
                                </div>
                                <div class="bfa-feature-item">
                                    <span class="bfa-feature-icon">⏰</span>
                                    <strong><?php esc_html_e('Time on Content', 'bandfront-analytics'); ?></strong>
                                    <p><?php esc_html_e('Measure how long members spend on exclusive content', 'bandfront-analytics'); ?></p>
                                </div>
                                <div class="bfa-feature-item">
                                    <span class="bfa-feature-icon">💬</span>
                                    <strong><?php esc_html_e('Member Interactions', 'bandfront-analytics'); ?></strong>
                                    <p><?php esc_html_e('Comments, likes, and shares from members', 'bandfront-analytics'); ?></p>
                                </div>
                                <div class="bfa-feature-item">
                                    <span class="bfa-feature-icon">🎵</span>
                                    <strong><?php esc_html_e('Music Access', 'bandfront-analytics'); ?></strong>
                                    <p><?php esc_html_e('Track member-only music plays and downloads', 'bandfront-analytics'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage(): void {
        if (!$this->settingsRenderer) {
            $this->settingsRenderer = new SettingsRenderer($this->plugin);
        }
        
        $this->settingsRenderer->render();
    }
    
    /**
     * AJAX handler for getting API traffic
     */
    public function ajaxGetApiTraffic(): void {
        check_ajax_referer('bfa_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }
        
        // Get recent API traffic from transient
        $traffic = get_transient('bfa_api_traffic') ?: [];
        
        wp_send_json_success([
            'traffic' => array_slice($traffic, -50), // Last 50 requests
        ]);
    }
    
    /**
     * AJAX handler for getting database activity
     */
    public function ajaxGetDbActivity(): void {
        check_ajax_referer('bfa_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }
        
        global $wpdb;
        
        // Get recent events from database
        $recent_events = $wpdb->get_results(
            "SELECT 
                event_type,
                object_id,
                object_type,
                value,
                created_at,
                user_agent_hash,
                referrer_domain
            FROM {$wpdb->prefix}bfa_events 
            ORDER BY created_at DESC 
            LIMIT 50"
        );
        
        // Format for display
        $activity = [];
        foreach ($recent_events as $event) {
            $activity[] = [
                'time' => human_time_diff(strtotime($event->created_at), current_time('timestamp')) . ' ago',
                'type' => $event->event_type,
                'object' => $event->object_type . '#' . $event->object_id,
                'value' => $event->value,
                'referrer' => $event->referrer_domain ?: 'direct',
            ];
        }
        
        wp_send_json_success([
            'activity' => $activity,
        ]);
    }
    
    /**
     * AJAX handler for generating test events
     */
    public function ajaxGenerateTestEvents(): void {
        check_ajax_referer('bfa_test_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }
        
        $database = $this->plugin->getDatabase();
        
        // Generate various test events
        $test_events = [
            ['pageview', 'post', rand(1, 100)],
            ['music_play', 'track', rand(1, 50)],
            ['download', 'file', rand(1, 30)],
            ['user_login', 'user', rand(1, 10)],
            ['add_to_cart', 'product', rand(1, 20)],
        ];
        
        $count = 0;
        foreach ($test_events as $event) {
            $database->recordEvent([
                'event_type' => $event[0],
                'object_type' => $event[1],
                'object_id' => $event[2],
                'session_id' => 'test_' . wp_generate_password(12, false),
                'meta_data' => ['test_event' => true],
            ]);
            $count++;
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Generated %d test events', 'bandfront-analytics'), $count),
        ]);
    }
    
    /**
     * AJAX handler for cleaning test events
     */
    public function ajaxCleanTestEvents(): void {
        check_ajax_referer('bfa_test_actions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }
        
        global $wpdb;
        
        // Delete test events (those with test_event in meta_data)
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->prefix}bfa_events 
            WHERE meta_data LIKE '%test_event%'"
        );
        
        wp_send_json_success([
            'message' => sprintf(__('Deleted %d test events', 'bandfront-analytics'), $deleted),
        ]);
    }
    
    /**
     * Save settings
     */
    private function saveSettings(): void {
        $settings = [
            'tracking_enabled' => !empty($_POST['tracking_enabled']),
            'exclude_admins' => !empty($_POST['exclude_admins']),
            'respect_dnt' => !empty($_POST['respect_dnt']),
            'anonymize_ip' => !empty($_POST['anonymize_ip']),
            'sampling_threshold' => intval($_POST['sampling_threshold'] ?? 10000),
            'retention_days' => intval($_POST['retention_days'] ?? 365),
        ];
        
        $this->plugin->getConfig()->save($settings);
        
        add_settings_error(
            'bfa_messages',
            'bfa_message',
            __('Settings saved successfully!', 'bandfront-analytics'),
            'success'
        );
    }
}