<?php declare(strict_types=1);

namespace LibraryThemeStyles\Config;

/**
 * Configuration constants for the LibraryThemeStyles module
 * Centralizes hardcoded values to improve maintainability and flexibility
 */
class ModuleConfig
{
    // Theme Configuration
    public const DEFAULT_THEME_KEY = 'LibraryTheme';
    public const FALLBACK_THEME_SLUG = 'library-theme';
    
    // Settings Keys
    public const THEME_SETTINGS_PREFIX = 'theme_settings_';
    public const THEME_SETTINGS_CONTAINER_KEY = 'theme_settings';
    public const DEFAULTS_KEY_PREFIX = 'LibraryThemeStyles_defaults_';
    
    // Default Preset
    public const DEFAULT_PRESET = 'modern';
    
    // Available Presets
    public const AVAILABLE_PRESETS = ['modern', 'traditional'];
    
    // Font Families
    public const FONT_FAMILIES = [
        'cormorant' => 'Cormorant Garamond',
        'helvetica' => 'Helvetica Neue',
        'georgia' => 'Georgia',
        'times' => 'Times New Roman',
        'arial' => 'Arial',
        'verdana' => 'Verdana',
    ];
    
    // Font Weights
    public const FONT_WEIGHTS = [
        '300' => 'Light',
        '400' => 'Normal',
        '500' => 'Medium',
        '600' => 'Semi-Bold',
        '700' => 'Bold',
    ];
    
    // Font Styles
    public const FONT_STYLES = [
        'normal' => 'Normal',
        'italic' => 'Italic',
    ];
    
    // Font Sizes
    public const FONT_SIZES = [
        'normal' => 'Normal',
        'large' => 'Large',
    ];
    
    // Button Sizes
    public const BUTTON_SIZES = [
        'extra_small' => 'Extra Small',
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
    ];
    
    // Header Layouts
    public const HEADER_LAYOUTS = [
        'logo_with_tagline' => 'Logo with Tagline',
        'logo_only' => 'Logo Only',
        'tagline_only' => 'Tagline Only',
    ];
    
    // Footer Banner Heights
    public const FOOTER_BANNER_HEIGHTS = [
        'compact' => 'Compact',
        'standard' => 'Standard',
        'tall' => 'Tall',
    ];
    
    // Default Values
    public const DEFAULTS = [
        'tagline_font_size' => '1.2',
        'logo_height' => '100',
        'header_height' => '100',
        'pagination_font_size' => '1rem',
        'footer_copyright_text' => '© ' . date('Y') . ' The Library. All rights reserved.',
        'footer_powered_by_text' => 'Powered by Omeka S',
    ];
    
    // Color Palette Defaults
    public const COLOR_PALETTE = [
        'primary_color' => '#b37c05',
        'sacred_gold' => '#D4AF37',
        'warm_earth' => '#8B4513',
        'soft_sage' => '#9CAF88',
        'warm_cream' => '#F5F5DC',
        'gentle_lavender' => '#E6E6FA',
        'sunset_orange' => '#FF8C42',
        'deep_burgundy' => '#800020',
        'charcoal' => '#36454F',
        'light_gray' => '#F8F9FA',
        'medium_gray' => '#6C757D',
    ];
    
    // Validation Rules
    public const VALIDATION_RULES = [
        'font_size_pattern' => '/^\d+(\.\d+)?(rem|px|em|%)$/',
        'color_pattern' => '/^#[0-9A-Fa-f]{6}$/',
        'height_pattern' => '/^\d+$/',
        'border_width_pattern' => '/^\d+(px)?$/',
    ];
    
    // Error Messages
    public const ERROR_MESSAGES = [
        'unknown_preset' => 'Unknown preset: %s',
        'missing_site_slug' => 'Site slug is required for this operation',
        'invalid_theme_key' => 'Invalid theme key: %s',
        'settings_not_found' => 'No theme settings found for site: %s',
        'preset_validation_failed' => 'Preset data validation failed',
        'api_error' => 'API error occurred: %s',
    ];
    
    // Success Messages
    public const SUCCESS_MESSAGES = [
        'preset_applied' => 'Loaded %d %s preset defaults into LibraryTheme settings.',
        'settings_saved' => 'Saved current LibraryTheme settings as %s preset defaults (%d fields).',
        'defaults_loaded' => 'Loaded stored defaults back into site settings.',
        'settings_inspected' => 'Current theme settings retrieved successfully.',
    ];
    
    /**
     * Builds the storage key for theme settings for the given theme slug.
     *
     * @param string $themeSlug The theme slug identifier.
     * @return string The composed theme settings key.
     */
    public static function getThemeSettingsKey(string $themeSlug): string
    {
        return self::THEME_SETTINGS_PREFIX . $themeSlug;
    }
    
    /**
     * Builds the storage key used to save defaults for a given preset.
     *
     * @param string $preset The preset identifier (e.g., 'modern').
     * @return string The storage key for the preset defaults.
     */
    public static function getDefaultsKey(string $preset): string
    {
        return self::DEFAULTS_KEY_PREFIX . $preset;
    }
    
    /**
     * Checks whether a preset name is one of the available presets.
     *
     * @param string $preset The preset name to check.
     * @return bool `true` if the preset exists in AVAILABLE_PRESETS, `false` otherwise.
     */
    public static function isValidPreset(string $preset): bool
    {
        return in_array($preset, self::AVAILABLE_PRESETS, true);
    }
    
    /**
     * Retrieve a formatted error message template by key.
     *
     * @param string $key The error message identifier to look up.
     * @param mixed ...$args Values to interpolate into the message template via `sprintf`.
     * @return string The formatted error message; returns `'Unknown error'` if no template is found for the provided key.
     */
    public static function getErrorMessage(string $key, ...$args): string
    {
        $message = self::ERROR_MESSAGES[$key] ?? 'Unknown error';
        return sprintf($message, ...$args);
    }
    
    /**
     * Retrieve and format a success message template identified by key.
     *
     * @param string $key The message key to look up in SUCCESS_MESSAGES.
     * @param mixed ...$args Values to interpolate into the message template.
     * @return string The formatted success message; if the key is missing returns "Operation completed".
     */
    public static function getSuccessMessage(string $key, ...$args): string
    {
        $message = self::SUCCESS_MESSAGES[$key] ?? 'Operation completed';
        return sprintf($message, ...$args);
    }
    
    /**
         * Checks whether a color string matches the module's configured color pattern.
         *
         * @param string $color The color value to validate (must conform to the module's color pattern).
         * @return bool `true` if the color matches the configured pattern, `false` otherwise.
         */
    public static function isValidColor(string $color): bool
    {
        return preg_match(self::VALIDATION_RULES['color_pattern'], $color) === 1;
    }
    
    /**
     * Determine whether a font size string matches the configured font-size pattern.
     *
     * Accepts numeric values with optional units commonly used in CSS (for example "14px", "1.2rem", "100%").
     *
     * @param string $fontSize The font size string to validate.
     * @return bool `true` if `$fontSize` matches the module's font-size pattern, `false` otherwise.
     */
    public static function isValidFontSize(string $fontSize): bool
    {
        return preg_match(self::VALIDATION_RULES['font_size_pattern'], $fontSize) === 1;
    }
}
