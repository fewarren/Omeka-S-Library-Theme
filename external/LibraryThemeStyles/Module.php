<?php declare(strict_types=1);

namespace LibraryThemeStyles;

use Omeka\Module\AbstractModule;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Model\ViewModel;

class Module extends AbstractModule
{
    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }

    // Explicitly mark module configurable to ensure Configure link appears
    public function isConfigurable(): bool
    {
        return true;
    }

    // Expose a Configure button in Modules list and render our admin form
    public function getConfigForm(PhpRenderer $renderer)
    {
        // Render a fragment that Omeka wraps in its own form with CSRF token
        $view = new ViewModel();
        $view->setTemplate('library-theme-styles/admin/configure');
        return $renderer->render($view);
    }

    // Handle form submission from the module's Configure page (Omeka signature)
    public function handleConfigForm(AbstractController $controller)
    {
        $services = $controller->getEvent()->getApplication()->getServiceManager();
        $api = $services->get('Omeka\ApiManager');
        $settings = $services->get('Omeka\Settings');
        $siteSettings = $services->get('Omeka\Settings\Site');
        $messenger = $controller->messenger();

        $data = $controller->params()->fromPost();
        $action = $data['action'] ?? null;
        $targetPreset = $data['target_preset'] ?? 'modern';
        $siteSlug = $data['site'] ?? null;
        $debug = !empty($data['debug']);
        $themeKey = 'LibraryTheme';

        try {
            if ($action === 'inspect_theme_settings') {
                if (!$siteSlug) { $messenger->addError('Provide a Site Slug to inspect current theme settings.'); return true; }
                $summary = $this->inspectThemeSettings($api, $siteSettings, $siteSlug, $themeKey);
                $messenger->addSuccess($summary);
                return true;
            }

            if ($action === 'verify_defaults_vs_settings') {
                if (!$siteSlug) { $messenger->addError('Provide a Site Slug.'); return true; }
                $targetPreset = $data['target_preset'] ?? 'modern';
                $report = $this->verifyDefaultsVsSettings($api, $settings, $siteSettings, $siteSlug, $targetPreset);
                $messenger->addSuccess($report);
                return true;
            }

            if ($action === 'load_stored_defaults') {
                if (!$siteSlug) { $messenger->addError('Provide a Site Slug.'); return true; }
                $targetPreset = $data['target_preset'] ?? 'modern';
                [$count, $msg] = $this->loadStoredDefaultsIntoSettings($api, $settings, $siteSettings, $siteSlug, $targetPreset);
                $messenger->addSuccess(sprintf('Loaded %d stored default keys into settings. %s', $count, $msg));
                return true;
            }

            if ($action === 'inspect_key') {
                if (!$siteSlug) { $messenger->addError('Provide a Site Slug.'); return true; }
                $key = trim((string)($data['inspect_key'] ?? ''));
                if ($key === '') { $messenger->addError('Provide a setting key to inspect.'); return true; }
                $value = $this->inspectSingleKey($api, $siteSettings, $siteSlug, $themeKey, $key);
                $messenger->addSuccess(sprintf('Inspect key %s: %s', $key, json_encode($value)));
                return true;
            }

            if ($action === 'diff_vs_preset') {
                if (!$siteSlug) { $messenger->addError('Provide a Site Slug.'); return true; }
                $target = $this->diffVsPreset($api, $siteSettings, $siteSlug, $themeKey, $targetPreset);
                $messenger->addSuccess('Diff vs preset (first 15): ' . $target);
                return true;
            }

            if ($action === 'load_defaults_into_settings') {
                if (!$siteSlug) { $messenger->addError('Please provide a Site Slug to load defaults into LibraryTheme settings.'); return true; }
                $before = $debug ? $this->countThemeSettings($api, $siteSettings, $siteSlug, $themeKey) : null;
                [$count] = $this->applyPresetToThemeSettings($api, $siteSettings, $siteSlug, $themeKey, $targetPreset);
                $after = $debug ? $this->countThemeSettings($api, $siteSettings, $siteSlug, $themeKey) : null;
                $messenger->addSuccess(sprintf('Loaded %d %s preset defaults into LibraryTheme settings for site "%s".', $count, $targetPreset, $siteSlug));
                if ($debug) { $messenger->addSuccess(sprintf('Debug: theme_settings_%s count before=%d after=%d', $themeKey, $before, $after)); }
            } elseif ($action === 'save_settings_as_defaults') {
                if (!$siteSlug) { $messenger->addError('Please provide a Site Slug to save current settings as preset defaults.'); return true; }
                [$count, $current] = $this->saveSettingsAsPresetDefaults($api, $settings, $siteSettings, $siteSlug, $themeKey, $targetPreset);
                $messenger->addSuccess(sprintf('Saved current LibraryTheme settings as %s preset defaults (%d fields).', $targetPreset, $count));
                if ($debug) { $messenger->addSuccess('Debug: stored defaults sample: ' . substr(json_encode($current), 0, 300) . '...'); }
            } else {
                $messenger->addWarning('No action selected.');
            }
        } catch (\Throwable $e) {
            error_log('[LibraryThemeStyles] ERROR: ' . $e->getMessage());
            $messenger->addError('Error: ' . $e->getMessage());
        }
        return true;
    }

    private function applyPresetToThemeSettings($api, $siteSettings, ?string $siteSlug, string $themeKey, string $preset): array
    {
        $presets = $this->getPresetMap();
        if (!isset($presets[$preset])) {
            throw new \RuntimeException('Unknown preset: ' . $preset);
        }
        $values = $presets[$preset];

        // If site slug provided, scope to that site
        if ($siteSlug) {
            $site = $api->read('sites', ['slug' => $siteSlug])->getContent();
            $siteSettings->setTargetId($site->id());
        } else {
            $site = null;
        }
        $themeSlug = $this->getThemeSlug($site) ?: 'library-theme';

        // Read current theme settings containers
        $container = $siteSettings->get('theme_settings', []);
        if (!is_array($container)) { $container = []; }
        $namespacedKey = 'theme_settings_' . $themeSlug;
        $namespaced = $siteSettings->get($namespacedKey, []);
        if (!is_array($namespaced)) { $namespaced = []; }

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
            $siteSettings->set('theme_settings', $container);
        } else {
            $siteSettings->set('theme_settings', $target);
        }
        $siteSettings->set($namespacedKey, $namespaced);

        return [$count, $values];
    }

    private function saveSettingsAsPresetDefaults($api, $settings, $siteSettings, ?string $siteSlug, string $themeKey, string $preset): array
    {
        if ($siteSlug) {
            $site = $api->read('sites', ['slug' => $siteSlug])->getContent();
            $siteSettings->setTargetId($site->id());
        } else {
            $site = null;
        }
        $themeSlug = $this->getThemeSlug($site) ?: 'library-theme';

        // Prefer namespaced settings; fall back to container (map or flat)
        $namespacedKey = 'theme_settings_' . $themeSlug;
        $current = $siteSettings->get($namespacedKey, []);
        if (!is_array($current) || !$current) {
            $container = $siteSettings->get('theme_settings', []);
            if (is_array($container)) {
                if (isset($container[$themeSlug]) && is_array($container[$themeSlug])) {
                    $current = $container[$themeSlug];
                } elseif (!empty($container)) {
                    $current = $container; // flat array variant
                }
            }
        }
        if (!is_array($current) || !$current) {
            return [0, []];
        }

        // Persist into global settings as JSON (per-preset)
        $defaultsKey = 'LibraryThemeStyles_defaults_' . $preset;
        $settings->set($defaultsKey, json_encode($current));
        return [count($current), $current];
    }

    private function countThemeSettings($api, $siteSettings, string $siteSlug, string $themeKey): int
    {
        $site = $api->read('sites', ['slug' => $siteSlug])->getContent();
        $siteSettings->setTargetId($site->id());
        $themeSlug = $this->getThemeSlug($site) ?: 'library-theme';
        $namespaced = $siteSettings->get('theme_settings_' . $themeSlug, []);
        if (is_array($namespaced)) return count($namespaced);
        $container = $siteSettings->get('theme_settings', []);
        if (is_array($container)) {
            if (isset($container[$themeSlug]) && is_array($container[$themeSlug])) return count($container[$themeSlug]);
            return count($container);
        }
        return 0;
    }

    private function inspectSingleKey($api, $siteSettings, string $siteSlug, string $themeKey, string $key)
    {
        $site = $api->read('sites', ['slug' => $siteSlug])->getContent();
        $siteSettings->setTargetId($site->id());
        $themeSlug = $this->getThemeSlug($site) ?: 'library-theme';
        $namespaced = $siteSettings->get('theme_settings_' . $themeSlug, []);
        if (is_array($namespaced) && array_key_exists($key, $namespaced)) return $namespaced[$key];
        $container = $siteSettings->get('theme_settings', []);
        if (is_array($container)) {
            if (isset($container[$themeSlug]) && is_array($container[$themeSlug]) && array_key_exists($key, $container[$themeSlug])) return $container[$themeSlug][$key];
            if (array_key_exists($key, $container)) return $container[$key];
        }
        return null;
    }

    private function diffVsPreset($api, $siteSettings, string $siteSlug, string $themeKey, string $preset): string
    {
        $site = $api->read('sites', ['slug' => $siteSlug])->getContent();
        $siteSettings->setTargetId($site->id());
        $themeSlug = $this->getThemeSlug($site) ?: 'library-theme';
        $current = $siteSettings->get('theme_settings_' . $themeSlug, []);
        $presetMap = $this->getPresetMap();
        $want = $presetMap[$preset] ?? [];
        $diffs = [];
        foreach ($want as $k => $v) {
            $cv = $current[$k] ?? null;
            if ($cv !== $v) {
                $diffs[] = $k . ':' . json_encode($cv) . ' -> ' . json_encode($v);
            }
        }
        return implode(', ', array_slice($diffs, 0, 15));
    }

    private function inspectThemeSettings($api, $siteSettings, string $siteSlug, string $themeKey): string
    {
        $site = $api->read('sites', ['slug' => $siteSlug])->getContent();
        $siteSettings->setTargetId($site->id());
        $themeSlug = $this->getThemeSlug($site) ?: 'library-theme';
        $namespacedKey = 'theme_settings_' . $themeSlug;
        $namespaced = $siteSettings->get($namespacedKey, []);
        $namespacedCount = is_array($namespaced) ? count($namespaced) : 0;

        $container = $siteSettings->get('theme_settings', []);
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
        return sprintf('Inspect: %s has %d keys; theme_settings (%s) has %d keys. Sample (namespaced): %s', $namespacedKey, $namespacedCount, $containerInfo, $containerCount, $sampleKeys);
    }

    private function getStoredDefaults($settings, string $preset): array
    {
        $raw = $settings->get('LibraryThemeStyles_defaults_' . $preset);
        if (!$raw) return [];
        $arr = json_decode((string)$raw, true);
        return is_array($arr) ? $arr : [];
    }

    private function verifyDefaultsVsSettings($api, $settings, $siteSettings, string $siteSlug, string $preset): string
    {
        $site = $api->read('sites', ['slug' => $siteSlug])->getContent();
        $siteSettings->setTargetId($site->id());
        $themeSlug = $this->getThemeSlug($site) ?: 'library-theme';
        $namespaced = $siteSettings->get('theme_settings_' . $themeSlug, []);
        $namespaced = is_array($namespaced) ? $namespaced : [];
        $defaults = $this->getStoredDefaults($settings, $preset);

        $missingInDefaults = [];
        $missingInSettings = [];
        $diffs = [];
        foreach ($namespaced as $k => $v) {
            if (!array_key_exists($k, $defaults)) $missingInDefaults[] = $k;
        }
        foreach ($defaults as $k => $v) {
            if (!array_key_exists($k, $namespaced)) $missingInSettings[] = $k;
            else if ($namespaced[$k] !== $v) $diffs[] = $k . ':' . json_encode($namespaced[$k]) . ' != ' . json_encode($v);
        }
        return sprintf(
            'Verify: settings=%d, defaults=%d, missingInDefaults=%d, missingInSettings=%d, diffs=%d. Samples: missingInDefaults=[%s]; missingInSettings=[%s]; diffs=[%s]',
            count($namespaced), count($defaults), count($missingInDefaults), count($missingInSettings), count($diffs),
            implode(', ', array_slice($missingInDefaults, 0, 10)),
            implode(', ', array_slice($missingInSettings, 0, 10)),
            implode(', ', array_slice($diffs, 0, 10))
        );
    }

    private function loadStoredDefaultsIntoSettings($api, $settings, $siteSettings, string $siteSlug, string $preset): array
    {
        $site = $api->read('sites', ['slug' => $siteSlug])->getContent();
        $siteSettings->setTargetId($site->id());
        $themeSlug = $this->getThemeSlug($site) ?: 'library-theme';
        $key = 'theme_settings_' . $themeSlug;
        $current = $siteSettings->get($key, []);
        $current = is_array($current) ? $current : [];
        $defaults = $this->getStoredDefaults($settings, $preset);

        $count = 0;
        foreach ($defaults as $k => $v) {
            $current[$k] = $v;
            $count++;
        }
        $siteSettings->set($key, $current);
        return [$count, sprintf('theme=%s key=%s now has %d keys', $themeSlug, $key, count($current))];
    }

    private function getThemeSlug($site = null): ?string
    {
        if ($site && method_exists($site, 'theme') && $site->theme()) {
            return (string) $site->theme();
        }
        try {
            $sm = $this->getServiceLocator();
            $siteSettings = $sm->get('Omeka\Settings\Site');
            $slug = $siteSettings->get('theme');
            return is_string($slug) && $slug !== '' ? $slug : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getPresetMap(): array
    {
        return [
            'modern' => [
                'h1_font_family' => 'cormorant', 'h1_font_size' => '2.5rem', 'h1_font_color' => '#b37c05', 'h1_font_weight' => '600',
                'h2_font_family' => 'cormorant', 'h2_font_size' => '2rem', 'h2_font_color' => '#b37c05', 'h2_font_weight' => '600',
                'h3_font_family' => 'georgia',   'h3_font_size' => '1.5rem', 'h3_font_color' => '#b37c05', 'h3_font_weight' => '500',
                'body_font_family' => 'helvetica','body_font_size' => '1.125rem','body_font_color' => '#b37c05','body_font_weight' => '400',
                'tagline_font_family' => 'georgia','tagline_font_weight' => '600','tagline_font_style' => 'italic','tagline_font_color' => '#b37c05', 'tagline_hover_text_color' => '#ffffff', 'tagline_hover_background_color' => '#f3d491',
                'primary_color' => '#b37c05', 'sacred_gold' => '#D4AF37',
                'toc_font_family' => 'georgia', 'toc_font_size' => 'normal', 'toc_font_weight' => '700',
                'toc_text_color' => '#b37c05', 'toc_hover_text_color' => '#ffffff', 'toc_hover_background_color' => '#f3d491',
                'toc_background_color' => '#ffffff', 'toc_border_color' => '#D4AF37', 'toc_border_width' => '2px', 'toc_border_radius' => '8px',
                // Breadcrumbs: independent settings (initially matching body text values)
                'breadcrumbs_font_family' => 'helvetica', 'breadcrumbs_font_style' => 'normal', 'breadcrumbs_font_weight' => '400',
                'breadcrumbs_font_size' => 'normal', 'breadcrumbs_font_size_rem' => '1.125rem',
                'breadcrumbs_text_color' => '#b37c05', 'breadcrumbs_hover_text_color' => '#ffffff', 'breadcrumbs_hover_background_color' => '#f3d491',
                'breadcrumbs_background_color' => '#ffffff', 'breadcrumbs_border_color' => '#D4AF37', 'breadcrumbs_pill_style' => '1', 'breadcrumbs_include_current' => '1',
                'page_title_pill_style' => '1',
                'pagination_font_color' => '#b37c05', 'pagination_background_color' => '#f3d491',
                'pagination_hover_background_color' => '#1a365d', 'pagination_hover_text_color' => '#ffffff',
                'menu_background_color' => '#ffffff', 'menu_text_color' => '#b37c05', 'menu_font_family' => 'helvetica',
                'footer_background_color' => '#ffffff', 'footer_text_color' => '#000000',
                'header_height' => '100', 'logo_height' => '100',
                'toc_font_size_rem' => ''
            ],
            'traditional' => [
                'h1_font_family' => 'georgia', 'h1_font_size' => '2rem',   'h1_font_color' => '#1F3A5F', 'h1_font_weight' => '600',
                'h2_font_family' => 'georgia', 'h2_font_size' => '1.5rem', 'h2_font_color' => '#1F3A5F', 'h2_font_weight' => '600',
                'h3_font_family' => 'georgia', 'h3_font_size' => '1.25rem','h3_font_color' => '#1F3A5F', 'h3_font_weight' => '500',
                'body_font_family' => 'helvetica','body_font_size' => '1rem', 'body_font_color' => '#2F3542','body_font_weight' => '400',
                'tagline_font_family' => 'georgia', 'tagline_font_weight' => '400', 'tagline_font_style' => 'italic', 'tagline_font_color' => '#5A6470',
                'tagline_hover_text_color' => '#ffffff', 'tagline_hover_background_color' => '#7A1E3A',
                'primary_color' => '#1F3A5F', 'sacred_gold' => '#7A1E3A',
                'toc_font_family' => 'helvetica','toc_font_size' => 'normal','toc_font_weight' => '400','toc_text_color' => '#1F3A5F','toc_hover_text_color' => '#ffffff','toc_hover_background_color' => '#7A1E3A','toc_background_color' => '#ffffff','toc_border_color' => '#7A1E3A','toc_border_width' => '2px','toc_border_radius' => '8px',
                // Breadcrumbs: independent settings (initially matching body text values)
                'breadcrumbs_font_family' => 'helvetica', 'breadcrumbs_font_style' => 'normal', 'breadcrumbs_font_weight' => '400',
                'breadcrumbs_font_size' => 'normal', 'breadcrumbs_font_size_rem' => '1rem',
                'breadcrumbs_text_color' => '#2F3542', 'breadcrumbs_hover_text_color' => '#ffffff', 'breadcrumbs_hover_background_color' => '#7A1E3A',
                'breadcrumbs_background_color' => '#ffffff', 'breadcrumbs_border_color' => '#7A1E3A', 'breadcrumbs_pill_style' => '1', 'breadcrumbs_include_current' => '1',
                'page_title_pill_style' => '1',
                'pagination_font_color' => '#ffffff','pagination_background_color' => '#1F3A5F','pagination_hover_background_color' => '#7A1E3A','pagination_hover_text_color' => '#ffffff',
                'menu_background_color' => '#1F3A5F','menu_text_color' => '#ffffff','menu_font_family' => 'helvetica',
                'footer_background_color' => '#f7f8fa','footer_text_color' => '#111111',
                'header_height' => '100','logo_height' => '100',
            ],
        ];
    }
}

