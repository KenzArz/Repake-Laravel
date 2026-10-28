# Laravel Theme Documentation

## Overview

This theme system has been converted from the original Dart/Flutter theme files located in the `/theme` directory. All color values, typography settings, and design tokens have been preserved exactly as they were in the original files.

## File Structure

```
resources/css/theme/
├── README.md           # This documentation file
├── colors.css          # All color definitions (converted from app_colors.dart)
├── typography.css      # All typography styles (converted from app_text_styles.dart)
└── theme.css           # Main theme file with utilities (converted from app_theme.dart)

config/
└── theme.php           # PHP configuration for server-side theme access
```

## Installation

### 1. Import Fonts

Add the required Google Fonts to your main Blade layout:

```html
<head>
    <!-- Bricolage Grotesque for headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght,opsz@400..800,12..96&display=swap" rel="stylesheet">
    
    <!-- Manrope for body text -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
```

### 2. Import Theme CSS

In your main CSS file or Blade layout:

```css
@import './theme/theme.css';
```

Or in your Blade layout:

```html
<link rel="stylesheet" href="{{ asset('css/theme/theme.css') }}">
```

## Usage

### Colors

#### CSS Variables

All colors are available as CSS variables:

```css
.my-element {
  background-color: var(--color-lime);
  color: var(--color-text-primary);
  border-color: var(--color-border-default);
}
```

#### Utility Classes

Pre-defined utility classes for common use cases:

```html
<!-- Background colors -->
<div class="bg-primary">Primary background</div>
<div class="bg-secondary">Secondary background</div>
<div class="surface-elevated">Elevated surface</div>

<!-- Text colors -->
<p class="text-primary">Primary text</p>
<p class="text-secondary">Secondary text</p>
<p class="text-muted">Muted text</p>

<!-- Semantic colors -->
<span class="text-success">Success message</span>
<span class="text-warning">Warning message</span>
<span class="text-error">Error message</span>
<span class="text-info">Info message</span>

<!-- Badges -->
<span class="badge-success">Success</span>
<span class="badge-warning">Warning</span>
<span class="badge-error">Error</span>
<span class="badge-info">Info</span>
```

#### PHP Configuration

Access colors in your PHP/Blade code:

```php
// Get a color
$primaryColor = config('theme.colors.lime');

// Get semantic colors
$successBase = config('theme.colors.semantic.success.base');
$successTint = config('theme.colors.semantic.success.tint');

// Get neutral shades
$neutral500 = config('theme.colors.neutral.500');
```

### Typography

#### CSS Variables

All typography settings are available as CSS variables:

```css
.custom-heading {
  font-family: var(--font-heading);
  font-size: var(--text-h1-size);
  line-height: var(--text-h1-height);
  font-weight: var(--text-h1-weight);
}
```

#### Typography Classes

Use pre-defined classes for consistent text styling:

```html
<!-- Headings -->
<h1 class="text-display">Display Text</h1>
<h1>H1 Heading (auto-styled)</h1>
<h2>H2 Heading (auto-styled)</h2>
<h3>H3 Heading (auto-styled)</h3>
<h4>H4 Heading (auto-styled)</h4>
<h5>H5 Heading (auto-styled)</h5>

<!-- Body text -->
<p class="text-body-large">Large body text</p>
<p class="text-body">Regular body text</p>
<p class="text-caption">Caption text</p>

<!-- Labels -->
<label class="text-label">Form Label</label>
<span class="text-small-label">Small Label</span>
```

#### PHP Configuration

Access typography settings in PHP:

```php
// Get font families
$headingFont = config('theme.typography.fonts.heading');
$bodyFont = config('theme.typography.fonts.body');

// Get text style settings
$h1Style = config('theme.typography.styles.h1');
// Returns: ['font_family' => '...', 'font_size' => '...', ...]
```

## Color Palette

### Base Colors
- **Charcoal**: `#202320` - Main dark color
- **Lime**: `#B8D94E` - Primary brand color
- **Off White**: `#F5F5F0` - Soft white
- **Gray**: `#D9DBD5` - Neutral gray

### Neutral Scale (50-900)
From lightest to darkest:
- 50: `#F5F5F0`
- 100: `#EAEBE6`
- 200: `#D9DBD5`
- 300: `#C2C4BE`
- 400: `#A4A6A1`
- 500: `#868984`
- 600: `#6A6D69`
- 700: `#515450`
- 800: `#3A3C39`
- 900: `#202320`

### Lime Scale (50-900)
From lightest to darkest:
- 50: `#EFF2E0`
- 100: `#E6EEC8`
- 200: `#DAE8A7`
- 300: `#CDE387`
- 400: `#C1DD66`
- 500: `#B8D94E`
- 600: `#9DB846`
- 700: `#81973D`
- 800: `#647535`
- 900: `#46502C`

