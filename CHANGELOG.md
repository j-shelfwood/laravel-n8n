# Changelog

All notable changes to `shelfwood/laravel-n8n` are documented in this file.

## [0.2.0] - 2026-04-27

### Added

- **Workflow list caching.** `N8nService::getWorkflows()` now caches the n8n
  REST response for `n8n.workflows.cache_ttl` seconds (default 60). Per-event
  dispatch jobs no longer refetch the full workflow set; high-volume event
  traffic collapses to one upstream call per TTL window. Set `cache_ttl=0`
  to disable. New `flushWorkflowsCache()` method for explicit invalidation.
- **Configurable tag prefix** via `n8n.tag_prefix` (default `app:`). Set to
  `staging:` / `prod:` per environment to discriminate workflows on a single
  shared n8n instance without duplicated workflow definitions. Both the
  trait's instance and static tag generators read the config.
- **Orphaned-workflow detection** on the Filament status page. Active
  workflows tagged with the configured prefix that match no discovered event
  class are surfaced in their own section so operators spot drift after
  event renames or deletions.
- **`DispatchN8nWebhook` now declares `$tries = 3`, `$backoff = 5`, and a
  `failed()` hook** that logs at warning level on permanent failure. Replaces
  reliance on Laravel queue defaults; consumers wanting different retry
  behaviour should subclass and override.

### Documentation

- README: domain-driven layout note (`src/Domain/*/Events`) and
  multi-environment tag-prefix guide.

### Compatibility

Non-breaking. Default tag prefix stays `app:`, default cache TTL of 60s is
transparent for low-volume callers, and the new orphaned-workflow section
only renders when n8n is reachable and orphans exist.

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
