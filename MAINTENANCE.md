# Library Theme Maintenance Notes

Last updated: 2025-08-21

Purpose: Document the production-safe state of the theme, the dev files that were removed, what remains, and how to repeat common maintenance tasks without re-introducing dev-only artifacts.

## Current production footprint (kept)
- Module.php
- config/ (module.config.php, theme.ini)
- view/ (layout + templates)
- asset/ (css + js)
- helper/ (helpers used by theme)
- src/ (PHP classes)
- README.md, LICENSE, package.json (safe for future enhancements)

## Dev-only artifacts removed
- Debug/diagnostic PHP/HTML layouts and scripts
- Alternative layout snapshots (minimal/final/working/etc.)
- Deploy shell scripts and force-deploy scripts
- Analysis markdowns and logs
- Old/disabled config snapshots
- Test/demo assets (e.g., multilevel-accordion demo)

Rationale: These files are not used at runtime and risk confusion in production.

## Empty directories that may remain
Some empty folders may still be present (depending on tooling):
- backup-*/ (previous backups cleared of files)
- dev-archive/
- library-theme/ (nested)
- multilevel-accordion-menu/
- scripts/

These are harmless; remove at OS level if desired.

## Presets workflow (admin)
- Style Preset controls which default set is used as fallback for settings.
- Apply Preset to Settings (checkbox): when checked and saved, loads the preset values into the form for editing; save again to persist any changes.
- Rendering always uses saved settings with preset fallback. No invisible overrides.

## How to refresh defaults in the future (without dev scripts)
If you want to capture the current site’s settings and make them the new theme defaults:
1) Decide which site reflects the target look.
2) Manually update config/theme.ini and config/module.config.php defaults to match that site’s settings. Suggested approach:
   - Use the Admin UI to read current values in Theme Settings.
   - Optionally export settings via the database (DB admin or a temporary one-off script created for this purpose, then removed).
3) Commit and deploy. New sites and Load defaults will use the updated defaults.

Note: Avoid leaving any exporter or diagnostic scripts on the server; add them temporarily and remove after use.

## Safety recommendations
- Keep production theme minimal; avoid adding ad-hoc PHP or shell scripts under the theme path.
- Prefer the Admin interface for changes. For bulk/preset updates, make changes in config/module.config.php and theme.ini only.
- When adding new assets or fonts, use asset/ and update views accordingly.

## Contact
This theme has been pruned for production. For future enhancements or to temporarily re-introduce tooling (exporters, diagnostics), coordinate changes and ensure they are removed after use.



## Lessons learned (and dev-tools usage)
- Keep dev tooling out of production. When you need temporary helpers, place them under dev-tools/ and ensure DEPLOY.sh excludes them.
- A minimal deployment script with include-only rules prevents accidental promotion of dev artifacts. Use DRY_RUN=1 first.
- Presets UX: one checkbox “Apply Preset to Settings” is clearer than multiple modes. Rendering should always use saved settings with preset fallback.
- When adopting live settings as defaults, run a one-off exporter on the production host, then remove it. We retained a dev-only helper:
  - dev-tools/export-modern-defaults.php (CLI): outputs JSON of the current theme settings for a site_id.
  - Use only on the instance whose DB you want to sample (it reads /var/www/omeka-s/config/database.ini).
  - After capturing values, update config/theme.ini and config/module.config.php defaults, and re-deploy.
- CSS order matters. Ensure font-overrides.css and dynamic theme-setting CSS load after base CSS. Add runtime checks sparingly as guardrails.
- For specificity conflicts (e.g., anchor inside tagline), apply settings to inner elements explicitly and avoid widespread !important unless justified.
- Clean up legacy/duplicate config entries to reduce confusion and prevent drift. Make conservative edits to avoid breaking admin forms.
