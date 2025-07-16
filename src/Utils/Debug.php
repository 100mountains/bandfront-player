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
    private static bool $enabled = false;
    private static array $enabledContexts = [];
    private static int $maxDepth = 3;
    private static int $maxStringLength = 500;

    /**
     * Enable debugging globally or for specific contexts
     */
    public static function enable(string $context = null): void {
        if ($context === null) {
            self::$enabled = true;
        } else {
            self::$enabledContexts[$context] = true;
        }
    }

    /**
     * Disable debugging globally or for specific contexts
     */
    public static function disable(string $context = null): void {
        if ($context === null) {
            self::$enabled = false;
            self::$enabledContexts = [];
        } else {
            unset(self::$enabledContexts[$context]);
        }
    }

    /**
     * Check if debugging is enabled for a context
     */
    private static function isEnabled(string $context = null): bool {
        if (self::$enabled) {
            return true;
        }
        if ($context && isset(self::$enabledContexts[$context])) {
            return true;
        }
        // Extract class name from file path if context looks like a file
        if ($context && strpos($context, '.php') !== false) {
            $className = basename($context, '.php');
            return isset(self::$enabledContexts[$className]);
        }
        return false;
    }

    /**
     * Log a message with automatic context detection
     */
    public static function log(string $message, array $context = []): void {
        // Extract calling context from message (format: "filename.php:line ...")
        $callingContext = null;
        if (preg_match('/^([^:]+\.php)/', $message, $matches)) {
            $callingContext = basename($matches[1], '.php');
        }

        if (!self::isEnabled($callingContext)) {
            return;
        }

        // Format the log entry
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[BFP DEBUG $timestamp] $message";

        // Format context data if provided
        if (!empty($context)) {
            $contextStr = self::formatContext($context);
            $logEntry .= " | Context: $contextStr";
        }

        // Always log to error log (much more reliable than console.log)
        error_log($logEntry);
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
