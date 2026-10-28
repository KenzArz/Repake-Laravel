<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Theme Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the theme configuration converted from the original
    | Dart/Flutter theme files. It includes color palettes, typography settings,
    | and semantic color mappings.
    |
    */

    'colors' => [
        // Base Colors
        'charcoal' => '#202320',
        'lime' => '#B8D94E',
        'off_white' => '#F5F5F0',
        'gray' => '#D9DBD5',

        // Neutral Scale
        'neutral' => [
            50 => '#F5F5F0',
            100 => '#EAEBE6',
            200 => '#D9DBD5',
            300 => '#C2C4BE',
            400 => '#A4A6A1',
            500 => '#868984',
            600 => '#6A6D69',
            700 => '#515450',
            800 => '#3A3C39',
            900 => '#202320',
        ],

        // Lime Scale
        'lime_scale' => [
            50 => '#EFF2E0',
            100 => '#E6EEC8',
            200 => '#DAE8A7',
            300 => '#CDE387',
            400 => '#C1DD66',
            500 => '#B8D94E',
            600 => '#9DB846',
            700 => '#81973D',
            800 => '#647535',
            900 => '#46502C',
        ],

        // Semantic Colors (base = status color, tint = badge background, shade = text on tint)
        'semantic' => [
            'success' => [
                'base' => '#4C8B4A',
                'tint' => '#E1E8DC',
                'shade' => '#365735',
            ],
            'warning' => [
                'base' => '#D9A23B',
                'tint' => '#F2EBDA',
                'shade' => '#7C622E',
            ],
            'error' => [
                'base' => '#C1543F',
                'tint' => '#EFE2DB',
                'shade' => '#703C30',
            ],
            'info' => [
                'base' => '#4E7DA6',
                'tint' => '#E1E7E7',
                'shade' => '#375063',
            ],
        ],

        // Surface / Border / Text
        'surface' => [
            'page' => '#F5F5F0',
            'elevated' => '#FFFFFF',
            'inverse' => '#202320',
        ],

        'border' => [
            'default' => '#D9DBD5',
            'strong' => '#A4A6A1',
        ],

        'text' => [
            'primary' => '#202320',
            'secondary' => '#6A6D69',
            'muted' => '#868984',
            'on_inverse' => '#F5F5F0',
            'on_dark' => '#FFFFFF',
        ],

        // Additional Colors
        'rating_yellow' => '#FFC107',

        // Brand Colors
        'brand' => [
            'google_blue' => '#4285F4',
            'google_red' => '#EA4335',
            'google_yellow' => '#FBBC05',
            'google_green' => '#34A853',
        ],
    ],

    'typography' => [
        // Font Families
        'fonts' => [
            'heading' => 'Bricolage Grotesque',
            'body' => 'Manrope',
        ],

        // Text Styles
        'styles' => [
            'display' => [
                'font_family' => 'Bricolage Grotesque',
                'font_size' => '40px',
                'line_height' => 1.05,
                'font_weight' => 800,
            ],
            'h1' => [
                'font_family' => 'Bricolage Grotesque',
                'font_size' => '32px',
                'line_height' => 1.1,
                'font_weight' => 700,
            ],
            'h2' => [
                'font_family' => 'Bricolage Grotesque',
                'font_size' => '28px',
                'line_height' => 1.15,
                'font_weight' => 700,
            ],
            'h3' => [
                'font_family' => 'Bricolage Grotesque',
                'font_size' => '24px',
                'line_height' => 1.2,
                'font_weight' => 700,
            ],
            'h4' => [
                'font_family' => 'Bricolage Grotesque',
                'font_size' => '20px',
                'line_height' => 1.3,
                'font_weight' => 600,
            ],
            'h5' => [
                'font_family' => 'Bricolage Grotesque',
                'font_size' => '18px',
                'line_height' => 1.4,
                'font_weight' => 600,
            ],
            'body_large' => [
                'font_family' => 'Manrope',
                'font_size' => '17px',
                'line_height' => 1.6,
                'font_weight' => 400,
            ],
            'body' => [
                'font_family' => 'Manrope',
                'font_size' => '16px',
                'line_height' => 1.6,
                'font_weight' => 400,
            ],
            'label' => [
                'font_family' => 'Bricolage Grotesque',
                'font_size' => '14px',
                'line_height' => 1.5,
                'font_weight' => 500,
            ],
            'small_label' => [
                'font_family' => 'Bricolage Grotesque',
                'font_size' => '12px',
                'line_height' => 1.5,
                'font_weight' => 500,
            ],
            'caption' => [
                'font_family' => 'Manrope',
                'font_size' => '12px',
                'line_height' => 1.4,
                'font_weight' => 400,
            ],
        ],
    ],

    'color_scheme' => [
        'light' => [
            'primary' => '#B8D94E',
            'on_primary' => '#202320',
            'secondary' => '#3A3C39',
            'on_secondary' => '#F5F5F0',
            'surface' => '#FFFFFF',
            'on_surface' => '#202320',
            'on_surface_variant' => '#6A6D69',
            'outline' => '#A4A6A1',
            'outline_variant' => '#D9DBD5',
            'error' => '#C1543F',
            'on_error' => '#FFFFFF',
            'inverse_surface' => '#202320',
            'on_inverse_surface' => '#F5F5F0',
        ],
    ],
];
