<?php
namespace Bandfront\Db;

use Bandfront\Utils\Debug;

// Set domain for Db
Debug::domain('db');

class Clean {
    
    /**
     * Clean all test events from the database
     * 
     * @return array Cleanup results
     */
    public static function cleanTestEvents() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bfa_events';
        
        // Count test events before deletion
        $count_before = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE meta LIKE '%\"test_event\":true%'"
        );
        
        // Delete all test events
        $deleted = $wpdb->query(
            "DELETE FROM {$table_name} 
             WHERE meta LIKE '%\"test_event\":true%'"
        );
        
        // Also clean up any orphaned sessions from test events
        $sessions_cleaned = self::cleanTestSessions();
        
        // Clear caches
        wp_cache_flush();
        
        return [
            'events_found' => $count_before,
            'events_deleted' => $deleted,
            'sessions_cleaned' => $sessions_cleaned,
            'success' => ($deleted !== false)
        ];
    }
    
    /**
     * Clean test sessions
     */
    private static function cleanTestSessions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bfa_events';
        
        // Get all test session IDs
        $test_sessions = $wpdb->get_col(
            "SELECT DISTINCT session_id FROM {$table_name} 
             WHERE session_id LIKE 'test_%'"
        );
        
        if (empty($test_sessions)) {
            return 0;
        }
        
        // Delete all events with test session IDs
        $placeholders = implode(',', array_fill(0, count($test_sessions), '%s'));
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE session_id IN ($placeholders)",
                $test_sessions
            )
        );
        
        return $deleted;
    }
    
    /**
     * Get statistics about test data
     */
    public static function getTestDataStats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bfa_events';
        
        $stats = [
            'test_events' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name} 
                 WHERE meta LIKE '%\"test_event\":true%'"
            ),
            'test_sessions' => $wpdb->get_var(
                "SELECT COUNT(DISTINCT session_id) FROM {$table_name} 
                 WHERE session_id LIKE 'test_%'"
            ),
            'test_event_types' => $wpdb->get_results(
                "SELECT event_type, COUNT(*) as count 
                 FROM {$table_name} 
                 WHERE meta LIKE '%\"test_event\":true%' 
                 GROUP BY event_type",
                ARRAY_A
            )
        ];
        
        return $stats;
    }
    
    /**
     * Clean old events (general cleanup, not just test data)
     * 
     * @param int $days_to_keep Number of days to keep
     * @return int Number of events deleted
     */
    public static function cleanOldEvents($days_to_keep = 90) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bfa_events';
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < %s",
            $cutoff_date
        ));
        
        return $deleted;
    }
}