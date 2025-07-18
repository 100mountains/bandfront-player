<?php
namespace Bandfront\Db;

use Bandfront\Utils\Debug;

/**
 * Test class for generating and cleaning test events in the database
 * 
 * @package Bandfront\Db
 * @since 2.0.0
 */
class Test {
    
    /**
     * Event types to generate
     */
    private static $event_types = [
        'pageview',
        'music_play',
        'music_complete',
        'download',
        'add_to_cart',
        'remove_from_cart',
        'purchase',
        'search',
        'login',
        'logout',
        'click',
        'scroll',
        'form_submit'
    ];
    
    /**
     * Sample product IDs for testing
     */
    private static $test_product_ids = [101, 102, 103, 104, 105, 106, 107, 108, 109, 110];
    
    /**
     * Sample post IDs for testing
     */
    private static $test_post_ids = [201, 202, 203, 204, 205];
    
    /**
     * Generate random test events
     * 
     * @param int $count Number of events to generate
     * @return array Results of the generation
     */
    public static function generateTestEvents($count = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bfa_events';
        
        $generated = 0;
        $errors = 0;
        
        // Generate events spread across the last 7 days
        for ($i = 0; $i < $count; $i++) {
            $event_type = self::$event_types[array_rand(self::$event_types)];
            $event_data = self::generateEventData($event_type);
            
            // Random timestamp within last 7 days
            $days_ago = rand(0, 6);
            $hours_ago = rand(0, 23);
            $minutes_ago = rand(0, 59);
            $timestamp = date('Y-m-d H:i:s', strtotime("-{$days_ago} days -{$hours_ago} hours -{$minutes_ago} minutes"));
            
            $data = [
                'event_type' => $event_type,
                'object_id' => $event_data['object_id'],
                'object_type' => $event_data['object_type'],
                'user_id' => self::getRandomUserId(),
                'session_id' => self::generateSessionId(),
                'value' => $event_data['value'],
                'meta' => json_encode($event_data['meta']),
                'ip_address' => self::generateRandomIp(),
                'user_agent' => self::getRandomUserAgent(),
                'referer' => self::getRandomReferer(),
                'created_at' => $timestamp
            ];
            
            $result = $wpdb->insert($table_name, $data);
            
            if ($result === false) {
                $errors++;
            } else {
                $generated++;
            }
        }
        
        // Clear any caches
        wp_cache_flush();
        
        return [
            'generated' => $generated,
            'errors' => $errors,
            'total_requested' => $count
        ];
    }
    
    /**
     * Generate event data based on event type
     */
    private static function generateEventData($event_type) {
        switch ($event_type) {
            case 'pageview':
                $post_id = self::$test_post_ids[array_rand(self::$test_post_ids)];
                return [
                    'object_id' => $post_id,
                    'object_type' => 'post',
                    'value' => 1,
                    'meta' => [
                        'page_title' => "Test Page {$post_id}",
                        'time_on_page' => rand(10, 300),
                        'test_event' => true
                    ]
                ];
                
            case 'music_play':
            case 'music_complete':
                $product_id = self::$test_product_ids[array_rand(self::$test_product_ids)];
                $track_id = rand(1, 20);
                return [
                    'object_id' => $product_id,
                    'object_type' => 'product',
                    'value' => 1,
                    'meta' => [
                        'track_id' => $track_id,
                        'track_title' => "Test Track {$track_id}",
                        'duration' => rand(180, 420),
                        'test_event' => true
                    ]
                ];
                
            case 'download':
                $product_id = self::$test_product_ids[array_rand(self::$test_product_ids)];
                return [
                    'object_id' => $product_id,
                    'object_type' => 'product',
                    'value' => 1,
                    'meta' => [
                        'file_name' => "test-file-{$product_id}.zip",
                        'file_size' => rand(1000000, 50000000),
                        'test_event' => true
                    ]
                ];
                
            case 'add_to_cart':
            case 'remove_from_cart':
                $product_id = self::$test_product_ids[array_rand(self::$test_product_ids)];
                $price = rand(5, 50) + (rand(0, 99) / 100);
                return [
                    'object_id' => $product_id,
                    'object_type' => 'product',
                    'value' => $price,
                    'meta' => [
                        'quantity' => rand(1, 3),
                        'price' => $price,
                        'test_event' => true
                    ]
                ];
                
            case 'purchase':
                $order_id = rand(1000, 9999);
                $total = rand(10, 200) + (rand(0, 99) / 100);
                return [
                    'object_id' => $order_id,
                    'object_type' => 'order',
                    'value' => $total,
                    'meta' => [
                        'order_total' => $total,
                        'item_count' => rand(1, 5),
                        'payment_method' => ['paypal', 'stripe', 'manual'][rand(0, 2)],
                        'test_event' => true
                    ]
                ];
                
            case 'search':
                return [
                    'object_id' => 0,
                    'object_type' => 'search',
                    'value' => rand(0, 20), // Results count
                    'meta' => [
                        'search_query' => self::getRandomSearchTerm(),
                        'results_count' => rand(0, 20),
                        'test_event' => true
                    ]
                ];
                
            default:
                return [
                    'object_id' => rand(1, 1000),
                    'object_type' => 'unknown',
                    'value' => rand(0, 100),
                    'meta' => [
                        'random_data' => 'test_' . uniqid(),
                        'test_event' => true
                    ]
                ];
        }
    }
    
    /**
     * Get random user ID (0 for guest)
     */
    private static function getRandomUserId() {
        // 60% guest, 40% logged in
        return rand(1, 10) <= 6 ? 0 : rand(1, 20);
    }
    
    /**
     * Generate random session ID
     */
    private static function generateSessionId() {
        return 'test_' . md5(uniqid(rand(), true));
    }
    
    /**
     * Generate random IP address
     */
    private static function generateRandomIp() {
        $ips = [
            '192.168.1.' . rand(1, 255),
            '10.0.0.' . rand(1, 255),
            '172.16.0.' . rand(1, 255),
            rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255)
        ];
        return $ips[array_rand($ips)];
    }
    
    /**
     * Get random user agent
     */
    private static function getRandomUserAgent() {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
            'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
            'BFA Test Bot/1.0'
        ];
        return $agents[array_rand($agents)];
    }
    
    /**
     * Get random referer
     */
    private static function getRandomReferer() {
        $referers = [
            '',
            'https://google.com/search?q=test',
            'https://facebook.com',
            'https://twitter.com',
            'https://instagram.com',
            'direct',
            home_url('/'),
            home_url('/shop/'),
            home_url('/music/')
        ];
        return $referers[array_rand($referers)];
    }
    
    /**
     * Get random search term
     */
    private static function getRandomSearchTerm() {
        $terms = [
            'rock music',
            'jazz album',
            'electronic beats',
            'vinyl records',
            'concert tickets',
            'band merch',
            'guitar tabs',
            'music video',
            'album download',
            'streaming playlist'
        ];
        return $terms[array_rand($terms)];
    }
}