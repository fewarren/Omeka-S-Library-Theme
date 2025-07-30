# Repository Pattern Refactoring

## Overview

The SQL queries in `ThemeFunctions.php` have been refactored to use the Repository pattern, improving separation of concerns, testability, and maintainability.

## Changes Made

### 1. Created SiteSettingsRepository Class

**File:** `helper/SiteSettingsRepository.php`

- Dedicated repository for site settings data access
- Encapsulates all SQL queries related to site settings
- Implements caching for improved performance
- Provides error handling and logging

### 2. Created SiteSettingsRepositoryFactory

**File:** `helper/SiteSettingsRepositoryFactory.php`

- Factory for proper dependency injection
- Handles creation of repository with database connection
- Follows Laminas service manager patterns

### 3. Refactored ThemeFunctions

**File:** `helper/ThemeFunctions.php`

- Removed direct SQL queries from helper methods
- Added dependency injection support for repository
- Maintained backward compatibility with fallback creation

## Benefits

### Separation of Concerns
- Data access logic separated from view helper logic
- SQL queries centralized in dedicated repository
- Business logic remains in helper, data access in repository

### Improved Testability
- Repository can be easily mocked for unit testing
- Database dependencies are explicit and injectable
- Helper methods can be tested without database

### Better Maintainability
- Single responsibility principle followed
- SQL queries are easier to find and modify
- Clear interfaces between components

### Performance Improvements
- Built-in caching at repository level
- Reduced duplicate queries
- Optimized query building

## Before vs After

### Before (Direct SQL in Helper)

```php
public function languageLists(string $type = null): ?array
{
    // ... setup code ...
    
    $sql = <<<'SQL'
SELECT
    site.slug AS site_slug,
    REPLACE(site_setting.value, '"', "") AS localeId
FROM site_setting
JOIN site ON site.id = site_setting.site_id
WHERE site_setting.id = :setting_id
ORDER BY site.id ASC
SQL;
    $bind = ['setting_id' => 'locale'];
    if ($isPublic) {
        $sql .= ' AND site.is_public = :is_public';
        $bind['is_public'] = 1;
    }

    $connection = $this->getServiceLocator()->get('Omeka\Connection');
    $localeSites = $connection->fetchAllKeyValue($sql, $bind);
    
    // ... rest of method ...
}
```

### After (Repository Pattern)

```php
public function languageLists(string $type = null): ?array
{
    // ... setup code ...
    
    // Use repository for data access instead of direct SQL
    $repository = $this->getSiteSettingsRepository();
    $localeSites = $repository->getLocaleSites($isPublic);
    
    // ... rest of method ...
}
```

## Repository Features

### Core Methods

```php
// Get locale sites with optional public filtering
$localeSites = $repository->getLocaleSites($publicOnly = false);

// Get single site setting
$value = $repository->getSiteSetting($siteId, $settingName);

// Get multiple site settings
$settings = $repository->getSiteSettings($siteId, $settingNames);

// Clear cache
$repository->clearCache();
```

### Caching System

- **Automatic caching** - Results cached by query parameters
- **Cache invalidation** - Methods to clear specific or all cache entries
- **Memory efficient** - Only caches what's actually used
- **Debug support** - Cache statistics for monitoring

### Error Handling

- **Exception catching** - Database errors handled gracefully
- **Logging** - Errors logged for debugging
- **Fallback behavior** - Returns empty arrays/null on errors
- **Robust operation** - Doesn't break application on database issues

## Usage Examples

### Basic Usage (No Changes Required)
```php
// Existing code continues to work
$languageLists = $this->themeFunctions()->languageLists();
```

### With Dependency Injection
```php
// In your service configuration
'view_helpers' => [
    'factories' => [
        'themeFunctions' => function ($container) {
            $helper = new \OmekaTheme\Helper\ThemeFunctions();
            
            // Inject repositories
            $siteSettingsRepository = $container->get('OmekaTheme\Helper\SiteSettingsRepository');
            $helper->setSiteSettingsRepository($siteSettingsRepository);
            
            return $helper;
        },
    ],
],
```

### Direct Repository Usage
```php
// Use the repository directly for advanced operations
$repository = $container->get('OmekaTheme\Helper\SiteSettingsRepository');

// Get locale sites for public sites only
$publicLocaleSites = $repository->getLocaleSites(true);

// Get specific site setting
$locale = $repository->getSiteSetting(1, 'locale');

// Get multiple settings at once
$settings = $repository->getSiteSettings(1, ['locale', 'theme', 'title']);

// Clear cache when needed
$repository->clearCache();
```

## Configuration

Add the repository to your service configuration:

```php
// In config/module.config.php or similar
return [
    'service_manager' => [
        'factories' => [
            'OmekaTheme\Helper\SiteSettingsRepository' => 'OmekaTheme\Helper\SiteSettingsRepositoryFactory',
        ],
    ],
];
```

## Performance Considerations

### Caching Strategy
- **Query-level caching** - Each unique query cached separately
- **Memory-based** - Fast access for repeated queries
- **Automatic invalidation** - Cache cleared when appropriate
- **Configurable** - Can be extended with Redis/Memcached

### Query Optimization
- **Prepared statements** - All queries use parameter binding
- **Efficient joins** - Optimized SQL structure
- **Minimal data transfer** - Only fetches required columns
- **Batch operations** - Multiple settings fetched in single query

## Testing

### Unit Testing Repository
```php
// Mock the database connection
$connectionMock = $this->createMock(Connection::class);
$repository = new SiteSettingsRepository($connectionMock);

// Test methods
$this->assertEquals([], $repository->getLocaleSites());
```

### Integration Testing
```php
// Test with real database
$repository = $container->get('OmekaTheme\Helper\SiteSettingsRepository');
$localeSites = $repository->getLocaleSites();
$this->assertIsArray($localeSites);
```

## Migration Guide

### For Theme Developers
No changes required - existing helper methods continue to work with the same API.

### For Advanced Users
Consider using the repository directly for better performance:

```php
// Old way (still works)
$languageLists = $this->themeFunctions()->languageLists();

// New way (more efficient for multiple calls)
$repository = $this->getServiceLocator()->get('OmekaTheme\Helper\SiteSettingsRepository');
$localeSites = $repository->getLocaleSites();
```

## Future Enhancements

### Planned Improvements
- **Redis/Memcached support** - Distributed caching
- **Query result pagination** - For large datasets
- **Bulk operations** - Batch updates and inserts
- **Event integration** - Cache invalidation on data changes

### Extension Points
- **Custom caching strategies** - Pluggable cache backends
- **Query interceptors** - Logging and monitoring hooks
- **Result transformers** - Custom data formatting
- **Connection pooling** - Multiple database support
