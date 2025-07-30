<?php declare(strict_types=1);

namespace OmekaTheme\Helper;

use Doctrine\DBAL\Connection;

/**
 * Repository for site settings data access
 * 
 * This repository handles all database queries related to site settings,
 * providing a clean separation of concerns and improved testability.
 */
class SiteSettingsRepository
{
    private Connection $connection;
    private array $cache = [];
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * Get locale sites mapping with optional public site filtering
     * 
     * @param bool $publicOnly Whether to filter for public sites only
     * @return array Array of site_slug => locale_id pairs
     */
    public function getLocaleSites(bool $publicOnly = false): array
    {
        $cacheKey = 'locale_sites_' . ($publicOnly ? 'public' : 'all');
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $sql = $this->buildLocaleSitesQuery($publicOnly);
        $bind = $this->buildLocaleSitesBindings($publicOnly);
        
        try {
            $localeSites = $this->connection->fetchAllKeyValue($sql, $bind);
            // Filter out empty locales
            $localeSites = array_filter($localeSites);
            
            // Cache the result
            $this->cache[$cacheKey] = $localeSites;
            
            return $localeSites;
            
        } catch (\Exception $e) {
            // Log error and return empty array
            error_log('Failed to fetch locale sites: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get site setting value by site ID and setting name
     * 
     * @param int $siteId Site identifier
     * @param string $settingName Setting name
     * @return string|null Setting value or null if not found
     */
    public function getSiteSetting(int $siteId, string $settingName): ?string
    {
        $cacheKey = "site_setting_{$siteId}_{$settingName}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $sql = 'SELECT value FROM site_setting WHERE site_id = :site_id AND id = :setting_name';
        $bind = [
            'site_id' => $siteId,
            'setting_name' => $settingName
        ];
        
        try {
            $value = $this->connection->fetchOne($sql, $bind);
            $result = $value !== false ? (string) $value : null;
            
            // Cache the result
            $this->cache[$cacheKey] = $result;
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("Failed to fetch site setting {$settingName} for site {$siteId}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get multiple site settings for a specific site
     * 
     * @param int $siteId Site identifier
     * @param array $settingNames Array of setting names
     * @return array Array of setting_name => value pairs
     */
    public function getSiteSettings(int $siteId, array $settingNames): array
    {
        if (empty($settingNames)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($settingNames) - 1) . '?';
        $sql = "SELECT id, value FROM site_setting WHERE site_id = ? AND id IN ({$placeholders})";
        $bind = array_merge([$siteId], $settingNames);
        
        try {
            $results = $this->connection->fetchAllKeyValue($sql, $bind);
            
            // Cache individual results
            foreach ($results as $settingName => $value) {
                $cacheKey = "site_setting_{$siteId}_{$settingName}";
                $this->cache[$cacheKey] = $value;
            }
            
            return $results;
            
        } catch (\Exception $e) {
            error_log("Failed to fetch site settings for site {$siteId}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear the repository cache
     * 
     * @param string|null $pattern Optional pattern to clear specific cache entries
     * @return void
     */
    public function clearCache(?string $pattern = null): void
    {
        if ($pattern === null) {
            $this->cache = [];
        } else {
            foreach (array_keys($this->cache) as $key) {
                if (strpos($key, $pattern) !== false) {
                    unset($this->cache[$key]);
                }
            }
        }
    }
    
    /**
     * Build the SQL query for locale sites
     * 
     * @param bool $publicOnly Whether to filter for public sites only
     * @return string SQL query
     */
    private function buildLocaleSitesQuery(bool $publicOnly): string
    {
        $sql = <<<'SQL'
SELECT
    site.slug AS site_slug,
    REPLACE(site_setting.value, '"', "") AS localeId
FROM site_setting
JOIN site ON site.id = site_setting.site_id
WHERE site_setting.id = :setting_id
SQL;
        
        if ($publicOnly) {
            $sql .= ' AND site.is_public = :is_public';
        }
        
        $sql .= ' ORDER BY site.id ASC';
        
        return $sql;
    }
    
    /**
     * Build the parameter bindings for locale sites query
     * 
     * @param bool $publicOnly Whether to filter for public sites only
     * @return array Parameter bindings
     */
    private function buildLocaleSitesBindings(bool $publicOnly): array
    {
        $bind = ['setting_id' => 'locale'];
        
        if ($publicOnly) {
            $bind['is_public'] = 1;
        }
        
        return $bind;
    }
    
    /**
     * Get cache statistics for debugging
     * 
     * @return array Cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'entries' => count($this->cache),
            'keys' => array_keys($this->cache),
            'memory_usage' => memory_get_usage()
        ];
    }
}