### Semantic Colors

Each semantic color has three variants:
- **Base**: Main status color
- **Tint**: Light background for badges
- **Shade**: Text color on tint backgrounds

#### Success (Green)
- Base: `#4C8B4A`
- Tint: `#E1E8DC`
- Shade: `#365735`

#### Warning (Yellow/Orange)
- Base: `#D9A23B`
- Tint: `#F2EBDA`
- Shade: `#7C622E`

#### Error (Red)
- Base: `#C1543F`
- Tint: `#EFE2DB`
- Shade: `#703C30`

#### Info (Blue)
- Base: `#4E7DA6`
- Tint: `#E1E7E7`
- Shade: `#375063`

### Additional Colors
- **Rating Yellow**: `#FFC107` - For star ratings
- **Google Brand Colors**: Blue, Red, Yellow, Green

## Typography Scale

### Font Families
- **Heading Font**: Bricolage Grotesque
- **Body Font**: Manrope

### Text Styles

| Style | Font | Size | Line Height | Weight |
|-------|------|------|-------------|---------|
| Display | Bricolage Grotesque | 40px | 1.05 | 800 |
| H1 | Bricolage Grotesque | 32px | 1.1 | 700 |
| H2 | Bricolage Grotesque | 28px | 1.15 | 700 |
| H3 | Bricolage Grotesque | 24px | 1.2 | 700 |
| H4 | Bricolage Grotesque | 20px | 1.3 | 600 |
| H5 | Bricolage Grotesque | 18px | 1.4 | 600 |
| Body Large | Manrope | 17px | 1.6 | 400 |
| Body | Manrope | 16px | 1.6 | 400 |
| Label | Bricolage Grotesque | 14px | 1.5 | 500 |
| Small Label | Bricolage Grotesque | 12px | 1.5 | 500 |
| Caption | Manrope | 12px | 1.4 | 400 |

## Material Design 3 Color Scheme

The theme follows Material Design 3 principles with the following mappings:

- **Primary**: Lime (`#B8D94E`)
- **On Primary**: Charcoal (`#202320`)
- **Secondary**: Neutral 800 (`#3A3C39`)
- **On Secondary**: Neutral 50 (`#F5F5F0`)
- **Surface**: White (`#FFFFFF`)
- **On Surface**: Text Primary (`#202320`)
- **Error**: Error Base (`#C1543F`)

## Examples

### Complete Card Component

```html
<div class="surface-elevated border-default" style="padding: 1.5rem; border-width: 1px; border-radius: 8px;">
  <h3 class="text-h3">Card Title</h3>
  <p class="text-body text-secondary" style="margin-top: 0.5rem;">
    This is a card with elevated surface and default border.
  </p>
  <div style="margin-top: 1rem;">
    <span class="badge-success" style="padding: 0.25rem 0.75rem; border-radius: 4px;">
      Active
    </span>
  </div>
</div>
```

### Form with Labels

```html
<form>
  <div style="margin-bottom: 1rem;">
    <label class="text-label" style="display: block; margin-bottom: 0.5rem;">
      Email Address
    </label>
    <input 
      type="email" 
      class="border-default" 
      style="width: 100%; padding: 0.5rem; border-width: 1px; border-radius: 4px;"
    >
    <span class="text-caption text-muted" style="display: block; margin-top: 0.25rem;">
      We'll never share your email.
    </span>
  </div>
</form>
```

### Alert Messages

```html
<!-- Success Alert -->
<div class="badge-success" style="padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
  <strong class="text-label">Success!</strong>
  <p class="text-body" style="margin-top: 0.25rem;">Your changes have been saved.</p>
</div>

<!-- Error Alert -->
<div class="badge-error" style="padding: 1rem; border-radius: 8px;">
  <strong class="text-label">Error!</strong>
  <p class="text-body" style="margin-top: 0.25rem;">Something went wrong. Please try again.</p>
</div>
```

## Notes

- All values are preserved exactly from the original Dart theme files
- No colors or typography settings have been added or removed
- The theme follows Material Design 3 principles
- All measurements use pixels for consistency with the original design
- Font weights use numeric values (400, 500, 600, 700, 800)

## Integration with Tailwind CSS

If you're using Tailwind CSS, you can extend your `tailwind.config.js` to use these theme values:

```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        'lime': '#B8D94E',
        'charcoal': '#202320',
        // Add more colors as needed
      },
      fontFamily: {
        'heading': ['Bricolage Grotesque', 'sans-serif'],
        'body': ['Manrope', 'sans-serif'],
      },
    },
  },
}
```
