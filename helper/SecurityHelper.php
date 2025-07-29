<?php declare(strict_types=1);

namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * Security Helper for Sufism Library Theme
 * Provides additional security functions for template escaping and validation
 */
class SecurityHelper extends AbstractHelper
{
    public function __invoke(): self
    {
        return $this;
    }
    
    /**
     * Safely escape HTML content with additional security measures
     * 
     * @param string $content Content to escape
     * @param bool $allowBasicTags Whether to allow basic HTML tags
     * @return string Escaped content
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
     * Generate secure CSRF token for forms
     * 
     * @return string CSRF token
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
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool Whether token is valid
     */
    public function validateCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate secure random string for IDs
     * 
     * @param int $length Length of string
     * @return string Random string
     */
    public function generateSecureId(int $length = 16): string
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Sanitize URL for safe output
     * 
     * @param string $url URL to sanitize
     * @return string Sanitized URL
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
     * Get theme setting with security validation
     * 
     * @param string $setting Setting name
     * @param mixed $default Default value
     * @return mixed Setting value
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
     * Validate and sanitize user input
     * 
     * @param string $input User input
     * @param string $type Type of validation (email, url, text, etc.)
     * @return string|false Sanitized input or false if invalid
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
     * Generate Content Security Policy nonce
     * 
     * @return string CSP nonce
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
     * Check if current request is HTTPS
     * 
     * @return bool Whether request is secure
     */
    public function isSecureRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Rate limiting check (basic implementation)
     * 
     * @param string $identifier Unique identifier (IP, user ID, etc.)
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window in seconds
     * @return bool Whether request is allowed
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
     * Log security event (basic implementation)
     * 
     * @param string $event Event description
     * @param array $context Additional context
     * @return void
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
