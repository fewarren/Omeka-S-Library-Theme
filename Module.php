<?php
/**
 * Library Theme Module
 *
 * Complete implementation with theme settings support
 */

namespace OmekaTheme\LibraryTheme;

use Omeka\Module\AbstractModule;
use Laminas\View\Model\ViewModel;
use Laminas\Form\Form;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\EventManager\Event;
use Laminas\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function getConfig()
    {
        // Load theme configuration (view manager, defaults, etc.)
        $configPath = __DIR__ . '/config/module.config.php';
        if (file_exists($configPath)) {
            return include $configPath;
        }
        return [];
    }

    /**
     * Register view helpers used by the theme (TabManager, ThemeFunctions alias if present).
     */
    public function getViewHelperConfig()
    {
        $helpers = ['invokables' => []];
        if (class_exists('\OmekaTheme\Helper\TabManager')) {
            $helpers['invokables']['tabManager'] = \OmekaTheme\Helper\TabManager::class;
            $helpers['invokables']['TabManager'] = \OmekaTheme\Helper\TabManager::class;
        }
        // If a ThemeFunctions helper exists in Theme\View\Helper, register aliases
        if (class_exists('Theme\\View\\Helper\\ThemeFunctions')) {
            $helpers['invokables']['themeFunctions'] = \Theme\View\Helper\ThemeFunctions::class;
            $helpers['invokables']['ThemeFunctions'] = \Theme\View\Helper\ThemeFunctions::class;
        } elseif (class_exists('OmekaTheme\\Helper\\ThemeFunctions')) {
            $helpers['invokables']['themeFunctions'] = \OmekaTheme\Helper\ThemeFunctions::class;
            $helpers['invokables']['ThemeFunctions'] = \OmekaTheme\Helper\ThemeFunctions::class;
        }
        return $helpers;
    }

    /**
     * Get the configuration form for this theme
     */
    public function getConfigForm(ViewModel $view)
    {
        // The form is automatically generated from theme.ini
        // We just need to return the view model
        return $view;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // Add admin assets (buttons to apply presets in admin form)
        $sharedEventManager->attach('*', 'view.layout', [$this, 'addAdminAssets']);
    }

    public function addAdminAssets(Event $event)
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (!$requestUri || strpos($requestUri, '/admin') === false) {
            return; // only admin pages
        }
        /** @var PhpRenderer $view */
        $view = $event->getTarget();
        $basePath = $view->plugin('basePath');
        $cb = '_cb=' . time();
        // Load our admin helper JS and CSS from this theme explicitly
        $adminJs = [
            '/themes/LibraryTheme/asset/js/admin-preset-reset.js',
        ];
        $adminCss = [
            '/themes/LibraryTheme/asset/css/color-picker.css',
        ];
        foreach ($adminCss as $p) {
            $u = $basePath($p);
            $sep = (strpos($u, '?') !== false) ? '&' : '?';
            $view->headLink()->appendStylesheet($u . $sep . $cb);
        }
        foreach ($adminJs as $p) {
            $u = $basePath($p);
            $sep = (strpos($u, '?') !== false) ? '&' : '?';
            $view->headScript()->appendFile($u . $sep . $cb);
        }
    }

    /**
     * Handle the configuration form submission
     */
    public function handleConfigForm(Form $form, array $params, ViewModel $view)
    {
        // Log form submission for debugging
        $logFile = '/tmp/theme-form-submission-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = function($message) use ($logFile, $timestamp) {
            file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
        };

        $logMessage("=== THEME FORM SUBMISSION ===");
        $logMessage("Form data received: " . json_encode($params));

        // CSRF diagnostics: dump server request method and token presence
        $token = $params['csrf'] ?? $params['_csrf'] ?? null;
        $logMessage("CSRF param present: " . (isset($token) ? 'YES' : 'NO'));
        if (isset($token)) {
            $logMessage('CSRF length: ' . strlen((string)$token));
            $logMessage('CSRF first 8: ' . substr((string)$token, 0, 8));
        }
        $logMessage('REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
        $logMessage('HTTP_ORIGIN: ' . ($_SERVER['HTTP_ORIGIN'] ?? ''));
        $logMessage('HTTP_REFERER: ' . ($_SERVER['HTTP_REFERER'] ?? ''));
        $logMessage('HTTP_USER_AGENT: ' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        // Apply preset to settings when requested
        if (!empty($params['apply_preset_to_settings'])) {
            $logMessage("Apply Preset to Settings: applying preset values to form data");
            $this->applyPresetToForm($form, $params, $logMessage);
            $form->setData(array_merge($form->getData() ?? [], $params));
        }

        $logMessage("Form processing complete");

        // Return true to indicate successful processing
        return true;
    }

    /**
     * Apply preset values to form data
     */
    private function applyPresetToForm(Form $form, array &$params, $logMessage)
    {
        $stylePreset = $params['style_preset'] ?? 'traditional';
        $logMessage("Applying preset: $stylePreset");

        // Define preset values
        $presets = [
            'traditional' => [
                'h1_font_family' => 'georgia',
                'h1_font_color' => '#8B4513',
                'h1_font_size' => '2.5rem',
                'h1_font_weight' => '700',
                'h2_font_family' => 'georgia',
                'h2_font_color' => '#8B4513',
                'h2_font_size' => '2rem',
                'h2_font_weight' => '700',
                'h3_font_family' => 'georgia',
                'h3_font_color' => '#8B4513',
                'h3_font_size' => '1.5rem',
                'h3_font_weight' => '600',
                'body_font_family' => 'georgia',
                'body_font_color' => '#333333',
                'body_font_size' => '1rem',
                'body_font_weight' => '400',
                'tagline_font_family' => 'georgia',
                'tagline_font_color' => '#8B4513',
                'tagline_font_weight' => '400',
                'primary_color' => '#8B4513',
                'accent_color' => '#D2691E',
                'page_title_font_family' => 'georgia',
                'page_title_font_color' => '#8B4513',
                'page_title_font_size' => '2rem',
                'page_title_font_weight' => '700',
                'toc_background_color' => '#F5F5DC',
                'toc_border_color' => '#8B4513',
                'toc_text_color' => '#333333',
                'pagination_background_color' => '#8B4513',
                'pagination_font_color' => '#FFFFFF'
            ],
            'modern' => [
                // Static snapshot of current defaults (can be updated over time)
                'h1_font_family' => 'cormorant', 'h1_font_size' => '2.5rem', 'h1_font_color' => '#111111', 'h1_font_weight' => '600',
                'h2_font_family' => 'cormorant', 'h2_font_size' => '2rem', 'h2_font_color' => '#2c4a6b', 'h2_font_weight' => '600',
                'h3_font_family' => 'georgia', 'h3_font_size' => '1.5rem', 'h3_font_color' => '#2c4a6b', 'h3_font_weight' => '500',
                'body_font_family' => 'helvetica', 'body_font_size' => '1.125rem', 'body_font_color' => '#111111', 'body_font_weight' => '400',
                'tagline_font_family' => 'georgia', 'tagline_font_weight' => '400', 'tagline_font_style' => 'normal', 'tagline_font_color' => '#f7c97f',
                'primary_color' => '#2C4A6B', 'sacred_gold' => '#D4AF37',
                'toc_font_family' => 'helvetica', 'toc_font_size' => 'normal', 'toc_font_weight' => '700', 'toc_text_color' => '#2c4a6b', 'toc_hover_text_color' => '#ffffff', 'toc_hover_background_color' => '#d4af37', 'toc_background_color' => '#ffffff', 'toc_border_color' => '#D4AF37', 'toc_border_width' => '2px', 'toc_border_radius' => '8px',
                'pagination_font_color' => '#ffffff', 'pagination_background_color' => '#2c5aa0', 'pagination_hover_color' => '#1a365d',
                'menu_background_color' => '#2C4A6B', 'menu_text_color' => '#ffffff', 'menu_font_family' => 'helvetica',
                'footer_background_color' => '#ffffff', 'footer_text_color' => '#000000',
                'header_height' => '100', 'logo_height' => '100',
                'toc_font_size_rem' => ''
            ]
        ];

        if (isset($presets[$stylePreset])) {
            $presetValues = $presets[$stylePreset];
            foreach ($presetValues as $key => $value) {
                $params[$key] = $value;
                // Update form field when present so the admin UI shows the applied value and Omeka persists it
                if ($form->has($key)) {
                    $form->get($key)->setValue($value);
                }
                $logMessage("Applied preset value: $key = $value");
            }
        }
    }
}
