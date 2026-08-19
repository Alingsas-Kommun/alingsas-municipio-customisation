---
name: release
description: Cut a new plugin release - bump the version, update CHANGELOG.md, merge dev into main, tag, push, and publish a GitHub release. Use when the user asks to release, cut/ship a version, bump the plugin version, or publish a new version.
---

Release process for this plugin. Follow these steps in order; do not skip the confirmation points.

## 1. Determine the new version number

- Read the current version from `municipio-customisation.php`'s `Version:` header (or `Plugin::VERSION`).
- Default to a **patch** bump unless the changes since the last tag clearly add a feature (**minor**) or break
  compatibility (**minor** is also used for breaking changes pre-1.0-style in this repo's history — check
  `CHANGELOG.md` for precedent before assuming semver's strict "breaking = major").
- If ambiguous, ask the user which bump they want rather than guessing.

## 2. Gather what's changing

- Find the last release tag: `git tag -l --sort=-v:refname | head -1`.
- List commits since that tag on the branch being released (usually `dev`):
  `git log --format='%ad|%s' --date=short <last-tag>..dev`.
- If `CHANGELOG.md` already has an `[Unreleased]` section with entries, prefer those (they're the
  human-curated version) over re-deriving from raw commit subjects. Only fall back to raw commits if
  `[Unreleased]` is empty.

## 3. Bump the version in all four places (must stay in sync)

- `municipio-customisation.php`: the `Version:` header comment.
- `municipio-customisation.php`: `Plugin::VERSION` constant.
- `composer.json`: the `version` field.
- `README.md`: the "Aktuell version är **X.Y.Z**." line under "## Versionering".

## 4. Update CHANGELOG.md

- Move the `[Unreleased]` entries into a new `## [X.Y.Z] - YYYY-MM-DD` section (today's date), grouped under
  `### Added` / `### Changed` / `### Fixed` / `### Removed` as appropriate (omit empty groups).
- Leave an empty `## [Unreleased]` heading at the top for future entries (this is intentional — see the
  existing file; don't remove it even when empty).

## 5. Commit

- Commit exactly the version-bump files (`municipio-customisation.php`, `composer.json`, `CHANGELOG.md`,
  `README.md`) with a message like `Bump version to X.Y.Z`, briefly noting what's included. Don't sweep in
  unrelated untracked files.

## 6. Merge into main

- Fetch first: `git fetch origin`.
- Fast-forward local `main` to `origin/main` (`git checkout main && git merge --ff-only origin/main`) before
  merging the release branch in — local `main` can drift behind origin between releases.
- Merge the release branch (usually `dev`) into `main`. Prefer `--ff-only`; if it's not a fast-forward, stop
  and ask the user how they want to handle the divergence rather than forcing a merge commit.

## 7. Push, tag, and confirm before anything public

**Pushing branches/tags and creating a GitHub release are visible to the whole team — confirm with the user
before doing them, unless they've already explicitly asked for the release to be published in this
conversation.**

- Push `main` (and `dev`, to keep them aligned): `git push origin main dev`.
- Create an annotated tag matching the version number, **unprefixed** (e.g. `1.0.4`, not `v1.0.4` — this repo
  standardized on unprefixed tags, see the `1.0.2` changelog entry "use unprefixed release tags"):
  `git tag -a X.Y.Z <commit> -m "X.Y.Z"`.
- Push the tag: `git push origin X.Y.Z`.

## 8. Publish the GitHub release

- Title: the bare version number, matching the tag (e.g. `1.0.4`).
- Notes: the corresponding `CHANGELOG.md` section content (the `### Added`/`### Changed`/`### Fixed` bullets),
  not a regenerated summary — keep the changelog and the release notes worded identically. Append a
  `**Full Changelog**: https://github.com/Alingsas-Kommun/alingsas-municipio-customisation/compare/<prev>...<new>`
  line.
- Command: `gh release create X.Y.Z --title X.Y.Z --notes-file <file> --verify-tag`, adding `--latest` if this
  is the newest release.
- Verify with `gh release list --limit 5`.

## Notes

- `gh` is already authenticated in this environment; don't attempt `gh auth login`.
- There's no CI/build step required before tagging — `dist/` is git-ignored and rebuilt by the deploying
  environment, not shipped in the release commit.
- If the user only asks to "bump the version" without saying "release" or "publish," stop after step 5
  (commit) unless they also mention main/merge/tag/GitHub — don't assume they want a public release.
