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

    /**
     * Instantiate the ModuleConfigService with its required dependencies.
     */
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
     * Process a module configuration form submission and dispatch the selected action.
     *
     * The $data array may contain the following keys: `action` (string|null) to select the operation,
     * `target_preset` (string) preset name to use (defaults to ModuleConfig::DEFAULT_PRESET),
     * `site` (string|null) site slug, and `debug` (truthy) to enable debugging output.
     * Any errors encountered during processing are converted by the ErrorHandler and added to the provided Messenger.
     *
     * @param array $data Form input values and flags (see description for expected keys).
     * @param Messenger $messenger Messenger used to report success, warning, and error messages to the user.
     * @return bool `true` if the submission was handled (errors are reported via Messenger), `false` otherwise.
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
         * Dispatches a module configuration action to its dedicated handler and reports the outcome via the Messenger.
         *
         * @param string|null $action The action identifier selected from the configuration form (may be null).
         * @param string $targetPreset The target preset name to use for preset-related actions.
         * @param string|null $siteSlug The site slug to operate on (may be null).
         * @param bool $debug When true, handlers may include additional debug information or comparisons.
         * @param array $data Raw form data; used by actions that require extra inputs (for example, inspect_key).
         * @param Messenger $messenger Messenger instance used to emit success, warning, or error messages.
         * @return bool `true` if processing completed (handlers ran or a warning was emitted), `false` if a handler indicates failure.
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
     * Inspect theme settings for the given site and add a success or error message to the messenger.
     *
     * @param string|null $siteSlug Site slug to inspect; `null` selects the default site.
     * @return bool `true` to indicate the handler finished processing.
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
     * Compare a preset's default values against a site's current theme settings and report the results to the provided Messenger.
     *
     * Validates the site slug before performing the comparison and sends a summary or error message to the messenger.
     *
     * @param string|null $siteSlug The site slug to target; may be null and will be validated.
     * @param string $targetPreset The preset identifier to compare against the site's theme settings.
     * @return bool `true` if processing completed. 
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
         * Load stored preset defaults into the theme settings for the given site and report the outcome via the messenger.
         *
         * Validates the provided site slug, invokes the theme settings service to load the specified preset's stored defaults,
         * and adds a success message with the number of keys loaded or an error message if the operation fails.
         *
         * @param string|null $siteSlug The site slug to target; may be null if not specified.
         * @param string $targetPreset The preset identifier whose stored defaults should be loaded.
         * @param Messenger $messenger Messenger used to report success or error messages to the user.
         * @return bool `true` to indicate that the form action processing has completed.
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
         * Inspect a single theme setting key for a given site and report the value via Messenger.
         *
         * Validates the provided site slug and reads the 'inspect_key' entry from `$data`.
         * On success, adds a success message containing the key and its JSON-encoded value.
         * On failure, adds appropriate error messages to the messenger.
         *
         * @param ?string $siteSlug The site slug to inspect, or null to use the default/current site.
         * @param array $data Input data array; must contain an 'inspect_key' entry with the key to inspect.
         * @return bool `true` if processing completed and a messenger message was added.
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
     * Build and send a concise diff summary between the site's current theme settings and the specified preset.
     *
     * @param string|null $siteSlug The site slug to operate on; may be null which will be validated.
     * @param string $targetPreset The name of the preset to compare against.
     * @return bool `true` if processing completed and a message was sent (method returns after reporting results).
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
     * Load a preset's defaults into the site's LibraryTheme settings and report results.
     *
     * Validates the site slug, applies the specified preset to the site's theme settings, and
     * adds success or error messages to the provided Messenger. When `$debug` is true, captures
     * and reports the theme settings count before and after the operation.
     *
     * @param string|null $siteSlug Site identifier (slug) to operate on.
     * @param string $targetPreset Name of the preset to apply.
     * @param bool $debug When true, capture and report pre/post operation theme settings counts.
     * @param \Drupal\Core\Messenger\MessengerInterface $messenger Messenger used to deliver user-facing messages.
     * @return bool `true` when processing is complete (messages are delivered to the messenger). 
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
     * Save current LibraryTheme settings as the specified preset defaults for a site.
     *
     * Validates the site slug, attempts to store the current theme settings under the given preset name,
     * and reports success or error messages via the provided messenger. When $debug is true, a truncated JSON
     * sample of the stored defaults is added to the messenger.
     *
     * @param string|null $siteSlug Site slug to operate on; validation errors are reported through the messenger.
     * @param string $targetPreset Name of the preset to save settings into.
     * @param bool $debug If true, include a truncated sample of the stored defaults in the messenger.
     * @param Messenger $messenger Messenger used to report status, success, and error messages to the user.
     * @return bool `true` if the action was handled.
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
     * Retrieve the value for a single theme setting key for a given site.
     *
     * @param string|null $siteSlug The site slug to inspect, or null to use the default site context.
     * @param string $key The setting key to retrieve.
     * @return mixed|null The setting value if present, `null` if the key is not found.
     */
    private function inspectSingleKey(?string $siteSlug, string $key)
    {
        $settings = $this->themeSettingsService->inspectThemeSettings($siteSlug);
        return $settings['settings'][$key] ?? null;
    }

    /**
     * Get the number of theme settings for the given site.
     *
     * @param string|null $siteSlug The site slug to inspect, or null to use the default site.
     * @return int The count of theme settings for the site.
     */
    private function countThemeSettings(?string $siteSlug): int
    {
        $settings = $this->themeSettingsService->inspectThemeSettings($siteSlug);
        return $settings['settings_count'];
    }
}
