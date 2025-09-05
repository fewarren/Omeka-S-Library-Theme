<?php declare(strict_types=1);

namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class ThemeFunctions extends AbstractHelper
{
    public function __invoke()
    {
        return $this;
    }

    public function getFontFamily($fontKey)
    {
        // Trace for debugging in Apache logs
        error_log('THEME HELPER: getFontFamily called with key: ' . var_export($fontKey, true));

        if (empty($fontKey)) {
            return 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        }

        $fontMap = [
            // Sans-Serif Fonts
            'helvetica' => 'Helvetica Neue, Arial, sans-serif',
            'roboto' => 'Roboto, Arial, sans-serif',
            'open_sans' => 'Open Sans, Arial, sans-serif',
            'lato' => 'Lato, Arial, sans-serif',
            'montserrat' => 'Montserrat, Arial, sans-serif',
            'source_sans' => 'Source Sans Pro, Arial, sans-serif',
            'nunito' => 'Nunito, Arial, sans-serif',
            'poppins' => 'Poppins, Arial, sans-serif',
            'inter' => 'Inter, Arial, sans-serif',
            'work_sans' => 'Work Sans, Arial, sans-serif',
            'fira_sans' => 'Fira Sans, Arial, sans-serif',
            'verdana' => 'Verdana, Arial, sans-serif',
            'arial' => 'Arial, sans-serif',

            // Serif Fonts
            'merriweather' => 'Merriweather, Georgia, serif',
            'playfair' => 'Playfair Display, Georgia, serif',
            'crimson' => 'Crimson Text, Georgia, serif',
            'libre_baskerville' => 'Libre Baskerville, Georgia, serif',
            'lora' => 'Lora, Georgia, serif',
            'pt_serif' => 'PT Serif, Georgia, serif',
            'source_serif' => 'Source Serif Pro, Georgia, serif',
            'georgia' => 'Georgia, serif',
            'times' => 'Times New Roman, serif',

            // Display Fonts
            'oswald' => 'Oswald, Arial, sans-serif',
            'raleway' => 'Raleway, Arial, sans-serif',
            'bebas_neue' => 'Bebas Neue, Arial, sans-serif',
            'anton' => 'Anton, Arial, sans-serif',
            'dancing_script' => 'Dancing Script, cursive',
            'pacifico' => 'Pacifico, cursive',

            // Monospace Fonts
            'fira_code' => 'Fira Code, Consolas, monospace',
            'source_code' => 'Source Code Pro, Consolas, monospace',
            'courier' => 'Courier New, monospace',

            // System Default
            'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        ];

        return $fontMap[$fontKey] ?? $fontMap['system'];
    }
}
