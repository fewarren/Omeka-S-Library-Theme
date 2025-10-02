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
    
    /**
     * Initialize the error handler with an optional PSR-3 logger.
     *
     * If no logger is provided, a NullLogger is used.
     *
     * @param LoggerInterface|null $logger Optional PSR-3 logger to receive error and audit messages.
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }
    
    /**
     * Handle an exception by logging detailed error information and returning a user-facing message.
     *
     * The method logs an error-level entry containing the exception, generated error ID, file, line and stack trace,
     * then returns a message suitable for display to end users which includes the reference error ID for support.
     *
     * @param \Throwable $exception The exception to handle.
     * @param string $context Optional contextual label (operation, component, or request id) to include in logs.
     * @return string A user-facing error message that includes a reference error ID. 
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
     * Validates a preset name and returns an error message when the preset is invalid.
     *
     * @param string $preset The preset name to validate.
     * @return string|null An error message describing the invalid preset if validation fails, `null` otherwise.
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
     * Validate the provided site slug and return a module-specific error message when a required slug is missing.
     *
     * @param string|null $siteSlug The site identifier slug to validate; may be null or empty.
     * @param bool $required When true, an empty or null `$siteSlug` is considered invalid.
     * @return string|null A module-specific error message if validation fails, or `null` when the slug is accepted.
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
     * Validate theme settings and report any format or type violations.
     *
     * Validates that each setting value is a string. Keys containing `_color` are
     * checked for valid color format and keys containing `_font_size` are checked
     * for valid font-size format.
     *
     * @param array $settings Associative array of theme settings (key => value). Keys with `_color` or `_font_size` receive additional format validation.
     * @return string[] An array of error messages describing each invalid setting; empty if all settings are valid.
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
     * Create a user-facing API error message for an exception that occurred during an operation.
     *
     * @param \Throwable $exception The caught exception.
     * @param string $operation A short identifier or description of the operation where the error occurred.
     * @return string The module-specific API error message that includes the exception message.
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
     * Record a successful operation to the logger for auditing.
     *
     * Logs an informational entry that includes the operation name and optional contextual data.
     *
     * @param string $operation The name or short description of the successful operation.
     * @param array $context Optional key-value pairs with additional context to include in the log.
     */
    public function logSuccess(string $operation, array $context = []): void
    {
        $this->logger->info("LibraryThemeStyles: {$operation}", $context);
    }
    
    /**
     * Builds a user-facing error message tailored to the given exception and includes the error identifier.
     *
     * The returned message is suitable for displaying to end users and always contains the provided error ID.
     *
     * @param \Throwable $exception The exception to generate a message from.
     * @param string $errorId A unique error identifier to append to the message.
     * @return string The user-facing message including the error ID.
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
     * Execute a callable and return a standardized response array.
     *
     * Executes the provided operation. On success returns a response containing the operation result;
     * on exception logs the error and returns a response containing a user-facing error message.
     *
     * @param callable $operation The operation to execute.
     * @param string $context Optional context label used when logging success or errors.
     * @return array{
     *     success: bool,
     *     data: mixed|null,
     *     error: string|null
     * } A standardized response where `success` indicates outcome, `data` holds the result on success or `null` on failure, and `error` holds a user-facing error message or `null`.
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
     * Builds a standardized error response array for API consumers.
     *
     * @param string $message The error message to include in the response.
     * @param array $details Additional contextual details to include (optional).
     * @return array The response array with keys:
     *               - `success` (bool): false
     *               - `error` (string): the provided message
     *               - `details` (array): the provided details
     *               - `timestamp` (string): current ISO-8601 timestamp
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
     * Builds a standardized success response array for API responses.
     *
     * @param mixed  $data    The payload to include in the response.
     * @param string $message Optional human-readable message.
     * @return array The response array with keys:
     *               - 'success' => true
     *               - 'data' => the provided payload
     *               - 'message' => the provided message
     *               - 'timestamp' => ISO 8601 formatted timestamp
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
