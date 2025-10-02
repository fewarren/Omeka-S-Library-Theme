<?php declare(strict_types=1);

namespace LibraryThemeStyles\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Api\Manager as ApiManager;

class AdminController extends AbstractActionController
{
    /** @var ApiManager */
    private $api;

    public function __construct(ApiManager $api)
    {
        $this->api = $api;
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        $siteSlug = $this->params()->fromQuery('site', null);

        $message = null;
        $error = null;

        // Discover the LibraryTheme key for this installation
        $themeKey = 'LibraryTheme';

        try {
            if ($request->isPost()) {
                $action = $this->params()->fromPost('action');
                $targetPreset = $this->params()->fromPost('target_preset', 'modern');

                if ($action === 'load_defaults_into_settings') {
                    // Copy preset defaults into current site theme settings
                    [$count, $details] = $this->applyPresetToThemeSettings($siteSlug, $themeKey, $targetPreset);
                    $message = sprintf('Loaded %d %s preset defaults into LibraryTheme settings.', $count, $targetPreset);
                } elseif ($action === 'save_settings_as_defaults') {
                    // Copy current settings into preset defaults (write to config storage)
                    [$count, $details] = $this->saveSettingsAsPresetDefaults($siteSlug, $themeKey, $targetPreset);
                    $message = sprintf('Saved current LibraryTheme settings as %s preset defaults (%d fields).', $targetPreset, $count);
                } else {
                    $error = 'Unknown action.';
                }
            }
        } catch (\Throwable $e) {
            $error = 'Error: ' . $e->getMessage();
        }

        return new ViewModel([
            'message' => $message,
            'error' => $error,
            'siteSlug' => $siteSlug,
        ]);
    }

    private function applyPresetToThemeSettings(?string $siteSlug, string $themeKey, string $preset): array
    {
        $presets = $this->getPresetMap();
        if (!isset($presets[$preset])) {
            throw new \RuntimeException('Unknown preset: ' . $preset);
        }
        $values = $presets[$preset];

        // Resolve site and settings scope
        $site = $siteSlug
            ? $this->api->read('sites', ['slug' => $siteSlug])->getContent()
            : null;

        $siteSettings = $this->settings();
        if ($site) {
            $siteSettings = $this->siteSettings();
            $siteSettings->setSiteId($site->id());
        }

        // Determine active theme slug and namespaced settings bucket
        $themeSlug = $site && method_exists($site, 'theme') && $site->theme()
            ? (string) $site->theme()
            : 'library-theme';
        $key = 'theme_settings_' . $themeSlug;

        $current = $siteSettings->get($key, []);
        $current = is_array($current) ? $current : [];

        $count = 0;
        foreach ($values as $k => $v) {
            $current[$k] = $v;
            $count++;
        }
        $siteSettings->set($key, $current);

        return [$count, $current];
    }

    private function saveSettingsAsPresetDefaults(?string $siteSlug, string $themeKey, string $preset): array
    {
        $site = $siteSlug
            ? $this->api->read('sites', ['slug' => $siteSlug])->getContent()
            : null;

        $siteSettings = $this->settings();
        if ($site) {
            $siteSettings = $this->siteSettings();
            $siteSettings->setSiteId($site->id());
        }

        $themeSlug = $site && method_exists($site, 'theme') && $site->theme()
            ? (string) $site->theme()
            : 'library-theme';

        // Prefer namespaced theme settings; fall back to container variants
        $namespacedKey = 'theme_settings_' . $themeSlug;
        $stored = $siteSettings->get($namespacedKey, []);
        if (!is_array($stored) || !$stored) {
            $container = $siteSettings->get('theme_settings', []);
            if (is_array($container)) {
                if (isset($container[$themeSlug]) && is_array($container[$themeSlug])) {
                    $stored = $container[$themeSlug];
                } elseif (!empty($container)) {
                    $stored = $container; // flat array variant
                }
            }
        }
        if (!is_array($stored) || !$stored) {
            return [0, []];
        }

        // Persist into global settings as JSON (per-preset)
        $defaultsKey = 'LibraryThemeStyles_defaults_' . $preset;
        $this->settings()->set($defaultsKey, json_encode($stored));
        return [count($stored), $stored];
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

