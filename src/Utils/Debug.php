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
            // Log to browser console
            echo "<script>console.log(" . json_encode(['message' => $message, 'context' => $context]) . ");</script>";

            // Optionally log to error log
            error_log("[DEBUG] " . $message . " " . json_encode($context));
        }
    }
}