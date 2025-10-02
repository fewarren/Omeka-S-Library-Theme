<?php declare(strict_types=1);

namespace LibraryThemeStyles\Service;

use Omeka\Api\Manager as ApiManager;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;

/**
 * Service for managing theme settings operations
 * 
 * This service handles the business logic for applying presets, saving settings,
 * and other theme-related operations. It eliminates code duplication by centralizing
 * all theme settings logic in one place.
 */
class ThemeSettingsService
{
    private ApiManager $api;
    private Settings $settings;
    private SiteSettings $siteSettings;
    private array $presetMap;

    public function __construct(
        ApiManager $api,
        Settings $settings,
        SiteSettings $siteSettings,
        array $presetMap
    ) {
        $this->api = $api;
        $this->settings = $settings;
        $this->siteSettings = $siteSettings;
        $this->presetMap = $presetMap;
    }

    /**
     * Apply preset values to theme settings for a specific site
     * 
     * @param string|null $siteSlug Site slug or null for global settings
     * @param string $themeKey Theme key (used for theme resolution if needed)
     * @param string $preset Preset name (modern, traditional)
     * @return array [count, values] - Number of settings applied and the preset values
     * @throws \RuntimeException If preset is unknown or site cannot be resolved
     */
    public function applyPresetToThemeSettings(?string $siteSlug, string $themeKey, string $preset): array
    {
        // Validate preset
        if (!isset($this->presetMap[$preset])) {
            throw new \RuntimeException('Unknown preset: ' . $preset);
        }
        $values = $this->presetMap[$preset];

        // Resolve site and set target
        $site = $this->resolveSite($siteSlug);
        if ($site) {
            $this->siteSettings->setTargetId($site->id());
        }

        // Get theme slug - use themeKey parameter for theme resolution
        $themeSlug = $this->getThemeSlug($site, $themeKey);

        // Read current theme settings containers
        $container = $this->siteSettings->get('theme_settings', []);
        if (!is_array($container)) { 
            $container = []; 
        }
        
        $namespacedKey = 'theme_settings_' . $themeSlug;
        $namespaced = $this->siteSettings->get($namespacedKey, []);
        if (!is_array($namespaced)) { 
            $namespaced = []; 
        }

        // Decide if theme_settings is a map keyed by theme slug or a flat array
        $isMap = isset($container[$themeSlug]) && is_array($container[$themeSlug]);
        if ($isMap) {
            $target = $container[$themeSlug];
        } else {
            $target = $container; // flat
        }

        // Merge preset values
        $count = 0;
        foreach ($values as $k => $v) {
            $target[$k] = $v;
            $namespaced[$k] = $v;
            $count++;
        }

        // Persist back
        if ($isMap) {
            $container[$themeSlug] = $target;
            $this->siteSettings->set('theme_settings', $container);
        } else {
            $this->siteSettings->set('theme_settings', $target);
        }
        $this->siteSettings->set($namespacedKey, $namespaced);

        return [$count, $values];
    }

    /**
     * Save current theme settings as preset defaults
     * 
     * @param string|null $siteSlug Site slug or null for global settings
     * @param string $themeKey Theme key (used for theme resolution if needed)
     * @param string $preset Preset name to save settings under
     * @return array [count, current] - Number of settings saved and the current settings
     */
    public function saveSettingsAsPresetDefaults(?string $siteSlug, string $themeKey, string $preset): array
    {
        // Resolve site and set target
        $site = $this->resolveSite($siteSlug);
        if ($site) {
            $this->siteSettings->setTargetId($site->id());
        }

        // Get theme slug - use themeKey parameter for theme resolution
        $themeSlug = $this->getThemeSlug($site, $themeKey);

        // Get current settings with fallback logic
        $current = $this->getCurrentThemeSettings($themeSlug);

        if (!is_array($current) || empty($current)) {
            return [0, []];
        }

        // Persist into global settings as JSON (per-preset)
        $defaultsKey = 'LibraryThemeStyles_defaults_' . $preset;
        $this->settings->set($defaultsKey, json_encode($current));
        
        return [count($current), $current];
    }

    /**
     * Load stored defaults back into site settings
     * 
     * @param string|null $siteSlug Site slug or null for global settings
     * @param string $preset Preset name to load defaults from
     * @return array [count, message] - Number of settings loaded and status message
     */
    public function loadStoredDefaultsIntoSettings(?string $siteSlug, string $preset): array
    {
        // Resolve site and set target
        $site = $this->resolveSite($siteSlug);
        if ($site) {
            $this->siteSettings->setTargetId($site->id());
        }

        $themeSlug = $this->getThemeSlug($site);
        $key = 'theme_settings_' . $themeSlug;
        $current = $this->siteSettings->get($key, []);
        $current = is_array($current) ? $current : [];
        $defaults = $this->getStoredDefaults($preset);

        $count = 0;
        foreach ($defaults as $k => $v) {
            $current[$k] = $v;
            $count++;
        }
        
        $this->siteSettings->set($key, $current);
        return [$count, sprintf('theme=%s key=%s now has %d keys', $themeSlug, $key, count($current))];
    }

