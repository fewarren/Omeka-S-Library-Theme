<?php
return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],

    'themes' => [
        'LibraryTheme' => [
            'label' => 'Library Theme',
            'settings' => [
                // Logo and Branding Settings
                'site_logo' => [
                    'type' => 'asset',
                    'label' => 'Site Logo',
                    'default' => ''
                ],
                'logo_link_page' => [
                    'type' => 'text',
                    'label' => 'Logo Link Page',
                    'default' => '/'
                ],
                'site_tagline' => [
                    'type' => 'text',
                    'label' => 'Site Tagline',
                    'default' => 'Menu'
                ],
                'tagline_font_family' => [
                    'type' => 'select',
                    'label' => 'Tagline Font Family',
                    'options' => [
                        'georgia' => 'Georgia',
                        'helvetica' => 'Helvetica Neue',
                        'times' => 'Times New Roman',
                        'arial' => 'Arial',
                    ],
                    'default' => 'georgia'
                ],
                'tagline_font_size' => [
                    'type' => 'text',
                    'label' => 'Tagline Font Size (rem)',
                    'default' => '1.2'
                ],
                'tagline_font_color' => [
                    'type' => 'color',
                    'label' => 'Tagline Font Color',
                    'default' => '#b37c05'
                ],
                'tagline_font_weight' => [
                    'type' => 'select',
                    'label' => 'Tagline Font Weight',
                    'options' => [ '400' => 'Normal', '500' => 'Medium', '600' => 'Semi-Bold', '700' => 'Bold' ],
                    'default' => '600'
                ],
                'tagline_font_style' => [
                    'type' => 'select',
                    'label' => 'Tagline Font Style',
                    'options' => [ 'normal' => 'Normal', 'italic' => 'Italic' ],
                    'default' => 'italic'
                ],
                'logo_height' => [
                    'type' => 'text',
                    'label' => 'Logo Height',
                    'default' => '100'
                ],
                'header_height' => [
                    'type' => 'text',
                    'label' => 'Header Height',
                    'default' => '100'
                ],
                'header_layout' => [
                    'type' => 'select',
                    'label' => 'Header Layout',
                    'options' => [
                        'logo_with_tagline' => 'Logo with Tagline',
                        'logo_only' => 'Logo Only',
                        'tagline_only' => 'Tagline Only',
                    ],
                    'default' => 'logo_with_tagline'
                ],

                // H1 Typography Settings
                'h1_font_family' => [
                    'type' => 'select',
                    'label' => 'H1 Font Family',
                    'options' => [
                        'cormorant' => 'Cormorant Garamond',
                        'helvetica' => 'Helvetica Neue',
                        'georgia' => 'Georgia',
                        'times' => 'Times New Roman',
                        'arial' => 'Arial',
                    ],
                    'default' => 'cormorant'
                ],
                'h1_font_size' => [
                    'type' => 'text',
                    'label' => 'H1 Font Size',
                    'default' => '2.5rem'
                ],
                'h1_font_color' => [
                    'type' => 'color',
                    'label' => 'H1 Font Color',
                    'default' => '#b37c05'
                ],
                'h1_font_weight' => [
                    'type' => 'select',
                    'label' => 'H1 Font Weight',
                    'options' => [
                        '300' => 'Light',
                        '400' => 'Normal',
                        '500' => 'Medium',
                        '600' => 'Semi-Bold',
                        '700' => 'Bold',
                    ],
                    'default' => '600'
                ],
                'h1_font_style' => [
                    'type' => 'select',
                    'label' => 'H1 Font Style',
                    'options' => [
                        'normal' => 'Normal',
                        'italic' => 'Italic',
                    ],
                    'default' => 'normal'
                ],

                // H2 Typography Settings
                'h2_font_family' => [
                    'type' => 'select',
                    'label' => 'H2 Font Family',
                    'options' => [
                        'helvetica' => 'Helvetica Neue',
                        'georgia' => 'Georgia',
                        'times' => 'Times New Roman',
                        'arial' => 'Arial',
                        'cormorant' => 'Cormorant Garamond',
                    ],
                    'default' => 'cormorant'
                ],
                'h2_font_size' => [
                    'type' => 'text',
                    'label' => 'H2 Font Size',
                    'default' => '2rem'
                ],
                'h2_font_color' => [
                    'type' => 'color',
                    'label' => 'H2 Font Color',
                    'default' => '#b37c05'
                ],
                'h2_font_weight' => [
                    'type' => 'select',
                    'label' => 'H2 Font Weight',
                    'options' => [
                        '300' => 'Light',
                        '400' => 'Normal',
                        '500' => 'Medium',
                        '600' => 'Semi-Bold',
                        '700' => 'Bold',
                    ],
                    'default' => '600'
                ],
                'h2_font_style' => [
                    'type' => 'select',
                    'label' => 'H2 Font Style',
                    'options' => [ 'normal' => 'Normal', 'italic' => 'Italic' ],
                    'default' => 'normal'
                ],

                // H3 Typography Settings
                'h3_font_family' => [
                    'type' => 'select',
                    'label' => 'H3 Font Family',
                    'options' => [
                        'helvetica' => 'Helvetica Neue',
                        'georgia' => 'Georgia',
                        'times' => 'Times New Roman',
                        'arial' => 'Arial',
                        'cormorant' => 'Cormorant Garamond',
                    ],
                    'default' => 'georgia'
                ],
                'h3_font_size' => [
                    'type' => 'text',
                    'label' => 'H3 Font Size',
                    'default' => '1.5rem'
                ],
                'h3_font_color' => [
                    'type' => 'color',
                    'label' => 'H3 Font Color',
                    'default' => '#b37c05'
                ],
                'h3_font_weight' => [
                    'type' => 'select',
                    'label' => 'H3 Font Weight',
                    'options' => [
                        '300' => 'Light',
                        '400' => 'Normal',
                        '500' => 'Medium',
                        '600' => 'Semi-Bold',
                        '700' => 'Bold',
                    ],
                    'default' => '500'
                ],
                'h3_font_style' => [
                    'type' => 'select',
                    'label' => 'H3 Font Style',
                    'options' => [ 'normal' => 'Normal', 'italic' => 'Italic' ],
                    'default' => 'normal'
                ],

                // Body Typography Settings
                'body_font_family' => [
                    'type' => 'select',
                    'label' => 'Body Font Family',
                    'options' => [
                        'helvetica' => 'Helvetica Neue',
                        'georgia' => 'Georgia',
                        'times' => 'Times New Roman',
                        'arial' => 'Arial',
                        'cormorant' => 'Cormorant Garamond',
                    ],
                    'default' => 'helvetica'
                ],
                'body_font_size' => [
                    'type' => 'text',
                    'label' => 'Body Font Size',
                    'default' => '1.125rem'
                ],
                'body_font_color' => [
                    'type' => 'color',
                    'label' => 'Body Font Color',
                    'default' => '#b37c05'
                ],
                'body_font_weight' => [
                    'type' => 'select',
                    'label' => 'Body Font Weight',
                    'options' => [
                        '300' => 'Light',
                        '400' => 'Normal',
                        '500' => 'Medium',
                        '600' => 'Semi-Bold',
                        '700' => 'Bold',
                    ],
                    'default' => '400'
                ],
                'body_font_style' => [
                    'type' => 'select',
                    'label' => 'Body Font Style',
                    'options' => [ 'normal' => 'Normal', 'italic' => 'Italic' ],
                    'default' => 'normal'
                ],

                // Menu Typography
                'menu_font_family' => [ 'type' => 'select', 'label' => 'Menu Font Family', 'options' => [ 'helvetica' => 'Helvetica Neue', 'georgia' => 'Georgia', 'arial' => 'Arial', 'verdana' => 'Verdana' ], 'default' => 'helvetica' ],
                'menu_font_style' => [ 'type' => 'select', 'label' => 'Menu Font Style', 'options' => [ 'normal' => 'Normal', 'italic' => 'Italic' ], 'default' => 'normal' ],
                'menu_font_weight' => [ 'type' => 'select', 'label' => 'Menu Font Weight', 'options' => [ '400' => 'Normal', '500' => 'Medium', '600' => 'Semi-Bold', '700' => 'Bold' ], 'default' => '400' ],
                'menu_text_color' => [ 'type' => 'color', 'label' => 'Menu Text Color', 'default' => '#ffffff' ],
                'menu_background_color' => [ 'type' => 'color', 'label' => 'Menu Background Color', 'default' => '#2c4a6b' ],

                // Footer Typography
                'footer_font_family' => [ 'type' => 'select', 'label' => 'Footer Font Family', 'options' => [ 'helvetica' => 'Helvetica Neue', 'georgia' => 'Georgia', 'arial' => 'Arial', 'verdana' => 'Verdana' ], 'default' => 'helvetica' ],
                'footer_font_style' => [ 'type' => 'select', 'label' => 'Footer Font Style', 'options' => [ 'normal' => 'Normal', 'italic' => 'Italic' ], 'default' => 'normal' ],
                'footer_font_weight' => [ 'type' => 'select', 'label' => 'Footer Font Weight', 'options' => [ '400' => 'Normal', '500' => 'Medium', '600' => 'Semi-Bold', '700' => 'Bold' ], 'default' => '400' ],
                'footer_text_color' => [ 'type' => 'color', 'label' => 'Footer Text Color', 'default' => '#000000' ],
                'footer_background_color' => [ 'type' => 'color', 'label' => 'Footer Background Color', 'default' => '#ffffff' ],

                // Pagination Typography
                'pagination_font_family' => [ 'type' => 'select', 'label' => 'Pagination Font Family', 'options' => [ 'helvetica' => 'Helvetica Neue', 'georgia' => 'Georgia', 'arial' => 'Arial', 'verdana' => 'Verdana' ], 'default' => 'helvetica' ],
                'pagination_font_style' => [ 'type' => 'select', 'label' => 'Pagination Font Style', 'options' => [ 'normal' => 'Normal', 'italic' => 'Italic' ], 'default' => 'normal' ],
                'pagination_font_weight' => [ 'type' => 'select', 'label' => 'Pagination Font Weight', 'options' => [ '400' => 'Normal', '500' => 'Medium', '600' => 'Semi-Bold', '700' => 'Bold' ], 'default' => '400' ],


                // Pagination Settings
                'pagination_font_color' => [
                    'type' => 'color',
                    'label' => 'Pagination Font Color',
                    'default' => '#ffffff'
                ],
                'pagination_background_color' => [
                    'type' => 'color',
                    'label' => 'Pagination Background Color',
                    'default' => '#debe7a'
                ],
                'pagination_hover_background_color' => [
                    'type' => 'color',
                    'label' => 'Pagination Hover Background Color',
                    'default' => '#e3ad6f'
                ],
                'pagination_hover_text_color' => [
                    'type' => 'color',
                    'label' => 'Pagination Hover Text Color',
                    'default' => '#ffffff'
                ],
                'pagination_font_size' => [
                    'type' => 'text',
                    'label' => 'Pagination Font Size',
                    'default' => '1rem'
                ],
                'pagination_button_size' => [
                    'type' => 'select',
                    'label' => 'Pagination Button Size',
                    'options' => [
                        'extra_small' => 'Extra Small',
                        'small' => 'Small',
                        'medium' => 'Medium',
                        'large' => 'Large',
                    ],
                    'default' => 'extra_small'
                ],

                // Footer Content Settings
                'footer_copyright_text' => [
                    'type' => 'text',
                    'label' => 'Footer Copyright Text',
                    'default' => '© ' . date('Y') . ' The Library. All rights reserved.'
                ],
                'footer_powered_by_text' => [
                    'type' => 'text',
                    'label' => 'Footer Powered By Text',
                    'default' => 'Powered by Omeka S'
                ],
                'footer_banner_height' => [
                    'type' => 'select',
                    'label' => 'Footer Banner Height',
                    'options' => [
                        'compact' => 'Compact',
                        'standard' => 'Standard',
                        'tall' => 'Tall',
                    ],
                    'default' => 'standard'
                ],

                // Color Palette Settings
                'primary_color' => [
                    'type' => 'color',
                    'label' => 'Primary Color',
                    'default' => '#b37c05'
                ],
                'sacred_gold' => [
                    'type' => 'color',
                    'label' => 'Sacred Gold',
                    'default' => '#D4AF37'
                ],
                'warm_earth' => [
                    'type' => 'color',
                    'label' => 'Warm Earth',
                    'default' => '#8B4513'
                ],
                'soft_sage' => [
                    'type' => 'color',
                    'label' => 'Soft Sage',
                    'default' => '#9CAF88'
                ],
                'warm_cream' => [
                    'type' => 'color',
                    'label' => 'Warm Cream',
                    'default' => '#F5F5DC'
                ],
                'gentle_lavender' => [
                    'type' => 'color',
                    'label' => 'Gentle Lavender',
                    'default' => '#E6E6FA'
                ],
                'sunset_orange' => [
                    'type' => 'color',
                    'label' => 'Sunset Orange',
                    'default' => '#FF8C42'
                ],
                'deep_burgundy' => [
                    'type' => 'color',
                    'label' => 'Deep Burgundy',
                    'default' => '#800020'
                ],
                'charcoal' => [
                    'type' => 'color',
                    'label' => 'Charcoal',
                    'default' => '#36454F'
                ],
                'light_gray' => [
                    'type' => 'color',
                    'label' => 'Light Gray',
                    'default' => '#F8F9FA'
                ],
                'medium_gray' => [
                    'type' => 'color',
                    'label' => 'Medium Gray',
                    'default' => '#6C757D'
                ],

                // Table of Contents Settings
                'toc_font_family' => [
                    'type' => 'select',
                    'label' => 'TOC Font Family',
                    'options' => [
                        'helvetica' => 'Helvetica Neue',
                        'georgia' => 'Georgia',
                        'arial' => 'Arial',
                        'verdana' => 'Verdana',
                        'times' => 'Times New Roman',
                    ],
                    'default' => 'georgia'
                ],
                'toc_font_size' => [
                    'type' => 'select',
                    'label' => 'TOC Font Size',
                    'options' => [
                        'normal' => 'Normal',
                        'large' => 'Large',
                    ],
                    'default' => 'normal'
                ],
                'toc_font_style' => [
                    'type' => 'select',
                    'label' => 'TOC Font Style',
                    'options' => [ 'normal' => 'Normal', 'italic' => 'Italic' ],
                    'default' => 'normal'
                ],
                'toc_text_color' => [
                    'type' => 'color',
                    'label' => 'TOC Text Color',
                    'default' => '#b37c05'
                ],
                'toc_hover_text_color' => [
                    'type' => 'color',
                    'label' => 'TOC Hover Text Color',
                    'default' => '#ffffff'
                ],
                'toc_hover_background_color' => [
                    'type' => 'color',
                    'label' => 'TOC Hover Background Color',
                    'default' => '#d4af37'
                ],
            ],
        ],
    ],
];