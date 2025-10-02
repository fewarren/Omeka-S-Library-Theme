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

    /**
     * Construct the ThemeSettingsService with its required dependencies.
     */
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
     * Apply a named preset's values to a theme's settings for a given site.
     *
     * @param string|null $siteSlug The site slug to target, or null for global scope.
     * @param string $themeKey The theme settings key identifier.
     * @param string $preset The preset name to apply.
     * @throws \RuntimeException If the preset does not exist or preset validation fails.
     * @return array An array with two elements: the number of settings applied (int) and the updated settings array.
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
     * Persist the current theme settings for a site as the defaults for a named preset.
     *
     * @param string|null $siteSlug Target site slug, or `null` to operate on global settings.
     * @param string $themeKey Module theme key used to identify theme settings.
     * @param string $preset Name of the preset under which to store the defaults.
     * @return array An array with two elements: [number of stored settings, stored settings array].
     * @throws \RuntimeException If no settings are found for the target site/theme or if validation fails.
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
         * Apply stored preset defaults to the specified site (or global) theme settings.
         *
         * Retrieves stored defaults for the given preset and applies them to the target scope, returning the result.
         *
         * @param string|null $siteSlug The site slug to target, or null to apply globally.
         * @param string $preset The preset identifier whose stored defaults should be applied.
         * @return array Array with two elements: [0] => number of keys applied, [1] => the updated settings array.
         * @throws \RuntimeException If no stored defaults exist for the preset or the stored defaults are not valid JSON/array.
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
     * Retrieve a snapshot of the current theme settings for a site or the global scope.
     *
     * @param string|null $siteSlug The site slug to inspect, or `null` to inspect global settings.
     * @return array{
     *     site_slug: string|null,
     *     theme_slug: string,
     *     settings_count: int,
     *     settings: array
     * } An associative array with:
     *     - `site_slug`: the inspected site slug or `null` for global,
     *     - `theme_slug`: the resolved theme slug (or fallback),
     *     - `settings_count`: number of keys in the returned settings,
     *     - `settings`: the current theme settings as an associative array.
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
     * Compare the current theme settings for a site with a named preset.
     *
     * Compares each key in the preset against the site's current theme settings and reports matching and differing keys.
     *
     * @param string|null $siteSlug Site slug to inspect or null for global settings.
     * @param string $preset Name of the preset to compare against.
     * @return array{
     *     preset: string,
     *     total_preset_keys: int,
     *     matches: int,
     *     differences: int,
     *     matching_keys: array<string,mixed>,
     *     different_keys: array<string,array{current:mixed,preset:mixed}>
     * } Summary including preset name, counts of keys, map of matching keys to their values, and map of differing keys to current vs preset values.
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
         * Resolve a site entity by its slug or return null to indicate global scope.
         *
         * @param string|null $siteSlug The site slug to resolve; null indicates the global scope.
         * @return object|null The resolved site entity object, or null if no slug was provided.
         * @throws \RuntimeException If the API read fails; the exception message is produced by the error handler.
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
     * Return the settings instance scoped to the given site or the global settings.
     *
     * @param object|null $site Site entity with an id() method when provided, or null for global scope.
     * @return Settings|SiteSettings The SiteSettings instance configured for the given site, or the global Settings instance when no site is provided.
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
     * Get the theme slug for a site or the configured fallback.
     *
     * @param mixed $site Site entity (or `null`) that may expose a `theme()` method.
     * @return string The theme slug from the site when available, otherwise ModuleConfig::FALLBACK_THEME_SLUG.
     */
    private function getThemeSlug($site): string
    {
        return $site && method_exists($site, 'theme') && $site->theme()
            ? (string) $site->theme()
            : ModuleConfig::FALLBACK_THEME_SLUG;
    }

    /**
     * Retrieve the stored settings for a theme, falling back to container-style storage if necessary.
     *
     * Looks up the namespaced theme settings key first; if not present or not an array, it will use
     * a per-theme entry inside the theme settings container or, when that container is a flat array,
     * the container itself.
     *
     * @param \LibraryThemeStyles\Service\Settings|\LibraryThemeStyles\Service\SiteSettings $siteSettings Site-level or global settings instance to read from.
     * @param string $themeSlug The theme slug whose settings should be retrieved.
     * @return array The theme settings as an associative array, or an empty array if none are stored.
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
