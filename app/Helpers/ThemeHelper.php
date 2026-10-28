<?php

namespace App\Helpers;

/**
 * Theme Helper
 * 
 * Provides convenient methods to access theme configuration values
 * in Blade templates and PHP code.
 * 
 * Usage examples:
 * - ThemeHelper::color('lime')
 * - ThemeHelper::neutral(500)
 * - ThemeHelper::semantic('success', 'base')
 * - ThemeHelper::textStyle('h1')
 */
class ThemeHelper
{
    /**
     * Get a base color value
     *
     * @param string $name Color name (e.g., 'lime', 'charcoal', 'off_white', 'gray')
     * @return string|null Hex color code
     */
    public static function color(string $name): ?string
    {
        return config("theme.colors.{$name}");
    }

    /**
     * Get a neutral scale color
     *
     * @param int $shade Shade value (50, 100, 200, ..., 900)
     * @return string|null Hex color code
     */
    public static function neutral(int $shade): ?string
    {
        return config("theme.colors.neutral.{$shade}");
    }

    /**
     * Get a lime scale color
     *
     * @param int $shade Shade value (50, 100, 200, ..., 900)
     * @return string|null Hex color code
     */
    public static function lime(int $shade): ?string
    {
        return config("theme.colors.lime_scale.{$shade}");
    }

    /**
     * Get a semantic color
     *
     * @param string $type Type of semantic color (success, warning, error, info)
     * @param string $variant Variant (base, tint, shade)
     * @return string|null Hex color code
     */
    public static function semantic(string $type, string $variant = 'base'): ?string
    {
        return config("theme.colors.semantic.{$type}.{$variant}");
    }

    /**
     * Get a surface color
     *
     * @param string $type Surface type (page, elevated, inverse)
     * @return string|null Hex color code
     */
    public static function surface(string $type): ?string
    {
        return config("theme.colors.surface.{$type}");
    }

    /**
     * Get a border color
     *
     * @param string $type Border type (default, strong)
     * @return string|null Hex color code
     */
    public static function border(string $type = 'default'): ?string
    {
        return config("theme.colors.border.{$type}");
    }

    /**
     * Get a text color
     *
     * @param string $type Text type (primary, secondary, muted, on_inverse, on_dark)
     * @return string|null Hex color code
     */
    public static function text(string $type = 'primary'): ?string
    {
        return config("theme.colors.text.{$type}");
    }

    /**
     * Get a brand color
     *
     * @param string $brand Brand name (google_blue, google_red, google_yellow, google_green)
     * @return string|null Hex color code
     */
    public static function brand(string $brand): ?string
    {
        return config("theme.colors.brand.{$brand}");
    }

    /**
     * Get rating yellow color
     *
     * @return string Hex color code
     */
    public static function ratingYellow(): string
    {
        return config('theme.colors.rating_yellow', '#FFC107');
    }

    /**
     * Get a font family
     *
     * @param string $type Font type (heading, body)
     * @return string|null Font family name
     */
    public static function font(string $type): ?string
    {
        return config("theme.typography.fonts.{$type}");
    }

    /**
     * Get a complete text style configuration
     *
     * @param string $style Style name (display, h1, h2, h3, h4, h5, body_large, body, label, small_label, caption)
     * @return array|null Array containing font_family, font_size, line_height, font_weight
     */
    public static function textStyle(string $style): ?array
    {
        return config("theme.typography.styles.{$style}");
    }

    /**
     * Get color scheme value
     *
     * @param string $key Color scheme key (primary, on_primary, secondary, etc.)
     * @param string $mode Mode (light, dark) - currently only light is supported
     * @return string|null Hex color code
     */
    public static function colorScheme(string $key, string $mode = 'light'): ?string
    {
        return config("theme.color_scheme.{$mode}.{$key}");
    }

    /**
     * Generate inline style attribute for text style
     *
     * @param string $style Style name
     * @return string Inline CSS style string
     */
    public static function textStyleAttribute(string $style): string
    {
        $config = self::textStyle($style);
        
        if (!$config) {
            return '';
        }

        return sprintf(
            'font-family: %s; font-size: %s; line-height: %s; font-weight: %d;',
            $config['font_family'],
            $config['font_size'],
            $config['line_height'],
            $config['font_weight']
        );
    }

    /**
     * Generate CSS class name for text style
     *
     * @param string $style Style name
     * @return string CSS class name
     */
    public static function textClass(string $style): string
    {
        return "text-{$style}";
    }

    /**
     * Get all colors as an array for JavaScript
     *
     * @return array All theme colors
     */
    public static function allColors(): array
    {
        return config('theme.colors', []);
    }

    /**
     * Get all typography settings as an array for JavaScript
     *
     * @return array All typography settings
     */
    public static function allTypography(): array
    {
        return config('theme.typography', []);
    }
}
