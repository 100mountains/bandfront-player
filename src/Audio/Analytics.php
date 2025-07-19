<?php
declare(strict_types=1);

namespace Bandfront\Audio;

use Bandfront\Core\Config;
use Bandfront\Utils\Debug;

// Set domain for Audio
Debug::domain('audio');

/**
 * Analytics Class
 * 
 * Tracks audio play events and analytics for the Bandfront Player plugin.
 * 
 * @package Bandfront\Audio
 * @since 2.0.0
 */
class Analytics {
    
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    /**
     * Initialize analytics
     */
    public function init(): void {
        // Hook registration handled by Hooks.php
    }

    /**
     * Track a play event for a specific product and file.
     *
     * @param int $productId The ID of the product.
     * @param string $fileUrl The URL of the audio file.
     * @return void
     */
    public function trackPlayEvent(int $productId, string $fileUrl): void {
        Debug::log('Analytics: trackPlayEvent', ['productId' => $productId, 'fileUrl' => $fileUrl]); // DEBUG-REMOVE
        
        $settings = $this->config->getStates([
            '_bfp_analytics_integration',
            '_bfp_analytics_property',
            '_bfp_analytics_api_secret'
        ]);
        
        if (empty($settings['_bfp_analytics_property'])) {
            Debug::log('Analytics: no analytics property configured'); // DEBUG-REMOVE
            return;
        }
        
        $clientId = $this->getClientId();
        $endpoint = $this->getAnalyticsEndpoint($settings);
        $body = $this->buildAnalyticsPayload($settings, $clientId, $productId, $fileUrl);
        
        Debug::log('Analytics: sending to endpoint', [
            'clientId' => $clientId,
            'endpoint' => $endpoint,
            'integration' => $settings['_bfp_analytics_integration']
        ]); // DEBUG-REMOVE
        
        $response = wp_remote_post($endpoint, $body);
        
        if (is_wp_error($response)) {
            Debug::log('Analytics: error', $response->get_error_message()); // DEBUG-REMOVE
            error_log('BFP Analytics error: ' . $response->get_error_message());
        } else {
            Debug::log('Analytics: success', wp_remote_retrieve_response_code($response)); // DEBUG-REMOVE
        }
    }
    
    /**
     * AJAX handler for tracking events
     */
    public function ajaxTrackEvent(): void {
        $productId = absint($_POST['product_id'] ?? 0);
        $fileUrl = sanitize_text_field($_POST['file_url'] ?? '');
        
        if (!$productId || !$fileUrl) {
            wp_send_json_error('Invalid parameters');
        }
        
        $this->trackPlayEvent($productId, $fileUrl);
        wp_send_json_success();
    }
    
    /**
     * Output tracking code in footer
     */
    public function outputTrackingCode(): void {
        if (!$this->config->getState('_bfp_analytics_property')) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).on('bfp:play', function(e, data) {
            jQuery.post(bfp_global_settings.ajaxurl, {
                action: 'bfp_track_event',
                product_id: data.product_id,
                file_url: data.file_url
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get client ID for analytics
     * 
     * @return string Client ID
     */
    private function getClientId(): string {
        // Try Google Analytics cookie first
        if (isset($_COOKIE['_ga'])) {
            $parts = explode('.', sanitize_text_field(wp_unslash($_COOKIE['_ga'])), 3);
            if (isset($parts[2])) {
                return $parts[2];
            }
        }
        
        // Fall back to IP address
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }
    
    /**
     * Get analytics endpoint URL
     * 
     * @param array $settings Analytics settings
     * @return string Endpoint URL
     */
    private function getAnalyticsEndpoint(array $settings): string {
        if ($settings['_bfp_analytics_integration'] === 'ua') {
            return 'http://www.google-analytics.com/collect';
        }
        
        return sprintf(
            'https://www.google-analytics.com/mp/collect?api_secret=%s&measurement_id=%s',
            $settings['_bfp_analytics_api_secret'],
            $settings['_bfp_analytics_property']
        );
    }
    
    /**
     * Build analytics payload
     * 
     * @param array $settings Analytics settings
     * @param string $clientId Client ID
     * @param int $productId Product ID
     * @param string $fileUrl File URL
     * @return array Request arguments
     */
    private function buildAnalyticsPayload(array $settings, string $clientId, int $productId, string $fileUrl): array {
        if ($settings['_bfp_analytics_integration'] === 'ua') {
            return [
                'body' => [
                    'v' => 1,
                    'tid' => $settings['_bfp_analytics_property'],
                    'cid' => $clientId,
                    't' => 'event',
                    'ec' => 'Music Player for WooCommerce',
                    'ea' => 'play',
                    'el' => $fileUrl,
                    'ev' => $productId,
                ],
            ];
        }
        
        return [
            'sslverify' => true,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'client_id' => $clientId,
                'events' => [
                    [
                        'name' => 'play',
                        'params' => [
                            'event_category' => 'Music Player for WooCommerce',
                            'event_label' => $fileUrl,
                            'event_value' => $productId,
                        ],
                    ],
                ],
            ]),
        ];
    }
}
