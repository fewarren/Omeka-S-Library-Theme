<?php declare(strict_types=1);

namespace LibraryThemeStyles\Service;

/**
 * Manages theme preset definitions and operations
 * Centralizes preset data to eliminate duplication between Module.php and AdminController.php
 */
class PresetManager
{
    /** @var array<string, array<string, string>> */
    private static array $presets = [
        'modern' => [
            // Typography - Headings
            'h1_font_family' => 'cormorant',
            'h1_font_size' => '2.5rem',
            'h1_font_color' => '#b37c05',
            'h1_font_weight' => '600',
            
            'h2_font_family' => 'cormorant',
            'h2_font_size' => '2rem',
            'h2_font_color' => '#b37c05',
            'h2_font_weight' => '600',
            
            'h3_font_family' => 'georgia',
            'h3_font_size' => '1.5rem',
            'h3_font_color' => '#b37c05',
            'h3_font_weight' => '500',
            
            // Typography - Body
            'body_font_family' => 'helvetica',
            'body_font_size' => '1.125rem',
            'body_font_color' => '#b37c05',
            'body_font_weight' => '400',
            
            // Typography - Tagline
            'tagline_font_family' => 'georgia',
            'tagline_font_weight' => '600',
            'tagline_font_style' => 'italic',
            'tagline_font_color' => '#b37c05',
            'tagline_hover_text_color' => '#ffffff',
            'tagline_hover_background_color' => '#f3d491',
            
            // Colors
            'primary_color' => '#b37c05',
            'sacred_gold' => '#D4AF37',
            
            // Table of Contents
            'toc_font_family' => 'georgia',
            'toc_font_size' => 'normal',
            'toc_font_weight' => '700',
            'toc_text_color' => '#b37c05',
            'toc_hover_text_color' => '#ffffff',
            'toc_hover_background_color' => '#f3d491',
            'toc_background_color' => '#ffffff',
            'toc_border_color' => '#D4AF37',
            'toc_border_width' => '2px',
            'toc_border_radius' => '8px',
            'toc_pill_style' => '1',
            'toc_font_size_rem' => '',
            
            // Breadcrumbs
            'breadcrumbs_font_family' => 'helvetica',
            'breadcrumbs_font_style' => 'normal',
            'breadcrumbs_font_weight' => '400',
            'breadcrumbs_font_size_rem' => '1.125rem',
            'breadcrumbs_text_color' => '#b37c05',
            'breadcrumbs_hover_text_color' => '#ffffff',
            'breadcrumbs_hover_background_color' => '#f3d491',
            'breadcrumbs_background_color' => '#ffffff',
            'breadcrumbs_border_color' => '#D4AF37',
            'breadcrumbs_border_width' => '1px',
            'breadcrumbs_pill_style' => '1',
            'breadcrumbs_include_current' => '1',
            
            // Page Title
            'page_title_pill_style' => '1',
            'page_title_border_width' => '1px',
            
            // Pagination
            'pagination_font_color' => '#b37c05',
            'pagination_background_color' => '#f3d491',
            'pagination_border_width' => '1px',
            'pagination_hover_background_color' => '#1a365d',
            'pagination_hover_text_color' => '#ffffff',
            
            // Menu
            'menu_background_color' => '#ffffff',
            'menu_text_color' => '#b37c05',
            'menu_font_family' => 'helvetica',
            
            // Footer
            'footer_background_color' => '#ffffff',
            'footer_text_color' => '#000000',
            
            // Layout
            'header_height' => '100',
            'logo_height' => '100',
        ],
        
        'traditional' => [
            // Typography - Headings
            'h1_font_family' => 'georgia',
            'h1_font_size' => '2rem',
            'h1_font_color' => '#1F3A5F',
            'h1_font_weight' => '600',
            
            'h2_font_family' => 'georgia',
            'h2_font_size' => '1.5rem',
            'h2_font_color' => '#1F3A5F',
            'h2_font_weight' => '600',
            
            'h3_font_family' => 'georgia',
            'h3_font_size' => '1.25rem',
            'h3_font_color' => '#1F3A5F',
            'h3_font_weight' => '500',
            
            // Typography - Body
            'body_font_family' => 'helvetica',
            'body_font_size' => '1rem',
            'body_font_color' => '#2F3542',
            'body_font_weight' => '400',
            
            // Typography - Tagline
            'tagline_font_family' => 'georgia',
            'tagline_font_weight' => '400',
            'tagline_font_style' => 'italic',
            'tagline_font_color' => '#5A6470',
            'tagline_hover_text_color' => '#ffffff',
            'tagline_hover_background_color' => '#7A1E3A',
            
            // Colors
            'primary_color' => '#1F3A5F',
            'sacred_gold' => '#7A1E3A',
            
            // Table of Contents
            'toc_font_family' => 'helvetica',
            'toc_font_size' => 'normal',
            'toc_font_weight' => '400',
            'toc_text_color' => '#1F3A5F',
            'toc_hover_text_color' => '#ffffff',
            'toc_hover_background_color' => '#7A1E3A',
            'toc_background_color' => '#ffffff',
            'toc_border_color' => '#7A1E3A',
            'toc_border_width' => '2px',
            'toc_border_radius' => '8px',
            'toc_pill_style' => '1',
            
            // Breadcrumbs
            'breadcrumbs_font_family' => 'helvetica',
            'breadcrumbs_font_style' => 'normal',
            'breadcrumbs_font_weight' => '400',
            'breadcrumbs_font_size_rem' => '1rem',
            'breadcrumbs_text_color' => '#2F3542',
            'breadcrumbs_hover_text_color' => '#ffffff',
            'breadcrumbs_hover_background_color' => '#7A1E3A',
            'breadcrumbs_background_color' => '#ffffff',
            'breadcrumbs_border_color' => '#7A1E3A',
            'breadcrumbs_border_width' => '1px',
            'breadcrumbs_pill_style' => '1',
            'breadcrumbs_include_current' => '1',
            
            // Page Title
            'page_title_pill_style' => '1',
            'page_title_border_width' => '1px',
            
            // Pagination
            'pagination_font_color' => '#ffffff',
            'pagination_background_color' => '#1F3A5F',
            'pagination_border_width' => '1px',
            'pagination_hover_background_color' => '#7A1E3A',
            'pagination_hover_text_color' => '#ffffff',
            
            // Menu
            'menu_background_color' => '#1F3A5F',
            'menu_text_color' => '#ffffff',
            'menu_font_family' => 'helvetica',
            
            // Footer
            'footer_background_color' => '#f7f8fa',
            'footer_text_color' => '#111111',
            
            // Layout
            'header_height' => '100',
            'logo_height' => '100',
        ],
    ];

