<?php declare(strict_types=1);

namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * Security Helper for Sufism Library Theme
 * Provides additional security functions for template escaping and validation
 */
class SecurityHelper extends AbstractHelper
{
    /**
     * Provide callable invocation syntax for the view helper.
     *
     * @return self The helper instance for fluent or chained usage.
     */
    public function __invoke(): self
    {
        return $this;
    }
    
    /**
     * Escape HTML content and optionally preserve a limited set of safe HTML tags.
     *
     * When $allowBasicTags is true, a small whitelist of basic tags is preserved
     * and potentially dangerous attributes (inline event handlers, javascript:, data:,
     * and inline style) are removed before escaping.
     *
     * @param string $content The content to sanitize and escape.
     * @param bool $allowBasicTags Whether to allow a limited set of basic HTML tags; dangerous attributes are removed when enabled.
     * @return string The sanitized and HTML-escaped string.
     */
    public function secureEscape($content, $allowBasicTags = false): string
    {
        if (empty($content)) {
            return '';
        }
        
        $escape = $this->getView()->plugin('escapeHtml');
        
        if ($allowBasicTags) {
            // Allow only safe HTML tags
            $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>';
            $content = strip_tags($content, $allowedTags);
            
            // Remove potentially dangerous attributes
            $content = preg_replace('/(<[^>]+)\s+(on\w+|javascript:|data:|style=)[^>]*>/i', '$1>', $content);
        }
        
        return $escape($content);
    }
    
    /**
     * Provide a per-session CSRF token, creating and storing one if absent.
     *
     * Ensures a PHP session is active and stores a 64-character hexadecimal token
     * in $_SESSION['csrf_token'] when no token exists.
     *
     * @return string The per-session CSRF token as a 64-character hexadecimal string.
     */
    public function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Determine whether the provided token matches the CSRF token stored in the current session.
     *
     * Ensures a PHP session is active before performing the comparison.
     *
     * @param string $token The CSRF token to validate against the session token.
     * @return bool `true` if the provided token exactly matches the session's CSRF token, `false` otherwise.
     */
    public function validateCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate a cryptographically secure hexadecimal identifier of the requested length.
     *
     * Uses a cryptographically secure random source to produce a hex string.
     *
     * @param int $length Number of hexadecimal characters to produce.
     * @return string Hexadecimal string exactly $length characters long.
     */
    public function generateSecureId(int $length = 16): string
    {
        // Generate ceil($length/2) bytes and trim hex output to exact length
        $bytes = (int) ceil($length / 2);
        return substr(bin2hex(random_bytes($bytes)), 0, $length);
    }
    
    /**
     * Produce a safe URL for output by stripping dangerous URI schemes and validating format.
     *
     * Removes leading "javascript:", "data:", and "vbscript:" schemes (case-insensitive). If the
     * resulting value is not a valid absolute URL and does not start with a slash (relative path),
     * returns `#`.
     *
     * @param string $url The URL to sanitize.
     * @return string The sanitized URL, or `#` when the input is invalid and not a relative path.
     */
    public function sanitizeUrl(string $url): string
    {
        // Remove dangerous protocols
        $url = preg_replace('/^(javascript|data|vbscript):/i', '', $url);
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\//', $url)) {
            return '#';
        }
        
        return $url;
    }
    
    /**
         * Retrieve a theme setting and sanitize string values to remove script-like tags.
         *
         * If the retrieved value is a string, script, iframe, object, and embed tag openings are removed.
         *
         * @param string $setting The theme setting name.
         * @param mixed $default The value to return when the setting is not present.
         * @return mixed The sanitized setting value (strings with script/iframe/object/embed openings removed).
         */
    public function getSecureThemeSetting(string $setting, $default = null)
    {
        $themeSetting = $this->getView()->plugin('themeSetting');
        $value = $themeSetting($setting, $default);
        
        // Sanitize based on setting type
        if (is_string($value)) {
            // Remove potentially dangerous content
            $value = preg_replace('/(<script|<iframe|<object|<embed)/i', '', $value);
        }
        
        return $value;
    }
    
    /**
     * Validate or sanitize an input value according to the specified type.
     *
     * For 'email', 'url', 'int', and 'float' the function validates the value and
     * returns the validated value or `false` when validation fails. For 'text' it
     * removes null bytes and control characters and returns the trimmed string.
     *
     * @param string $input The value to validate or sanitize.
     * @param string $type The validation type: 'email', 'url', 'int', 'float', or 'text' (default).
     * @return string|int|float|false The validated or sanitized value, or `false` if validation fails.
     */
    public function validateInput(string $input, string $type = 'text')
    {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
                
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
                
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
                
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
                
            case 'text':
            default:
                // Remove null bytes and control characters
                $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
                return trim($input);
        }
    }
    
    /**
     * Retrieve a per-session base64-encoded 16-byte CSP nonce, creating and storing one if absent.
     *
     * @return string The base64-encoded 16-byte Content Security Policy nonce.
     */
    public function generateCspNonce(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csp_nonce'])) {
            $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
        }
        
        return $_SESSION['csp_nonce'];
    }
    
    /**
     * Determine whether the current HTTP request was made over TLS/HTTPS.
     *
     * @return bool `true` if the current request appears to be HTTPS (TLS), `false` otherwise.
     */
    public function isSecureRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Enforces a simple per-identifier rate limit using session-backed counters.
     *
     * Uses the PHP session to track request counts for the given identifier over a rolling time window.
     * If no counter exists for the identifier it is created; when the time window elapses the counter resets.
     *
     * @param string $identifier Unique identifier for the caller (for example an IP address or user ID).
     * @param int $maxRequests Maximum allowed requests within the time window.
     * @param int $timeWindow Time window in seconds for rate limiting.
     * @return bool `true` if the request is allowed, `false` if the rate limit has been reached.
     */
    public function checkRateLimit(string $identifier, int $maxRequests = 60, int $timeWindow = 3600): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window has passed
        if ($now - $data['start'] > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxRequests) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Record a structured security event to the PHP error log.
     *
     * The logged entry includes a timestamp, the provided event description,
     * client IP, user agent, and any additional context; it is JSON-encoded
     * and prefixed with "SECURITY EVENT:" when written to error_log.
     *
     * @param string $event Short description of the security event.
     * @param array $context Additional contextual data to include in the log entry.
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'context' => $context
        ];
        
        // In a production environment, this should write to a proper log file
        // For now, we'll use error_log
        error_log('SECURITY EVENT: ' . json_encode($logEntry));
    }
}
