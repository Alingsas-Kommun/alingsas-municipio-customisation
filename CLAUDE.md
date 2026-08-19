# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A WordPress plugin (`municipio-customisation`, "Alingsås Municipio customisation") that adds Alingsås kommun's
business and design customisations on top of a site built on the [Municipio](https://getmunicipio.com/) theme
([source](https://github.com/helsingborg-stad/Municipio)). It does not replace Municipio functionality — it
extends/decorates it via WordPress hooks. Runs on PHP 8.1+.

Depends on (via Composer, resolved by the parent site project): `helsingborg-stad/api-event-manager-integration`
(events), `considbrs-webdev/typesense-search` (site search — this plugin only adapts the search result page title,
it does not implement search itself), Advanced Custom Fields + ACF Export Manager, and Modularity.

## Commands

```bash
npm ci               # install JS deps
npm run dev           # start Vite dev server (HTTPS on localhost:5173)
npm run build         # build production assets into dist/ (required before deploying — dist/ is gitignored)
npm run make-pot      # regenerate languages/municipio-customisation.pot after changing translatable strings
```

There is no PHP test suite, linter config, or `composer` scripts defined in this plugin — don't assume `composer test`/`phpunit`/`phpcs` exist here.

Asset loading (`includes/Scripts.php`) is environment-aware: when `wp_get_environment_type() === 'development'`
it loads `src/js/main.js` straight from the Vite dev server; otherwise it reads `dist/manifest.json` and enqueues
the built, hashed file. So JS/SCSS changes need `npm run build` before they'll show up in non-development environments.

WP-CLI media-cleanup commands (see Architecture below) are namespaced `wp alingsas <command>`, e.g.
`wp alingsas find-unused-images --limit=all`.

## General rules

Fixes and customisations for this site must be contained within this plugin (or, when truly unavoidable, called
out explicitly to the user as a change needed elsewhere) — don't edit the Municipio theme, other plugins, or
mu-plugins directly, even when the root cause of a bug lives there. Prefer hooks, filters, and CSS/JS overrides
loaded from this plugin over patching vendor code.

Commit messages must not include a `Co-Authored-By` trailer.

## Architecture

### Bootstrapping and autoloading convention

Everything starts in `municipio-customisation.php` (`AlingsasCustomisation\Plugin`). On construction it, in order:

1. Loads the plugin textdomain.
2. `require_once`s every file in `helpers/*.php`.
3. `require_once`s every `components/<Name>/<Name>.php`.
4. `require_once`s every file in `includes/*.php` and `includes/**/*.php`, then **instantiates** any class whose
   name matches a derived namespace/classname pattern: a file at `includes/foo/Bar.php` must declare
   `class Bar` in namespace `\AlingsasCustomisation\Includes\Foo` for it to be auto-`new`'d. If the derived
   class doesn't exist (wrong namespace, or the file defines something else entirely), it's silently skipped —
   this is intentional for files like `includes/controllers/AkEventTemplate.php`, which declares a
   `Modularity\Module\Posts\TemplateController\AkEventTemplate` class instead (a Modularity template controller,
   picked up by Modularity's own mechanism, not by this bootstrap).

Consequence: **every feature lives in a class whose constructor wires up its own `add_action`/`add_filter` calls.**
There is no central hook registry — to find what a given WP hook does, grep for it across `includes/`. To add a
new feature, add a new `includes/<Area>/<Name>.php` with a matching namespaced class; it's picked up automatically,
no registration step needed elsewhere.

`includes/Cron.php` is the one exception that manually instantiates its sub-classes (`includes/cron/*.php`) itself
rather than relying on the auto-instantiation, since cron job classes need to be constructed in a specific place.

### Feature areas (includes/)

- **Appearance/theming**: `AppearanceSettings.php` + `helpers/Appearance.php` — an options page (Utseende →
  Alingsås) for custom colors and named "themes" (a theme = a named reference to one of the custom colors).
  Themes can be assigned per-page or per-URL-path and are emitted as CSS custom properties (`--alingsas-color-*`,
  `--alingsas-theme-*`) in `wp_head`. `ExtraSettings.php` adds further per-page settings (hide title/breadcrumbs/
  sidebar, background stripe color that can reference a theme).
- **Events**: `Event.php`, `Events.php`, `helpers/Events.php` (`Events::parseEvent()` decorates a Municipio
  `PostObjectInterface` with computed display fields — date/time formatting incl. all-day detection, image,
  location, categories/tags, archive link), `components/Event/` (a custom Blade component for event cards, used
  by the posts module), `includes/controllers/AkEventTemplate.php` (Modularity Posts module template controller
  that renders upcoming events), `views/Events/*.blade.php` and `views/events.blade.php` (template overrides).
- **Jobs (lediga-jobb)**: `Hooks.php` appends extra JobPosting schema fields (start date, work hours, duration,
  read-more URL) to the single job view's info list; `views/Jobs/single-schema-jobposting.blade.php` is the
  matching template override.
- **Digital anslagstavla (noticeboard)**: `Announcements.php` + `includes/cron/Announcement.php` — validation,
  admin notice (title-naming convention), and an hourly cron (`alingsas_announcement_posts_archiver`) that
  archives posts past their configured archive date.
- **News**: `NewsArchive.php` + `includes/cron/News.php` — a custom "archived" post status for `nyheter`, an
  options page for the retention period (days before auto-archiving), and a WP-CLI-registered cron routine.
- **Webcasts**: `Webcasts.php` — embeds a livestream from an ACF field, disables comments for the `webcast` CPT.
- **Media cleanup (WP-CLI)**: `includes/cron/{Find,Check,Mark,Delete}Unused{Images,Pdfs}.php` — eight
  `wp alingsas <verb>-unused-{images,pdfs}` commands forming a pipeline: **find** (scan post content/postmeta/
  termmeta, incl. serialized data and resized-filename patterns, for references to each attachment; write a JSON
  report) → **check** → **mark** (flag unreferenced attachments with postmeta `_marked_unused`, filterable in the
  media library UI via `Media.php`) → **delete**. Reports are written under `data/*-report*.json` (git-ignored
  except the `*.template.json` examples); treat existing report JSON files as generated scratch data, not source.
  `Media.php` adds the "show only unused" checkbox to `upload.php` (list + grid/AJAX views).
- **WP All Import**: `WpAllImport.php` — moves temporary unpublish-related fields into proper post meta after
  import.
- **Menu filtering**: `includes/navigation/MenuFilter.php` — hides orphaned top-level pages (no parent, no menu
  or published children) from Municipio's navigation.
- **Search**: `Search.php` — only adapts the search results page heading to Municipio's terminology; actual
  search is provided by the separate `considbrs-webdev/typesense-search` plugin (see README for its Typesense
  connection constants).
- **ACF**: `ACF.php` uses `AcfExportManager` to auto-export/import the field groups under `acf/php/` + `acf/json/`
  (keep both in sync — the PHP files are the source ACF Export Manager writes from/imports on `acf/init`) and
  registers a custom location rule (`includes/ACF/Modularity_Location.php`) for targeting ACF fields at specific
  Modularity modules. Field group keys used elsewhere in code (e.g. `AppearanceSettings::FIELD_*`) are ACF field
  keys — cross-reference `acf/php/*.php` when changing field behavior.
- **Modularity modules**: `includes/modules/*.php` (`InlayList`, `ManualInput`, `Posts`, `Recommend`) — settings/
  behavior for specific Modularity module types; `src/js/lib/manualinput-search.js` implements free-text search
  for the "Manual input" module when enabled in its settings.
- **Decorators**: `Decorators.php` conditionally requires and applies `src/Decorators/HideTitlePostObject.php`
  (note: lives under `src/`, not `includes/`, so it's outside the auto-instantiation bootstrap) via the
  `Municipio/DecoratePostObject` filter to blank a post's title when its `hide_title` ACF field is set.
- **Misc**: `ContentSecurityPolicy.php`, `Config.php` (assorted small Municipio/ComponentLibrary filter tweaks —
  accessibility menu items, button classes, page excerpts, stripping HTML from `nyheter` excerpts), `Sidebar.php`
  (right sidebar shown by default on singular content unless explicitly hidden), `Translation.php`,
  `Worddown.php`.

### Views and components (Blade)

Modularity/Municipio render Blade templates. This plugin adds its own search paths for both, so its views/
components are found without touching theme or Modularity code:

- `Municipio/viewPaths` (in `TemplateOverrides.php`) — adds `views/`, plus `views/Events/` or `views/Jobs/` when on
  the matching archive/singular, so files there override the corresponding Municipio template.
- `Modularity/Module/TemplatePath` / `Modularity/Module/Posts/TemplatePath` — adds `views/` for module template
  overrides (e.g. event cards inside the Posts module).
- `helsingborg-stad/blade/internalComponentsPath`, `helsingborg-stad/blade/controllerPaths`,
  `ComponentLibrary/ViewPaths` (in `Components.php`) — adds `components/` (and `views/`) so custom components like
  `components/Event/` are discoverable. A component follows the pattern `components/<Name>/<Name>.php` (a
  `ComponentLibrary\Component\BaseController` subclass) + `<Name>.blade.php` + `<Name>.json`.

### Frontend assets

Vite-built from `src/js/main.js` and `src/scss/main.scss` (see `vite.config.mjs`) into `dist/`, with a
`manifest.json` for hashed-filename lookup at runtime (see Commands section above). `jquery` is treated as an
external (provided by WordPress, not bundled).

### Versioning

Semantic versioning. The version number must be kept in sync in four places: the `Version:` header comment in
`municipio-customisation.php`, `Plugin::VERSION` in the same file, `composer.json`'s `version`, and the
"Aktuell version är **X.Y.Z**." line in `README.md`.

`CHANGELOG.md` tracks notable changes per release, starting at 1.0.0. Update it (including an `[Unreleased]`
section) alongside version bumps rather than relying solely on `git log`.

Release tags on GitHub are **unprefixed** (`1.0.4`, not `v1.0.4`). To cut a release — bump the version, update
the changelog, merge `dev` into `main`, tag, push, and publish a GitHub release with changelog-derived notes —
use the `/release` skill (`.claude/skills/release/SKILL.md`) rather than improvising the steps; it also covers
when to stop short of a public release (e.g. a plain version-bump request shouldn't push/tag/publish on its
own).