    /**
     * Count theme settings for a site
     * 
     * @param string $siteSlug Site slug
     * @param string $themeKey Theme key (used for theme resolution if needed)
     * @return int Number of theme settings
     */
    public function countThemeSettings(string $siteSlug, string $themeKey): int
    {
        $site = $this->resolveSite($siteSlug);
        if ($site) {
            $this->siteSettings->setTargetId($site->id());
        }

        $themeSlug = $this->getThemeSlug($site, $themeKey);
        $namespaced = $this->siteSettings->get('theme_settings_' . $themeSlug, []);
        
        if (is_array($namespaced)) {
            return count($namespaced);
        }
        
        $container = $this->siteSettings->get('theme_settings', []);
        if (is_array($container)) {
            if (isset($container[$themeSlug]) && is_array($container[$themeSlug])) {
                return count($container[$themeSlug]);
            }
            return count($container);
        }
        
        return 0;
    }

    /**
     * Inspect a single setting key
     * 
     * @param string $siteSlug Site slug
     * @param string $themeKey Theme key (used for theme resolution if needed)
     * @param string $key Setting key to inspect
     * @return mixed Setting value or null if not found
     */
    public function inspectSingleKey(string $siteSlug, string $themeKey, string $key)
    {
        $site = $this->resolveSite($siteSlug);
        if ($site) {
            $this->siteSettings->setTargetId($site->id());
        }

        $themeSlug = $this->getThemeSlug($site, $themeKey);
        $namespaced = $this->siteSettings->get('theme_settings_' . $themeSlug, []);
        
        if (is_array($namespaced) && array_key_exists($key, $namespaced)) {
            return $namespaced[$key];
        }
        
        $container = $this->siteSettings->get('theme_settings', []);
        if (is_array($container)) {
            if (isset($container[$themeSlug]) && is_array($container[$themeSlug]) && array_key_exists($key, $container[$themeSlug])) {
                return $container[$themeSlug][$key];
            }
            if (array_key_exists($key, $container)) {
                return $container[$key];
            }
        }
        
        return null;
    }

    /**
     * Compare current settings with a preset
     * 
     * @param string $siteSlug Site slug
     * @param string $themeKey Theme key (used for theme resolution if needed)
     * @param string $preset Preset name to compare against
     * @return string Formatted difference string
     */
    public function diffVsPreset(string $siteSlug, string $themeKey, string $preset): string
    {
        $site = $this->resolveSite($siteSlug);
        if ($site) {
            $this->siteSettings->setTargetId($site->id());
        }

        $themeSlug = $this->getThemeSlug($site, $themeKey);
        $current = $this->siteSettings->get('theme_settings_' . $themeSlug, []);
        $want = $this->presetMap[$preset] ?? [];
        
        $diffs = [];
        foreach ($want as $k => $v) {
            $cv = $current[$k] ?? null;
            if ($cv !== $v) {
                $diffs[] = $k . ':' . json_encode($cv) . ' -> ' . json_encode($v);
            }
        }
        
        return implode(', ', array_slice($diffs, 0, 15));
    }

    /**
     * Inspect theme settings and return formatted summary
     * 
     * @param string $siteSlug Site slug
     * @param string $themeKey Theme key (used for theme resolution if needed)
     * @return string Formatted inspection summary
     */
    public function inspectThemeSettings(string $siteSlug, string $themeKey): string
    {
        $site = $this->resolveSite($siteSlug);
        if ($site) {
            $this->siteSettings->setTargetId($site->id());
        }

        $themeSlug = $this->getThemeSlug($site, $themeKey);
        $namespacedKey = 'theme_settings_' . $themeSlug;
        $namespaced = $this->siteSettings->get($namespacedKey, []);
        $namespacedCount = is_array($namespaced) ? count($namespaced) : 0;

        $container = $this->siteSettings->get('theme_settings', []);
        $containerInfo = 'N/A';
        $containerCount = 0;
        
        if (is_array($container)) {
            if (isset($container[$themeSlug]) && is_array($container[$themeSlug])) {
                $containerCount = count($container[$themeSlug]);
                $containerInfo = 'map[' . $themeSlug . ']';
            } else {
                $containerCount = count($container);
                $containerInfo = 'flat';
            }
        }
        
        $sampleKeys = is_array($namespaced) ? implode(', ', array_slice(array_keys($namespaced), 0, 15)) : 'N/A';
        
        return sprintf(
            'Inspect: %s has %d keys; theme_settings (%s) has %d keys. Sample (namespaced): %s',
            $namespacedKey,
            $namespacedCount,
            $containerInfo,
            $containerCount,
            $sampleKeys
        );
    }

