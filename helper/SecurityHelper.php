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
     * Rate limiting check with shared storage support
     *
     * @param string $identifier Unique identifier (IP, user ID, etc.)
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window in seconds
     * @return bool Whether request is allowed
     */
    public function checkRateLimit(string $identifier, int $maxRequests = 60, int $timeWindow = 3600): bool
    {
        // Generate secure key using SHA256 for better distribution
        $key = 'rate_limit_' . hash('sha256', $identifier);
        $now = time();

        // Try shared storage first (Redis/Memcached), fallback to session
        $storage = $this->getSharedStorage();

        if ($storage) {
            return $this->checkRateLimitShared($storage, $key, $maxRequests, $timeWindow, $now);
        } else {
            return $this->checkRateLimitSession($key, $maxRequests, $timeWindow, $now);
        }
    }

    /**
     * Get shared storage instance (Redis or Memcached)
     *
     * @return object|null Storage instance or null if unavailable
     */
    private function getSharedStorage()
    {
        static $storage = null;
        static $checked = false;

        if ($checked) {
            return $storage;
        }

        $checked = true;

        // Try Redis first
        if (class_exists('Redis')) {
            try {
                $redis = new \Redis();
                $host = $_ENV['REDIS_HOST'] ?? 'localhost';
                $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
                $timeout = (float)($_ENV['REDIS_TIMEOUT'] ?? 2.0);

                if ($redis->connect($host, $port, $timeout)) {
                    // Test connection
                    $redis->ping();
                    $storage = $redis;
                    return $storage;
                }
            } catch (\Exception $e) {
                // Redis failed, try Memcached
            }
        }

        // Try Memcached as fallback
        if (class_exists('Memcached')) {
            try {
                $memcached = new \Memcached();
                $host = $_ENV['MEMCACHED_HOST'] ?? 'localhost';
                $port = (int)($_ENV['MEMCACHED_PORT'] ?? 11211);

                $memcached->addServer($host, $port);

                // Test connection
                $memcached->get('test_connection');
                if ($memcached->getResultCode() !== \Memcached::RES_NOTFOUND &&
                    $memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
                    throw new \Exception('Memcached connection failed');
                }

                $storage = $memcached;
                return $storage;
            } catch (\Exception $e) {
                // Memcached failed, will use session fallback
            }
        }

        return null;
    }

    /**
     * Rate limiting with shared storage (Redis/Memcached)
     *
     * @param object $storage Storage instance
     * @param string $key Rate limit key
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window in seconds
     * @param int $now Current timestamp
     * @return bool Whether request is allowed
     */
    private function checkRateLimitShared($storage, string $key, int $maxRequests, int $timeWindow, int $now): bool
    {
        $dataKey = $key . '_data';
        $lockKey = $key . '_lock';

        // Implement atomic operations based on storage type
        if ($storage instanceof \Redis) {
            return $this->checkRateLimitRedis($storage, $dataKey, $lockKey, $maxRequests, $timeWindow, $now);
        } elseif ($storage instanceof \Memcached) {
            return $this->checkRateLimitMemcached($storage, $dataKey, $lockKey, $maxRequests, $timeWindow, $now);
        }

        return false;
    }

    /**
     * Redis-specific rate limiting with atomic operations
     */
    private function checkRateLimitRedis(\Redis $redis, string $dataKey, string $lockKey, int $maxRequests, int $timeWindow, int $now): bool
    {
        // Use Redis transactions for atomicity
        $redis->multi();

        try {
            // Get current data
            $data = $redis->get($dataKey);

            if ($data === false) {
                // First request
                $newData = json_encode(['count' => 1, 'start' => $now]);
                $redis->setex($dataKey, $timeWindow, $newData);
                $redis->exec();
                return true;
            }

            $data = json_decode($data, true);

            // Reset if time window has passed
            if ($now - $data['start'] > $timeWindow) {
                $newData = json_encode(['count' => 1, 'start' => $now]);
                $redis->setex($dataKey, $timeWindow, $newData);
                $redis->exec();
                return true;
            }

            // Check if limit exceeded
            if ($data['count'] >= $maxRequests) {
                $redis->discard();
                return false;
            }

            // Increment counter atomically
            $data['count']++;
            $newData = json_encode($data);
            $ttl = $timeWindow - ($now - $data['start']);
            $redis->setex($dataKey, max($ttl, 1), $newData);
            $redis->exec();

            return true;

        } catch (\Exception $e) {
            $redis->discard();
            return false;
        }
    }

    /**
     * Memcached-specific rate limiting with CAS operations
     */
    private function checkRateLimitMemcached(\Memcached $memcached, string $dataKey, string $lockKey, int $maxRequests, int $timeWindow, int $now): bool
    {
        $maxRetries = 3;
        $retryDelay = 1000; // microseconds

        for ($retry = 0; $retry < $maxRetries; $retry++) {
            // Get with CAS token for atomic updates
            $data = $memcached->get($dataKey, null, $casToken);

            if ($memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
                // First request
                $newData = ['count' => 1, 'start' => $now];
                if ($memcached->set($dataKey, $newData, $timeWindow)) {
                    return true;
                }
                // If set failed, retry
                usleep($retryDelay);
                continue;
            }

            if (!is_array($data)) {
                return false;
            }

            // Reset if time window has passed
            if ($now - $data['start'] > $timeWindow) {
                $newData = ['count' => 1, 'start' => $now];
                if ($memcached->set($dataKey, $newData, $timeWindow)) {
                    return true;
                }
                usleep($retryDelay);
                continue;
            }

            // Check if limit exceeded
            if ($data['count'] >= $maxRequests) {
                return false;
            }

            // Increment counter with CAS
            $data['count']++;
            $ttl = $timeWindow - ($now - $data['start']);

            if ($memcached->cas($casToken, $dataKey, $data, max($ttl, 1))) {
                return true;
            }

            // CAS failed, retry
            usleep($retryDelay);
        }

        return false;
    }

    /**
     * Session-based rate limiting fallback
     */
    private function checkRateLimitSession(string $key, int $maxRequests, int $timeWindow, int $now): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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
