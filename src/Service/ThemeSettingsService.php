<?php declare(strict_types=1);

namespace LibraryThemeStyles\Service;

use Omeka\Api\Manager as ApiManager;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;
use LibraryThemeStyles\Config\ModuleConfig;

/**
 * Service for managing theme settings operations
 * Handles the business logic for applying presets and saving settings
 */
class ThemeSettingsService
{
    private ApiManager $api;
    private Settings $settings;
    private SiteSettings $siteSettings;
    private ErrorHandler $errorHandler;

    public function __construct(
        ApiManager $api,
        Settings $settings,
        SiteSettings $siteSettings,
        ErrorHandler $errorHandler
    ) {
        $this->api = $api;
        $this->settings = $settings;
        $this->siteSettings = $siteSettings;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Apply preset values to theme settings for a specific site
     */
    public function applyPresetToThemeSettings(?string $siteSlug, string $themeKey, string $preset): array
    {
        // Validate preset
        if (!PresetManager::hasPreset($preset)) {
            throw new \RuntimeException(ModuleConfig::getErrorMessage('unknown_preset', $preset));
        }
        
        $values = PresetManager::getPreset($preset);

        // Validate preset data
        $validationErrors = $this->errorHandler->validateThemeSettings($values);
        if (!empty($validationErrors)) {
            throw new \RuntimeException('Preset validation failed: ' . implode(', ', $validationErrors));
        }

        // Resolve site and settings scope
        $site = $this->resolveSite($siteSlug);
        $siteSettings = $this->getSiteSettingsInstance($site);
        
        // Get theme slug and settings key
        $themeSlug = $this->getThemeSlug($site);
        $key = ModuleConfig::getThemeSettingsKey($themeSlug);

        // Apply settings
        $current = $siteSettings->get($key, []);
        $current = is_array($current) ? $current : [];

        $count = 0;
        foreach ($values as $k => $v) {
            $current[$k] = $v;
            $count++;
        }
        
        $siteSettings->set($key, $current);

        $this->errorHandler->logSuccess('Applied preset to theme settings', [
            'preset' => $preset,
            'site_slug' => $siteSlug,
            'settings_count' => $count,
        ]);

        return [$count, $current];
    }

    /**
     * Save current theme settings as preset defaults
     */
    public function saveSettingsAsPresetDefaults(?string $siteSlug, string $themeKey, string $preset): array
    {
        // Resolve site and settings
        $site = $this->resolveSite($siteSlug);
        $siteSettings = $this->getSiteSettingsInstance($site);
        $themeSlug = $this->getThemeSlug($site);

        // Get current settings with fallback logic
        $stored = $this->getCurrentThemeSettings($siteSettings, $themeSlug);

        if (!is_array($stored) || empty($stored)) {
            throw new \RuntimeException(ModuleConfig::getErrorMessage('settings_not_found', $siteSlug ?? 'default'));
        }

        // Validate settings before saving
        $validationErrors = $this->errorHandler->validateThemeSettings($stored);
        if (!empty($validationErrors)) {
            throw new \RuntimeException('Settings validation failed: ' . implode(', ', $validationErrors));
        }

        // Persist into global settings as JSON (per-preset)
        $defaultsKey = ModuleConfig::getDefaultsKey($preset);
        $this->settings->set($defaultsKey, json_encode($stored));

        $this->errorHandler->logSuccess('Saved settings as preset defaults', [
            'preset' => $preset,
            'site_slug' => $siteSlug,
            'settings_count' => count($stored),
        ]);

        return [count($stored), $stored];
    }

    /**
     * Load stored defaults back into site settings
     */
    public function loadStoredDefaults(?string $siteSlug, string $preset): array
    {
        $defaultsKey = ModuleConfig::getDefaultsKey($preset);
        $storedJson = $this->settings->get($defaultsKey);
        
        if (!$storedJson) {
            throw new \RuntimeException("No stored defaults found for preset: {$preset}");
        }

        $storedDefaults = json_decode($storedJson, true);
        if (!is_array($storedDefaults)) {
            throw new \RuntimeException("Invalid stored defaults format for preset: {$preset}");
        }

        // Apply the stored defaults as if they were a preset
        return $this->applyPresetToThemeSettings($siteSlug, ModuleConfig::DEFAULT_THEME_KEY, $preset);
    }

    /**
     * Get current theme settings for inspection
     */
    public function inspectThemeSettings(?string $siteSlug): array
    {
        $site = $this->resolveSite($siteSlug);
        $siteSettings = $this->getSiteSettingsInstance($site);
        $themeSlug = $this->getThemeSlug($site);
        
        $settings = $this->getCurrentThemeSettings($siteSettings, $themeSlug);
        
        return [
            'site_slug' => $siteSlug,
            'theme_slug' => $themeSlug,
            'settings_count' => count($settings),
            'settings' => $settings,
        ];
    }

    /**
     * Compare current settings with a preset
     */
    public function compareWithPreset(?string $siteSlug, string $preset): array
    {
        $current = $this->inspectThemeSettings($siteSlug);
        $presetValues = PresetManager::getPreset($preset);
        
        $differences = [];
        $matches = [];
        
        foreach ($presetValues as $key => $presetValue) {
            $currentValue = $current['settings'][$key] ?? null;
            
            if ($currentValue === $presetValue) {
                $matches[$key] = $currentValue;
            } else {
                $differences[$key] = [
                    'current' => $currentValue,
                    'preset' => $presetValue,
                ];
            }
        }
        
        return [
            'preset' => $preset,
            'total_preset_keys' => count($presetValues),
            'matches' => count($matches),
            'differences' => count($differences),
            'matching_keys' => $matches,
            'different_keys' => $differences,
        ];
    }

    /**
     * Resolve site entity from slug
     */
    private function resolveSite(?string $siteSlug)
    {
        if (!$siteSlug) {
            return null;
        }

        try {
            return $this->api->read('sites', ['slug' => $siteSlug])->getContent();
        } catch (\Throwable $e) {
            throw new \RuntimeException($this->errorHandler->handleApiError($e, 'read site'));
        }
    }

    /**
     * Get appropriate settings instance (site or global)
     */
    private function getSiteSettingsInstance($site): Settings|SiteSettings
    {
        if ($site) {
            $this->siteSettings->setSiteId($site->id());
            return $this->siteSettings;
        }
        
        return $this->settings;
    }

    /**
     * Get theme slug from site or use fallback
     */
    private function getThemeSlug($site): string
    {
        return $site && method_exists($site, 'theme') && $site->theme()
            ? (string) $site->theme()
            : ModuleConfig::FALLBACK_THEME_SLUG;
    }

    /**
     * Get current theme settings with fallback logic
     */
    private function getCurrentThemeSettings($siteSettings, string $themeSlug): array
    {
        // Prefer namespaced theme settings; fall back to container variants
        $namespacedKey = ModuleConfig::getThemeSettingsKey($themeSlug);
        $stored = $siteSettings->get($namespacedKey, []);
        
        if (!is_array($stored) || empty($stored)) {
            $container = $siteSettings->get(ModuleConfig::THEME_SETTINGS_CONTAINER_KEY, []);
            if (is_array($container)) {
                if (isset($container[$themeSlug]) && is_array($container[$themeSlug])) {
                    $stored = $container[$themeSlug];
                } elseif (!empty($container)) {
                    $stored = $container; // flat array variant
                }
            }
        }
        
        return is_array($stored) ? $stored : [];
    }
}
