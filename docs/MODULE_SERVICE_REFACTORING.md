# Module Service Refactoring

## Overview

The `isModuleActive` method in `ThemeFunctions.php` has been refactored to improve separation of concerns and eliminate tight coupling to the database layer.

## Changes Made

### 1. Created ModuleService Class

**File:** `helper/ModuleService.php`

- Dedicated service for module-related operations
- Uses Omeka's API and ModuleManager instead of direct database access
- Implements caching at the service level
- Provides multiple methods for module checking

### 2. Created ModuleServiceFactory

**File:** `helper/ModuleServiceFactory.php`

- Factory for proper dependency injection
- Handles creation of ModuleService with required dependencies
- Follows Laminas service manager patterns

### 3. Refactored ThemeFunctions

**File:** `helper/ThemeFunctions.php`

- Added dependency injection support via setter method
- Removed direct database access
- Moved caching logic to ModuleService
- Maintained backward compatibility with fallback methods

## Benefits

### Separation of Concerns
- Module logic separated from view helper logic
- Database access abstracted behind service layer
- Caching logic centralized in appropriate service

### Improved Testability
- ModuleService can be easily mocked for testing
- Dependencies are explicit and injectable
- No hidden database dependencies

### Better Maintainability
- Single responsibility principle followed
- Clear interfaces between components
- Easier to modify module checking logic

### Performance
- Caching moved to service level for better efficiency
- API-based access is more efficient than direct SQL
- Reduced coupling to internal Omeka structures

## Usage

### Basic Usage (No Changes Required)
```php
// Existing code continues to work
$isActive = $this->themeFunctions()->isModuleActive('MyModule', '1.0.0');
```

### With Dependency Injection
```php
// In your service configuration
'view_helpers' => [
    'factories' => [
        'themeFunctions' => function ($container) {
            $helper = new \OmekaTheme\Helper\ThemeFunctions();
            $moduleService = $container->get('OmekaTheme\Helper\ModuleService');
            $helper->setModuleService($moduleService);
            return $helper;
        },
    ],
],
```

### Direct Service Usage
```php
// Use the service directly for more advanced operations
$moduleService = $container->get('OmekaTheme\Helper\ModuleService');

// Check if module is active
$isActive = $moduleService->isModuleActive('MyModule');

// Get all active modules
$activeModules = $moduleService->getActiveModules();

// Check specific version
$hasVersion = $moduleService->hasModuleVersion('MyModule', '2.1.0');

// Clear cache
$moduleService->clearCache();
```

## Migration Guide

### For Theme Developers
No changes required - existing `isModuleActive()` calls continue to work.

### For Advanced Users
Consider using the ModuleService directly for better performance and more features:

```php
// Old way (still works)
$isActive = $this->themeFunctions()->isModuleActive('MyModule');

// New way (recommended)
$moduleService = $this->getServiceLocator()->get('OmekaTheme\Helper\ModuleService');
$isActive = $moduleService->isModuleActive('MyModule');
```

## Configuration

Add the service configuration to your theme's configuration:

```php
// In config/module.config.php or similar
return [
    'service_manager' => [
        'factories' => [
            'OmekaTheme\Helper\ModuleService' => 'OmekaTheme\Helper\ModuleServiceFactory',
        ],
    ],
];
```

## Fallback Behavior

If the ModuleService cannot be created or injected, the system falls back to:
1. Checking for module view helpers (as suggested in the deprecation notice)
2. Basic API calls where possible
3. Graceful failure with `false` return value

This ensures backward compatibility and robustness in various deployment scenarios.
