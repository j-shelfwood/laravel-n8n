# Changelog

All notable changes to `shelfwood/laravel-n8n` are documented in this file.

## [0.1.3] - 2026-04-24

### Fixed

- `N8nStatus::resolveClassName()` now reads the host application's `composer.json` PSR-4 map instead of hard-coded `app/` and `modules/` prefixes. Fixes the 0-events bug on the admin page for consumers using `src/Domain/` or other custom layouts. Existing `app/` and `modules/` layouts continue to work when mapped in `composer.json`.

## [0.1.2] - 2026-04-23

- Use native Filament section components for proper dark mode and spacing.
- Make webhook dispatch truly fire-and-forget — catch all exceptions.

## [0.1.1] - 2026-04-22

- Add tests, README, CI, LICENSE, and make Filament optional.

## [0.1.0] - 2026-04-22

- Initial release: tag-based n8n webhook integration for Laravel.