    /**
     * Verify stored defaults against current settings
     * 
     * @param string $siteSlug Site slug
     * @param string $preset Preset name to verify against
     * @return string Formatted verification report
     */
    public function verifyDefaultsVsSettings(string $siteSlug, string $preset): string
    {
        $site = $this->resolveSite($siteSlug);
        if ($site) {
            $this->siteSettings->setTargetId($site->id());
        }

        $themeSlug = $this->getThemeSlug($site);
        $namespaced = $this->siteSettings->get('theme_settings_' . $themeSlug, []);
        $namespaced = is_array($namespaced) ? $namespaced : [];
        $defaults = $this->getStoredDefaults($preset);

        $missingInDefaults = [];
        $missingInSettings = [];
        $diffs = [];
        
        foreach ($namespaced as $k => $v) {
            if (!array_key_exists($k, $defaults)) {
                $missingInDefaults[] = $k;
            }
        }
        
        foreach ($defaults as $k => $v) {
            if (!array_key_exists($k, $namespaced)) {
                $missingInSettings[] = $k;
            } elseif ($namespaced[$k] !== $v) {
                $diffs[] = $k . ':' . json_encode($namespaced[$k]) . ' != ' . json_encode($v);
            }
        }
        
        return sprintf(
            'Verify: settings=%d, defaults=%d, missingInDefaults=%d, missingInSettings=%d, diffs=%d. Samples: missingInDefaults=[%s]; missingInSettings=[%s]; diffs=[%s]',
            count($namespaced),
            count($defaults),
            count($missingInDefaults),
            count($missingInSettings),
            count($diffs),
            implode(', ', array_slice($missingInDefaults, 0, 10)),
            implode(', ', array_slice($missingInSettings, 0, 10)),
            implode(', ', array_slice($diffs, 0, 10))
        );
    }

    /**
     * Resolve site entity from slug
     * 
     * @param string|null $siteSlug Site slug or null
     * @return mixed Site entity or null
     * @throws \RuntimeException If site cannot be found
     */
    private function resolveSite(?string $siteSlug)
    {
        if (!$siteSlug) {
            return null;
        }

        try {
            return $this->api->read('sites', ['slug' => $siteSlug])->getContent();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Site not found: ' . $siteSlug);
        }
    }

    /**
     * Get theme slug from site or use fallback
     * 
     * @param mixed $site Site entity or null
     * @param string|null $themeKey Theme key for resolution (now used)
     * @return string Theme slug
     */
    private function getThemeSlug($site = null, ?string $themeKey = null): string
    {
        // First try to get theme from site
        if ($site && method_exists($site, 'theme') && $site->theme()) {
            return (string) $site->theme();
        }
        
        // If themeKey is provided and looks like a theme slug, use it
        if ($themeKey && $themeKey !== 'LibraryTheme') {
            return strtolower(str_replace(' ', '-', $themeKey));
        }
        
        // Try to get theme from site settings
        try {
            $slug = $this->siteSettings->get('theme');
            if (is_string($slug) && $slug !== '') {
                return $slug;
            }
        } catch (\Throwable $e) {
            // Fall through to default
        }
        
        // Default fallback
        return 'library-theme';
    }

    /**
     * Get current theme settings with fallback logic
     * 
     * @param string $themeSlug Theme slug
     * @return array Current theme settings
     */
    private function getCurrentThemeSettings(string $themeSlug): array
    {
        // Prefer namespaced settings; fall back to container (map or flat)
        $namespacedKey = 'theme_settings_' . $themeSlug;
        $current = $this->siteSettings->get($namespacedKey, []);
        
        if (!is_array($current) || empty($current)) {
            $container = $this->siteSettings->get('theme_settings', []);
            if (is_array($container)) {
                if (isset($container[$themeSlug]) && is_array($container[$themeSlug])) {
                    $current = $container[$themeSlug];
                } elseif (!empty($container)) {
                    $current = $container; // flat array variant
                }
            }
        }
        
        return is_array($current) ? $current : [];
    }

    /**
     * Get stored defaults for a preset
     * 
     * @param string $preset Preset name
     * @return array Stored defaults or empty array
     */
    private function getStoredDefaults(string $preset): array
    {
        $raw = $this->settings->get('LibraryThemeStyles_defaults_' . $preset);
        if (!$raw) {
            return [];
        }
        
        $arr = json_decode((string)$raw, true);
        return is_array($arr) ? $arr : [];
    }
}
