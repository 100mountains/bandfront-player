<?php
namespace bfp\Utils;

/**
 * Debug Class
 * Handles logging for debugging purposes
 */
class Debug {
    private static bool $enabled = false;

    /**
     * Enable debugging
     */
    public static function enable(): void {
        self::$enabled = true;
    }

    /**
     * Disable debugging
     */
    public static function disable(): void {
        self::$enabled = false;
    }

    /**
     * Log a message
     */
    public static function log(string $message, array $context = []): void {
        if (self::$enabled) {
            // Never output during plugin activation
            if (!defined('WP_INSTALLING') && !defined('WP_SETUP_CONFIG')) {
                // Only log to browser console during AJAX or specific actions
                if (defined('DOING_AJAX') || (isset($_REQUEST['bfp-action']) && $_REQUEST['bfp-action'] === 'play')) {
                    echo "<script>console.log(" . json_encode(['message' => $message, 'context' => $context]) . ");</script>";
                }
            }

            // Always safe to log to error log
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log("[BFP DEBUG] " . $message . " " . json_encode($context));
            }
        }
    }
}