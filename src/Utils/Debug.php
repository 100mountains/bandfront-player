<?php
declare(strict_types=1);

// Set the domain for this file
// Debug::domain('admin');

// class AdminClass {
//     public function processRequest() {
//         Debug::log('Processing admin request', ['user' => $userId]);
//         // This will only log if 'admin' domain is enabled
//     }
// }
// // Anywhere in your code
// Debug::admin('Admin action performed', ['action' => 'save_settings']);
// Debug::ui('Rendering player interface', ['theme' => 'dark']);
// Debug::filemanager('File uploaded', ['size' => $fileSize]);
// Debug::audio('Processing audio file', ['format' => 'mp3']);
// Debug::api('API request received', ['endpoint' => '/stream']);
// // Pass domain as parameter
// Debug::bootstrap('Component initialized', ['component' => 'player']);

namespace Bandfront\Admin;
namespace Bandfront\Utils;

use Bandfront\Utils\Debug;
use Bandfront\Core\Config;


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug Class
 * Domain-based logging system for development
 * 
 * @package Bandfront\Utils
 * @since 2.0.0
 */
class Debug {
    private static ?Config $config = null;
    private static ?string $currentDomain = null;
    private static int $maxDepth = 3;
    private static int $maxStringLength = 500;
    private static ?string $logFile = null;

    /**
     * Initialize Debug with Config instance
     */
    public static function init(Config $config): void {
        self::$config = $config;
    }

    /**
     * Set the current debug domain for this file/class
     */
    public static function domain(string $domain): void {
        self::$currentDomain = strtolower($domain);
    }

    /**
     * Log a message
     * @param string $message The message to log
     * @param array $context Optional context data
     * @param string|null $domain Optional domain override
     */
    public static function log(string $message, array $context = [], ?string $domain = null): void {
        // Use provided domain, fall back to current domain
        $domain = $domain ? strtolower($domain) : self::$currentDomain;
        
        // If no domain set, don't log
        if (!$domain || !self::$config) {
            return;
        }
        
        // Check if core override is enabled (affects all core-* domains)
        if (strpos($domain, 'core-') === 0 && self::$config->isDebugEnabled('core')) {
            // Core override is enabled, proceed with logging
        } else if (!self::$config->isDebugEnabled($domain)) {
            // Domain not enabled
            return;
        }

        // Format the log entry
        $timestamp = date('H:i:s'); // Only show time Y-m-d removed
        $domainPrefix = strtoupper($domain);
        $logEntry = "[BFP:{$domainPrefix}] [{$timestamp}] {$message}";

        // Add context if provided
        if (!empty($context)) {
            $contextStr = self::formatContext($context);
            $logEntry .= " | Context: {$contextStr}";
        }

        // Write to error log
        error_log($logEntry);
        
        // Also write to debug.log file if configured
        $logFile = self::getLogFile();
        if ($logFile) {
            $logEntry .= PHP_EOL;
            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Domain-specific convenience methods
     */
    public static function admin(string $message, array $context = []): void {
        self::log($message, $context, 'admin');
    }

    public static function bootstrap(string $message, array $context = []): void {
        self::log($message, $context, 'core-bootstrap');
    }

    public static function ui(string $message, array $context = []): void {
        self::log($message, $context, 'ui');
    }

    public static function filemanager(string $message, array $context = []): void {
        self::log($message, $context, 'storage');
    }

    public static function audio(string $message, array $context = []): void {
        self::log($message, $context, 'audio');
    }

    public static function api(string $message, array $context = []): void {
        self::log($message, $context, 'api');
    }
    
    // New convenience methods
    public static function storage(string $message, array $context = []): void {
        self::log($message, $context, 'storage');
    }
    
    public static function db(string $message, array $context = []): void {
        self::log($message, $context, 'db');
    }
    
    public static function woocommerce(string $message, array $context = []): void {
        self::log($message, $context, 'woocommerce');
    }
    
    public static function core(string $message, array $context = []): void {
        self::log($message, $context, 'core');
    }
    
    public static function utils(string $message, array $context = []): void {
        self::log($message, $context, 'utils');
    }
    
    /**
     * Get the log file path
     */
    private static function getLogFile(): string {
        if (self::$logFile === null) {
            // First try WordPress debug.log if WP_DEBUG_LOG is enabled
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                if (is_string(WP_DEBUG_LOG)) {
                    self::$logFile = WP_DEBUG_LOG;
                } else {
                    self::$logFile = WP_CONTENT_DIR . '/debug.log';
                }
            } else {
                // Create our own debug log
                $uploadDir = wp_upload_dir();
                $logDir = $uploadDir['basedir'] . '/bfp-logs';
                
                if (!file_exists($logDir)) {
                    wp_mkdir_p($logDir);
                    file_put_contents($logDir . '/.htaccess', 'deny from all');
                }
                
                self::$logFile = $logDir . '/debug.log';
            }
        }
        
        return self::$logFile;
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

    /**
     * Log with timing information
     */
    public static function timing(string $operation, callable $callback, ?string $domain = null) {
        $start = microtime(true);
        $result = $callback();
        $duration = microtime(true) - $start;
        
        self::log("Timing: {$operation}", [
            'duration_ms' => round($duration * 1000, 2)
        ], $domain);
        
        return $result;
    }

    /**
     * Check if debugging is enabled (for any domain)
     */
    public static function isEnabled(): bool {
        return self::$config && self::$config->isDebugEnabled();
    }

    /**
     * Check if a specific domain is enabled
     */
    public static function isDomainEnabled(string $domain): bool {
        return self::$config && self::$config->isDebugEnabled($domain);
    }
}
