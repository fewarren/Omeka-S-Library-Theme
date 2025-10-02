<?php declare(strict_types=1);

namespace LibraryThemeStyles\Service;

use Omeka\Api\Manager as ApiManager;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;
use Laminas\Mvc\Controller\Plugin\Messenger;

/**
 * Service for handling module configuration form operations
 * 
 * This service extracts all business logic from Module::handleConfigForm()
 * and provides a clean, testable interface for configuration operations.
 */
class ModuleConfigService
{
    private ApiManager $api;
    private Settings $settings;
    private SiteSettings $siteSettings;
    private ThemeSettingsService $themeSettingsService;
    private array $presetMap;

    public function __construct(
        ApiManager $api,
        Settings $settings,
        SiteSettings $siteSettings,
        ThemeSettingsService $themeSettingsService,
        array $presetMap
    ) {
        $this->api = $api;
        $this->settings = $settings;
        $this->siteSettings = $siteSettings;
        $this->themeSettingsService = $themeSettingsService;
        $this->presetMap = $presetMap;
    }

    /**
     * Handle configuration form submission
     */
    public function handleConfigFormSubmission(array $data, Messenger $messenger): bool
    {
        $action = $data['action'] ?? null;
        $targetPreset = $data['target_preset'] ?? 'modern';
        $siteSlug = $data['site'] ?? null;
        $debug = !empty($data['debug']);
        $themeKey = 'LibraryTheme';

        try {
            return $this->processAction($action, $siteSlug, $targetPreset, $themeKey, $debug, $data, $messenger);
        } catch (\Throwable $e) {
            error_log('[LibraryThemeStyles] ERROR: ' . $e->getMessage());
            $messenger->addError('Error: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Process the specified action using a dispatcher pattern
     */
    private function processAction(
        ?string $action,
        ?string $siteSlug,
        string $targetPreset,
        string $themeKey,
        bool $debug,
        array $data,
        Messenger $messenger
    ): bool {
        switch ($action) {
            case 'inspect_theme_settings':
                return $this->handleInspectThemeSettings($siteSlug, $themeKey, $messenger);

            case 'verify_defaults_vs_settings':
                return $this->handleVerifyDefaultsVsSettings($siteSlug, $targetPreset, $messenger);

            case 'load_stored_defaults':
                return $this->handleLoadStoredDefaults($siteSlug, $targetPreset, $messenger);

            case 'inspect_key':
                return $this->handleInspectKey($siteSlug, $themeKey, $data, $messenger);

            case 'diff_vs_preset':
                return $this->handleDiffVsPreset($siteSlug, $themeKey, $targetPreset, $messenger);

            case 'load_defaults_into_settings':
                return $this->handleLoadDefaultsIntoSettings($siteSlug, $themeKey, $targetPreset, $debug, $messenger);

            case 'save_settings_as_defaults':
                return $this->handleSaveSettingsAsDefaults($siteSlug, $themeKey, $targetPreset, $debug, $messenger);

            default:
                $messenger->addWarning('No action selected.');
                return true;
        }
    }

    /**
     * Handle inspect theme settings action
     */
    private function handleInspectThemeSettings(?string $siteSlug, string $themeKey, Messenger $messenger): bool
    {
        if (!$this->validateSiteSlug($siteSlug, $messenger, 'Provide a Site Slug to inspect current theme settings.')) {
            return true;
        }

        $summary = $this->inspectThemeSettings($siteSlug, $themeKey);
        $messenger->addSuccess($summary);
        return true;
    }

    /**
     * Handle verify defaults vs settings action
     */
    private function handleVerifyDefaultsVsSettings(?string $siteSlug, string $targetPreset, Messenger $messenger): bool
    {
        if (!$this->validateSiteSlug($siteSlug, $messenger, 'Provide a Site Slug.')) {
            return true;
        }

        $report = $this->verifyDefaultsVsSettings($siteSlug, $targetPreset);
        $messenger->addSuccess($report);
        return true;
    }

    /**
     * Handle load stored defaults action
     */
    private function handleLoadStoredDefaults(?string $siteSlug, string $targetPreset, Messenger $messenger): bool
    {
        if (!$this->validateSiteSlug($siteSlug, $messenger, 'Provide a Site Slug.')) {
            return true;
        }

        [$count, $msg] = $this->loadStoredDefaultsIntoSettings($siteSlug, $targetPreset);
        $messenger->addSuccess(sprintf('Loaded %d stored default keys into settings. %s', $count, $msg));
        return true;
    }

    /**
     * Handle inspect key action
     */
    private function handleInspectKey(?string $siteSlug, string $themeKey, array $data, Messenger $messenger): bool
    {
        if (!$this->validateSiteSlug($siteSlug, $messenger, 'Provide a Site Slug.')) {
            return true;
        }

        $key = trim((string)($data['inspect_key'] ?? ''));
        if ($key === '') {
            $messenger->addError('Provide a setting key to inspect.');
            return true;
        }

        $value = $this->inspectSingleKey($siteSlug, $themeKey, $key);
        $messenger->addSuccess(sprintf('Inspect key %s: %s', $key, json_encode($value)));
        return true;
    }

    /**
     * Handle diff vs preset action
     */
    private function handleDiffVsPreset(?string $siteSlug, string $themeKey, string $targetPreset, Messenger $messenger): bool
    {
        if (!$this->validateSiteSlug($siteSlug, $messenger, 'Provide a Site Slug.')) {
            return true;
        }

        $target = $this->diffVsPreset($siteSlug, $themeKey, $targetPreset);
        $messenger->addSuccess('Diff vs preset (first 15): ' . $target);
        return true;
    }

    /**
     * Handle load defaults into settings action
     */
    private function handleLoadDefaultsIntoSettings(
        ?string $siteSlug,
        string $themeKey,
        string $targetPreset,
        bool $debug,
        Messenger $messenger
    ): bool {
        if (!$this->validateSiteSlug($siteSlug, $messenger, 'Please provide a Site Slug to load defaults into LibraryTheme settings.')) {
            return true;
        }

        $before = $debug ? $this->countThemeSettings($siteSlug, $themeKey) : null;
        [$count] = $this->applyPresetToThemeSettings($siteSlug, $themeKey, $targetPreset);
        $after = $debug ? $this->countThemeSettings($siteSlug, $themeKey) : null;

        $messenger->addSuccess(sprintf('Loaded %d %s preset defaults into LibraryTheme settings for site "%s".', $count, $targetPreset, $siteSlug));
        
        if ($debug) {
            $messenger->addSuccess(sprintf('Debug: theme_settings_%s count before=%d after=%d', $themeKey, $before, $after));
        }
        
        return true;
    }

    /**
     * Handle save settings as defaults action
     */
    private function handleSaveSettingsAsDefaults(
        ?string $siteSlug,
        string $themeKey,
        string $targetPreset,
        bool $debug,
        Messenger $messenger
    ): bool {
        if (!$this->validateSiteSlug($siteSlug, $messenger, 'Please provide a Site Slug to save current settings as preset defaults.')) {
            return true;
        }

        [$count, $current] = $this->saveSettingsAsPresetDefaults($siteSlug, $themeKey, $targetPreset);
        $messenger->addSuccess(sprintf('Saved current LibraryTheme settings as %s preset defaults (%d fields).', $targetPreset, $count));
        
        if ($debug) {
            $messenger->addSuccess('Debug: stored defaults sample: ' . substr(json_encode($current), 0, 300) . '...');
        }
        
        return true;
    }

    /**
     * Validate site slug and add error message if invalid
     */
    private function validateSiteSlug(?string $siteSlug, Messenger $messenger, string $errorMessage): bool
    {
        if (!$siteSlug) {
            $messenger->addError($errorMessage);
            return false;
        }
        return true;
    }

    /**
     * Apply preset to theme settings
     * Delegates to ThemeSettingsService to eliminate code duplication
     */
    private function applyPresetToThemeSettings(?string $siteSlug, string $themeKey, string $preset): array
    {
        return $this->themeSettingsService->applyPresetToThemeSettings($siteSlug, $themeKey, $preset);
    }

    /**
     * Save settings as preset defaults
     * Delegates to ThemeSettingsService to eliminate code duplication
     */
    private function saveSettingsAsPresetDefaults(?string $siteSlug, string $themeKey, string $preset): array
    {
        return $this->themeSettingsService->saveSettingsAsPresetDefaults($siteSlug, $themeKey, $preset);
    }

    /**
     * Count theme settings
     * Delegates to ThemeSettingsService to eliminate code duplication
     */
    private function countThemeSettings(string $siteSlug, string $themeKey): int
    {
        return $this->themeSettingsService->countThemeSettings($siteSlug, $themeKey);
    }

    /**
     * Inspect single key
     * Delegates to ThemeSettingsService to eliminate code duplication
     */
    private function inspectSingleKey(string $siteSlug, string $themeKey, string $key)
    {
        return $this->themeSettingsService->inspectSingleKey($siteSlug, $themeKey, $key);
    }

    /**
     * Diff vs preset
     * Delegates to ThemeSettingsService to eliminate code duplication
     */
    private function diffVsPreset(string $siteSlug, string $themeKey, string $preset): string
    {
        return $this->themeSettingsService->diffVsPreset($siteSlug, $themeKey, $preset);
    }

    /**
     * Inspect theme settings
     * Delegates to ThemeSettingsService to eliminate code duplication
     */
    private function inspectThemeSettings(string $siteSlug, string $themeKey): string
    {
        return $this->themeSettingsService->inspectThemeSettings($siteSlug, $themeKey);
    }

    /**
     * Get stored defaults
     */
    private function getStoredDefaults(string $preset): array
    {
        $raw = $this->settings->get('LibraryThemeStyles_defaults_' . $preset);
        if (!$raw) return [];
        $arr = json_decode((string)$raw, true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * Verify defaults vs settings
     * Delegates to ThemeSettingsService to eliminate code duplication
     */
    private function verifyDefaultsVsSettings(string $siteSlug, string $preset): string
    {
        return $this->themeSettingsService->verifyDefaultsVsSettings($siteSlug, $preset);
    }

    /**
     * Load stored defaults into settings
     * Delegates to ThemeSettingsService to eliminate code duplication
     */
    private function loadStoredDefaultsIntoSettings(string $siteSlug, string $preset): array
    {
        return $this->themeSettingsService->loadStoredDefaultsIntoSettings($siteSlug, $preset);
    }


}
