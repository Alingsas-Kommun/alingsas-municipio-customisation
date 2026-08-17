# Changelog

All notable changes to this plugin are documented in this file, based on the project's git history.
Format loosely follows [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

## [1.0.6] - 2026-08-17

### Fixed

- Article content tables' horizontal-scroll fix is now scoped to narrow viewports only; it was previously also overriding desktop table layout.

## [1.0.5] - 2026-08-11

### Fixed

- Recommend module preload buttons no longer stay stuck showing "loading" when analytics cookies are declined (they were waiting on a RekAI script blocked by cookie consent).
- Article content tables no longer squeeze their columns into an unreadable wrap on narrow screens; they scroll horizontally instead, with a fade hinting there's more to scroll.

## [1.0.4] - 2026-08-11

### Fixed

- Event that runs 00:00–23:59 is now displayed as "All day"; links to the proper instance of a recurring event.
- Removed empty event categories from the filter list.

## [1.0.3] - 2026-08-10

### Fixed

- Orphaned page filter no longer hides menu category headers.
- Search page heading styling now applies regardless of which WordPress search engine is active.

### Added

- Extra Typesense metadata for Worddown export.

### Changed

- Fixes for search appearance.
- Removed admin styles.
- Removed shadow on manual input icon.

## [1.0.2] - 2026-06-19

### Fixed

- Publish the Vite manifest in `dist`.

### Changed

- Docs: use unprefixed release tags.

## [1.0.1] - 2026-06-19

### Changed

- Loosened the `symfony/http-client` version constraint so it no longer requires a specific version.

## [1.0.0] - 2026-06-19

### Changed

- Site search is now provided by the `considbrs-webdev/typesense-search` plugin instead of this plugin's own
  search template; the custom search template was removed.
- Tweaks to work properly with the Typesense search plugin.
- Horizontal layout for the card heading container in manual input styles.

### Added

- `considbrs-webdev/typesense-search` included as a Composer dependency.
- Admin list columns (meeting date, archived date) for Announcements.
