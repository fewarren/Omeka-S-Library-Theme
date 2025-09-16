# Library Theme (Omeka S)

A production-focused Omeka S theme tailored to the Library’s design system, with flexible typography, unified colors/shape, and clean, minimal runtime assets. Cormorant Garamond support is included.

- Omeka S compatibility: ^4.1.0
- Zero build required: no npm install, no bundling
- Minimal CSS/JS, cache-busted, CDN fonts

## Features

- Style Presets: Traditional, Modern (Library)
- Typography controls: H1, H2, H3, Body (family, size, weight, style, color)
- Colors & Shape: primary, accent; global box border width/radius
- TOC styling: font, size (or rem override), weight/style, colors + hover
- Pagination styling: background, text, hover background/text, size, typography
- Menu & Footer typography
- Breadcrumbs pill style toggle
- Resource Page Regions enabled (items, item sets, media)
- Runtime caption background guard for video thumbnails
- Debug gating: console logs only when URL contains ?debug

## Installation

1) Copy this theme directory to Omeka S themes folder and activate in Admin → Sites → Theme.
2) Configure the theme in Admin → Sites → Theme settings.

No build steps are required.

## Configuration Overview

- Preset selection: Settings → Style Preset (Traditional or Modern). Saved settings are used at render time with preset fallback for unspecified values.
- Typography:
  - H1/H2/H3/Body families include Georgia, Cormorant Garamond, Helvetica Neue, Arial, etc.
  - Body font-size is applied only to the body element; headings retain their own sizes. This fixes prior HTML block H2 size issues.
- Colors & Shape:
  - Primary and accent colors drive headings/hover, etc.
  - Global “Box Border Width” and “Box Border Radius” control pill/box styling across TOC, breadcrumbs, pagination, nav.
- TOC:
  - Choose size via preset options or override precisely using “TOC Font Size (rem)”.
  - Text/background + hover colors are explicitly configurable.
- Pagination:
  - Background/text color and explicit hover background/text color.
  - Button size (small/medium/large) and full typography controls.
- Header & Branding:
  - Logo, header height, optional Browse/Search buttons (via simple URL fields).
- Tagline:
  - Family, size, weight/style, color, and hover colors.
- Menu & Footer typography:
  - Family, weight/style, and colors (footer uses Footer Typography group).
- Breadcrumbs:
  - Pill style toggle enabled by default.

## Behavior and Rendering

- Dynamic CSS partial (view/common/theme-setting-css.phtml) injects CSS using saved settings with preset fallback.
- CSS load order: library.css → library-polish.css → library-reoriented-design.css → font-overrides.css (highest specificity).
- Fonts: Cormorant Garamond is loaded via Google Fonts.
- Captions: asset/js/caption-fix.js enforces white backgrounds for video thumbnail tiles and captions as a safety guard.
- Debugging: internal debug logging silenced by default; add `?debug` to the URL to enable.

## Dev Tools

- dev-tools/export-modern-defaults.php (CLI):
  - Export current site’s saved theme settings as JSON for updating defaults.
  - Usage: `php dev-tools/export-modern-defaults.php <site_id>` (run on the host with access to /var/www/omeka-s/config/database.ini)
  - Keys include modern fields (box_border_width/radius, explicit pagination hover colors, accent_color).

See MAINTENANCE.md for safe operating procedures, annual maintenance (footer year), and lessons learned.

## Project Structure (selected)

- config/theme.ini: Theme settings and element groups (admin UI grouping)
- view/layout/layout.phtml: Main layout; includes CSS/JS and preset fallback handling
- view/common/theme-setting-css.phtml: Dynamic CSS from settings
- asset/css/*.css: Base and override styles
- asset/js/caption-fix.js: Runtime guard for caption/tile white backgrounds
- dev-tools/export-modern-defaults.php: Exporter for capturing current settings

## License

Distributed under the site’s standard terms for themes. See project LICENSE if included; Omeka S is GPLv3.
