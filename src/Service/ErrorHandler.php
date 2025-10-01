<?php declare(strict_types=1);

namespace LibraryThemeStyles\Service;

use LibraryThemeStyles\Config\ModuleConfig;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Centralized error handling service for LibraryThemeStyles module
 * Provides consistent error handling, logging, and user-friendly messages
 */
class ErrorHandler
{
    private LoggerInterface $logger;
    
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }
    
    /**
     * Handle and log an exception, return user-friendly error message
     */
    public function handleException(\Throwable $exception, string $context = ''): string
    {
        $errorId = uniqid('lts_error_');
        $contextInfo = $context ? " (Context: {$context})" : '';
        
        // Log the full exception details
        $this->logger->error(
            "LibraryThemeStyles Error [{$errorId}]: {$exception->getMessage()}{$contextInfo}",
            [
                'exception' => $exception,
                'context' => $context,
                'error_id' => $errorId,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
        
        // Return user-friendly message with error ID for support
        return $this->getUserFriendlyMessage($exception, $errorId);
    }
    
    /**
     * Validate preset name and return appropriate error if invalid
     */
    public function validatePreset(string $preset): ?string
    {
        if (!ModuleConfig::isValidPreset($preset)) {
            $this->logger->warning("Invalid preset requested: {$preset}");
            return ModuleConfig::getErrorMessage('unknown_preset', $preset);
        }
        
        return null;
    }
    
    /**
     * Validate site slug and return appropriate error if invalid
     */
    public function validateSiteSlug(?string $siteSlug, bool $required = true): ?string
    {
        if ($required && empty($siteSlug)) {
            $this->logger->warning("Missing required site slug");
            return ModuleConfig::getErrorMessage('missing_site_slug');
        }
        
        return null;
    }
    
    /**
     * Validate theme settings data
     */
    public function validateThemeSettings(array $settings): array
    {
        $errors = [];
        
        foreach ($settings as $key => $value) {
            if (!is_string($value)) {
                $errors[] = "Setting '{$key}' must be a string, got " . gettype($value);
                continue;
            }
            
            // Validate color values
            if (str_contains($key, '_color') && !ModuleConfig::isValidColor($value)) {
                $errors[] = "Setting '{$key}' has invalid color format: {$value}";
            }
            
            // Validate font size values
            if (str_contains($key, '_font_size') && !ModuleConfig::isValidFontSize($value)) {
                $errors[] = "Setting '{$key}' has invalid font size format: {$value}";
            }
        }
        
        if (!empty($errors)) {
            $this->logger->warning("Theme settings validation failed", ['errors' => $errors]);
        }
        
        return $errors;
    }
    
    /**
     * Handle API errors with context
     */
    public function handleApiError(\Throwable $exception, string $operation): string
    {
        $this->logger->error(
            "API error during {$operation}: {$exception->getMessage()}",
            [
                'operation' => $operation,
                'exception' => $exception,
            ]
        );
        
        return ModuleConfig::getErrorMessage('api_error', $exception->getMessage());
    }
    
    /**
     * Log successful operations for audit trail
     */
    public function logSuccess(string $operation, array $context = []): void
    {
        $this->logger->info("LibraryThemeStyles: {$operation}", $context);
    }
    
    /**
     * Get user-friendly error message based on exception type
     */
    private function getUserFriendlyMessage(\Throwable $exception, string $errorId): string
    {
        $baseMessage = "An error occurred while processing your request.";
        
        // Customize message based on exception type
        if ($exception instanceof \InvalidArgumentException) {
            $baseMessage = "Invalid input provided: " . $exception->getMessage();
        } elseif ($exception instanceof \RuntimeException) {
            $baseMessage = "Operation failed: " . $exception->getMessage();
        } elseif (str_contains($exception->getMessage(), 'API')) {
            $baseMessage = "Database operation failed. Please try again.";
        }
        
        return "{$baseMessage} (Error ID: {$errorId})";
    }
    
    /**
     * Wrap operation in try-catch with consistent error handling
     */
    public function wrapOperation(callable $operation, string $context = ''): array
    {
        try {
            $result = $operation();
            
            // Log success if context provided
            if ($context) {
                $this->logSuccess($context, ['result_type' => gettype($result)]);
            }
            
            return ['success' => true, 'data' => $result, 'error' => null];
            
        } catch (\Throwable $exception) {
            $errorMessage = $this->handleException($exception, $context);
            return ['success' => false, 'data' => null, 'error' => $errorMessage];
        }
    }
    
    /**
     * Create error response array for consistent API responses
     */
    public function createErrorResponse(string $message, array $details = []): array
    {
        return [
            'success' => false,
            'error' => $message,
            'details' => $details,
            'timestamp' => date('c'),
        ];
    }
    
    /**
     * Create success response array for consistent API responses
     */
    public function createSuccessResponse($data, string $message = ''): array
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'timestamp' => date('c'),
        ];
    }
}
