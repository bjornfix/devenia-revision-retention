# Devenia Revision Retention

Keeps recent WordPress revisions plus older anchor revisions so the database stays controlled without losing useful history.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/devenia-revision-retention?display_name=tag)](https://github.com/bjornfix/devenia-revision-retention/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

- **Plugin page:** https://devenia.com/plugins/devenia-revision-retention/
- **Tested up to:** 7.0
- **Stable tag:** 0.1.5
- **Requires at least:** 6.9
- **Requires PHP:** 7.2
- **License:** GPLv2 or later

## About

Devenia Revision Retention is a small WordPress plugin for keeping useful revision history without letting old revisions grow forever.

The public plugin page is:

https://devenia.com/plugins/devenia-revision-retention/

## What It Does

The default policy:

- Keep the latest 10 revisions per supported parent post.
- Keep one anchor revision near 7, 14, 21, 28, and 70 days back.
- Process posts and pages by default.
- Use WordPress native revision deletion instead of broad SQL deletes.

## Control panel

Go to `Tools > Revision Retention`.

The screen shows:

- whether scheduled cleanup is enabled
- when the next scheduled run will happen
- how many revision records exist now
- the active keep policy
- the last run result
- dry-run and manual cleanup actions

Manual cleanup requires a confirmation checkbox. Dry-run is the safer first step and does not delete anything.

## MCP abilities

- `revision-retention/get-settings`
- `revision-retention/run`

## Admin

Settings live under `Tools > Revision Retention`.

The admin screen includes:

- status cards
- plain-language policy summary
- latest revision count
- anchor day list
- post type selection
- parent/delete batch limits
- interval minutes
- dry-run
- protected manual cleanup
- readable last-run results

## Installation

1. Download the latest release from GitHub.
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Activate `Devenia Revision Retention` in WordPress.
4. Go to `Tools > Revision Retention`.
5. Review the defaults and run a dry-run before manual cleanup.

## Release files

- `devenia-revision-retention.php`
- `readme.txt`
- `README.md`
- `CHANGELOG.md`
- `LICENSE`

## Links

- [Plugin Page](https://devenia.com/plugins/devenia-revision-retention/)
- [GitHub Releases](https://github.com/bjornfix/devenia-revision-retention/releases)
- [Devenia Plugins](https://devenia.com/plugins/)

## Changelog

### 0.1.5

- Harden the internal revision retention policy so dry-run and cleanup use the same explicit keep/delete decision.

### 0.1.4

- Fix release packaging and align WordPress requirement metadata with the Abilities API integration.

### 0.1.3

- Improve the admin screen with plain-language policy summary, status cards, readable last-run results, and confirmation before manual cleanup.

### 0.1.2

- Keep the plugin focused on its own revision retention policy.

### 0.1.1

- Add MCP abilities for status and dry-run/run.

### 0.1.0

- Initial release.
