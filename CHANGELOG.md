# Changelog

All notable changes to VilloCity Suite. The release workflow copies the section for each tag into the GitHub Release notes, which WordPress shows in "View version details".

## [0.0.2]

- Test release to confirm automatic updates.

## [0.0.1]

Ground-up rebuild of the 2.x plugin.

### Added
- Module system: per-site on/off from **VilloCity → Modules**, or locked via `VILLOCITY_MODULES` in wp-config.php.
- Automatic updates from GitHub Releases (Plugin Update Checker).
- Automatic migration of each site's 2.x module selection and Alert Bar settings.
- Alert Bar: start/end scheduling, automatic placement after `<body>`, per-message dismissal.

### Changed
- Resources: same content and shortcode; fixed the editor's button-text field, honoured `columns`, added capability checks.
- Brand colours are exposed as `--voc-primary` and `--voc-accent` CSS variables, with optional Elementor global-colour inheritance.

### Removed (compared with 2.x)
- Dashboard and login redirects, forced admin-menu position, global CSS reset, site-wide cache purging on every settings change, global search-page takeover.
