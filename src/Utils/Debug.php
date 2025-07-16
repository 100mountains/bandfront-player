<?php
namespace bfp\Utils;


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug Class
 * Handles logging for debugging purposes
 */
class Debug {
    private static bool $enabled = true; // Always enabled for debugging
    private static int $maxDepth = 3;
    private static int $maxStringLength = 500;
    private static ?string $logFile = null;

    /**
     * Get the log file path
     */
    private static function getLogFile(): string {
        if (self::$logFile === null) {
            // First try WordPress debug.log if WP_DEBUG_LOG is enabled
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                if (is_string(WP_DEBUG_LOG)) {
                    // WP 5.1+ allows specifying custom log file path
                    self::$logFile = WP_DEBUG_LOG;
                } else {
                    // Default WordPress debug.log location
                    self::$logFile = WP_CONTENT_DIR . '/debug.log';
                }
            } else {
                // Create our own debug log in the plugin directory
                $uploadDir = wp_upload_dir();
                $logDir = $uploadDir['basedir'] . '/bfp-logs';
                
                // Create directory if it doesn't exist
                if (!file_exists($logDir)) {
                    wp_mkdir_p($logDir);
                    // Protect directory with .htaccess
                    file_put_contents($logDir . '/.htaccess', 'deny from all');
                }
                
                self::$logFile = $logDir . '/debug.log';
            }
        }
        
        return self::$logFile;
    }

    /**
     * Enable debugging globally
     */
    public static function enable(): void {
        self::$enabled = true;
    }

    /**
     * Disable debugging globally
     */
    public static function disable(): void {
        self::$enabled = false;
    }

    /**
     * Log a message with automatic context detection
     */
    public static function log(string $message, array $context = []): void {
        // Simple global check
        if (!self::$enabled) {
            return;
        }

        // Format the log entry
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[BFP $timestamp] $message";

        // Format context data if provided
        if (!empty($context)) {
            $contextStr = self::formatContext($context);
            $logEntry .= " | Context: $contextStr";
        }

        // Write to both error log and debug.log for now
        error_log($logEntry);
        
        // Also write to debug.log file
        $logFile = self::getLogFile();
        if ($logFile) {
            $logEntry .= PHP_EOL;
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Format context data for logging
     */
    private static function formatContext(array $context, int $depth = 0): string {
        if ($depth > self::$maxDepth) {
            return '...';
        }

        $parts = [];
        foreach ($context as $key => $value) {
            $formattedValue = self::formatValue($value, $depth);
            $parts[] = "$key: $formattedValue";
        }

        return '{' . implode(', ', $parts) . '}';
    }

    /**
     * Format a single value for logging
     */
    private static function formatValue($value, int $depth = 0): string {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value)) {
            if (is_string($value) && strlen($value) > self::$maxStringLength) {
                return '"' . substr($value, 0, self::$maxStringLength) . '..."';
            }
            return var_export($value, true);
        }
        if (is_array($value)) {
            if ($depth >= self::$maxDepth) {
                return '[...]';
            }
            if (empty($value)) {
                return '[]';
            }
            // Check if it's a sequential array
            if (array_keys($value) === range(0, count($value) - 1)) {
                $items = array_map(function($v) use ($depth) {
                    return self::formatValue($v, $depth + 1);
                }, array_slice($value, 0, 5));
                $result = '[' . implode(', ', $items);
                if (count($value) > 5) {
                    $result .= ', ... (' . count($value) . ' items)';
                }
                return $result . ']';
            }
            return self::formatContext($value, $depth + 1);
        }
        if (is_object($value)) {
            $class = get_class($value);
            if (method_exists($value, '__toString')) {
                return "$class: " . (string)$value;
            }
            if (method_exists($value, 'toArray')) {
                return "$class: " . self::formatValue($value->toArray(), $depth + 1);
            }
            return "$class object";
        }
        return gettype($value);
    }

    /**
     * Log function entry (useful for tracing execution flow)
     */
    public static function enter(string $function, array $args = []): void {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $file = basename($backtrace[0]['file'] ?? 'unknown');
        $line = $backtrace[0]['line'] ?? 0;
        
        self::log("$file:$line Entering $function()", ['args' => $args]);
    }

    /**
     * Log function exit
     */
    public static function exit(string $function, $result = null): void {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $file = basename($backtrace[0]['file'] ?? 'unknown');
        $line = $backtrace[0]['line'] ?? 0;
        
        self::log("$file:$line Exiting $function()", ['result' => $result]);
    }

    /**
     * Log SQL queries (useful for debugging database issues)
     */
    public static function sql(string $query, array $params = []): void {
        self::log("SQL Query", [
            'query' => $query,
            'params' => $params
        ]);
    }

    /**
     * Log performance metrics
     */
    public static function performance(string $operation, float $duration, array $extra = []): void {
        self::log("Performance: $operation", array_merge([
            'duration_ms' => round($duration * 1000, 2)
        ], $extra));
    }
}
