<?php declare(strict_types=1);

namespace LibraryThemeStyles\Service;

use Omeka\Api\Manager as ApiManager;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;
use Omeka\Mvc\Controller\Plugin\Messenger;
use LibraryThemeStyles\Config\ModuleConfig;

/**
 * Service for handling module configuration operations
 * Centralizes business logic for module configuration form handling
 */
class ModuleConfigService
{
    private ApiManager $api;
    private Settings $settings;
    private SiteSettings $siteSettings;
    private ThemeSettingsService $themeSettingsService;
    private ErrorHandler $errorHandler;

    public function __construct(
        ApiManager $api,
        Settings $settings,
        SiteSettings $siteSettings,
        ThemeSettingsService $themeSettingsService,
        ErrorHandler $errorHandler
    ) {
        $this->api = $api;
        $this->settings = $settings;
        $this->siteSettings = $siteSettings;
        $this->themeSettingsService = $themeSettingsService;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Handle configuration form submission
     */
    public function handleConfigFormSubmission(array $data, Messenger $messenger): bool
    {
        $action = $data['action'] ?? null;
        $targetPreset = $data['target_preset'] ?? ModuleConfig::DEFAULT_PRESET;
        $siteSlug = $data['site'] ?? null;
        $debug = !empty($data['debug']);

        try {
            return $this->processAction($action, $targetPreset, $siteSlug, $debug, $data, $messenger);
        } catch (\Throwable $e) {
            $errorMessage = $this->errorHandler->handleException($e, 'module_config_form_submission');
            $messenger->addError($errorMessage);
            return true;
        }
    }

    /**
     * Process specific action
     */
    private function processAction(
        ?string $action,
        string $targetPreset,
        ?string $siteSlug,
        bool $debug,
        array $data,
        Messenger $messenger
    ): bool {
        switch ($action) {
            case 'inspect_theme_settings':
                return $this->handleInspectThemeSettings($siteSlug, $messenger);

            case 'verify_defaults_vs_settings':
                return $this->handleVerifyDefaultsVsSettings($siteSlug, $targetPreset, $messenger);

            case 'load_stored_defaults':
                return $this->handleLoadStoredDefaults($siteSlug, $targetPreset, $messenger);

            case 'inspect_key':
                return $this->handleInspectKey($siteSlug, $data, $messenger);

            case 'diff_vs_preset':
                return $this->handleDiffVsPreset($siteSlug, $targetPreset, $messenger);

            case 'load_defaults_into_settings':
                return $this->handleLoadDefaultsIntoSettings($siteSlug, $targetPreset, $debug, $messenger);

            case 'save_settings_as_defaults':
                return $this->handleSaveSettingsAsDefaults($siteSlug, $targetPreset, $debug, $messenger);

            default:
                $messenger->addWarning('No action selected.');
                return true;
        }
    }

    /**
     * Handle inspect theme settings action
     */
    private function handleInspectThemeSettings(?string $siteSlug, Messenger $messenger): bool
    {
        if ($siteError = $this->errorHandler->validateSiteSlug($siteSlug)) {
            $messenger->addError($siteError);
            return true;
        }

        $result = $this->errorHandler->wrapOperation(
            fn() => $this->themeSettingsService->inspectThemeSettings($siteSlug),
            'inspect_theme_settings'
        );

        if ($result['success']) {
            $data = $result['data'];
            $summary = sprintf(
                'Inspect: Site "%s" (theme: %s) has %d theme settings. Sample keys: %s',
                $data['site_slug'] ?? 'default',
                $data['theme_slug'],
                $data['settings_count'],
                implode(', ', array_slice(array_keys($data['settings']), 0, 15))
            );
            $messenger->addSuccess($summary);
        } else {
            $messenger->addError($result['error']);
        }

        return true;
    }

    /**
     * Handle verify defaults vs settings action
     */
    private function handleVerifyDefaultsVsSettings(?string $siteSlug, string $targetPreset, Messenger $messenger): bool
    {
        if ($siteError = $this->errorHandler->validateSiteSlug($siteSlug)) {
            $messenger->addError($siteError);
            return true;
        }

        $result = $this->errorHandler->wrapOperation(
            fn() => $this->themeSettingsService->compareWithPreset($siteSlug, $targetPreset),
            'verify_defaults_vs_settings'
        );

        if ($result['success']) {
            $data = $result['data'];
            $report = sprintf(
                'Verify: %d preset keys, %d matches, %d differences. Sample differences: %s',
                $data['total_preset_keys'],
                $data['matches'],
                $data['differences'],
                implode(', ', array_slice(array_keys($data['different_keys']), 0, 10))
            );
            $messenger->addSuccess($report);
        } else {
            $messenger->addError($result['error']);
        }

        return true;
    }

    /**
     * Handle load stored defaults action
     */
    private function handleLoadStoredDefaults(?string $siteSlug, string $targetPreset, Messenger $messenger): bool
    {
        if ($siteError = $this->errorHandler->validateSiteSlug($siteSlug)) {
            $messenger->addError($siteError);
            return true;
        }

        $result = $this->errorHandler->wrapOperation(
            fn() => $this->themeSettingsService->loadStoredDefaults($siteSlug, $targetPreset),
            'load_stored_defaults'
        );

        if ($result['success']) {
            [$count, $details] = $result['data'];
            $messenger->addSuccess(sprintf('Loaded %d stored default keys into settings.', $count));
        } else {
            $messenger->addError($result['error']);
        }

        return true;
    }

    /**
     * Handle inspect key action
     */
    private function handleInspectKey(?string $siteSlug, array $data, Messenger $messenger): bool
    {
        if ($siteError = $this->errorHandler->validateSiteSlug($siteSlug)) {
            $messenger->addError($siteError);
            return true;
        }

        $key = trim((string)($data['inspect_key'] ?? ''));
        if ($key === '') {
            $messenger->addError('Provide a setting key to inspect.');
            return true;
        }

        $result = $this->errorHandler->wrapOperation(
            fn() => $this->inspectSingleKey($siteSlug, $key),
            'inspect_single_key'
        );

        if ($result['success']) {
            $value = $result['data'];
            $messenger->addSuccess(sprintf('Inspect key %s: %s', $key, json_encode($value)));
        } else {
            $messenger->addError($result['error']);
        }

        return true;
    }

    /**
     * Handle diff vs preset action
     */
    private function handleDiffVsPreset(?string $siteSlug, string $targetPreset, Messenger $messenger): bool
    {
        if ($siteError = $this->errorHandler->validateSiteSlug($siteSlug)) {
            $messenger->addError($siteError);
            return true;
        }

        $result = $this->errorHandler->wrapOperation(
            fn() => $this->themeSettingsService->compareWithPreset($siteSlug, $targetPreset),
            'diff_vs_preset'
        );

        if ($result['success']) {
            $data = $result['data'];
            $differences = array_slice($data['different_keys'], 0, 15);
            $diffStrings = [];
            foreach ($differences as $key => $diff) {
                $diffStrings[] = $key . ':' . json_encode($diff['current']) . ' -> ' . json_encode($diff['preset']);
            }
            $messenger->addSuccess('Diff vs preset (first 15): ' . implode(', ', $diffStrings));
        } else {
            $messenger->addError($result['error']);
        }

        return true;
    }

    /**
     * Handle load defaults into settings action
     */
    private function handleLoadDefaultsIntoSettings(?string $siteSlug, string $targetPreset, bool $debug, Messenger $messenger): bool
    {
        if ($siteError = $this->errorHandler->validateSiteSlug($siteSlug)) {
            $messenger->addError($siteError);
            return true;
        }

        $before = $debug ? $this->countThemeSettings($siteSlug) : null;

        $result = $this->errorHandler->wrapOperation(
            fn() => $this->themeSettingsService->applyPresetToThemeSettings($siteSlug, ModuleConfig::DEFAULT_THEME_KEY, $targetPreset),
            'load_defaults_into_settings'
        );

        if ($result['success']) {
            [$count, $details] = $result['data'];
            $after = $debug ? $this->countThemeSettings($siteSlug) : null;
            
            $messenger->addSuccess(sprintf(
                'Loaded %d %s preset defaults into LibraryTheme settings for site "%s".',
                $count,
                $targetPreset,
                $siteSlug
            ));
            
            if ($debug && $before !== null && $after !== null) {
                $messenger->addSuccess(sprintf(
                    'Debug: theme_settings count before=%d after=%d',
                    $before,
                    $after
                ));
            }
        } else {
            $messenger->addError($result['error']);
        }

        return true;
    }

    /**
     * Handle save settings as defaults action
     */
    private function handleSaveSettingsAsDefaults(?string $siteSlug, string $targetPreset, bool $debug, Messenger $messenger): bool
    {
        if ($siteError = $this->errorHandler->validateSiteSlug($siteSlug)) {
            $messenger->addError($siteError);
            return true;
        }

        $result = $this->errorHandler->wrapOperation(
            fn() => $this->themeSettingsService->saveSettingsAsPresetDefaults($siteSlug, ModuleConfig::DEFAULT_THEME_KEY, $targetPreset),
            'save_settings_as_defaults'
        );

        if ($result['success']) {
            [$count, $current] = $result['data'];
            $messenger->addSuccess(sprintf(
                'Saved current LibraryTheme settings as %s preset defaults (%d fields).',
                $targetPreset,
                $count
            ));
            
            if ($debug) {
                $sample = substr(json_encode($current), 0, 300) . '...';
                $messenger->addSuccess('Debug: stored defaults sample: ' . $sample);
            }
        } else {
            $messenger->addError($result['error']);
        }

        return true;
    }

    /**
     * Inspect a single setting key
     */
    private function inspectSingleKey(?string $siteSlug, string $key)
    {
        $settings = $this->themeSettingsService->inspectThemeSettings($siteSlug);
        return $settings['settings'][$key] ?? null;
    }

    /**
     * Count theme settings for a site
     */
    private function countThemeSettings(?string $siteSlug): int
    {
        $settings = $this->themeSettingsService->inspectThemeSettings($siteSlug);
        return $settings['settings_count'];
    }
}