    /**
     * Get all available presets
     * 
     * @return array<string, array<string, string>>
     */
    public static function getAllPresets(): array
    {
        return self::$presets;
    }

    /**
     * Retrieve preset data for the given preset name.
     *
     * @param string $presetName Name of the preset to retrieve.
     * @return array<string, string> Associative array mapping preset keys to string values.
     * @throws \InvalidArgumentException If the specified preset does not exist.
     */
    public static function getPreset(string $presetName): array
    {
        if (!isset(self::$presets[$presetName])) {
            throw new \InvalidArgumentException("Unknown preset: {$presetName}");
        }
        
        return self::$presets[$presetName];
    }

    /**
         * Determine whether a preset with the given name is registered.
         *
         * @param string $presetName The preset identifier to check.
         * @return bool `true` if a preset with the given name exists, `false` otherwise.
         */
    public static function hasPreset(string $presetName): bool
    {
        return isset(self::$presets[$presetName]);
    }

    /**
     * List all registered preset names.
     *
     * @return array<string> The list of preset names.
     */
    public static function getPresetNames(): array
    {
        return array_keys(self::$presets);
    }

    /**
     * Check that every key and value in a preset data array is a string.
     *
     * @param array<string, string> $presetData Associative array representing a preset where keys and values must be strings.
     * @return bool `true` if all keys and values are strings, `false` otherwise.
     */
    public static function validatePresetData(array $presetData): bool
    {
        // Basic validation - ensure all values are strings
        foreach ($presetData as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                return false;
            }
        }
        
        return true;
    }
}
