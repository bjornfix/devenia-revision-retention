# Devenia Revision Retention

Keeps recent WordPress revisions plus older anchor revisions so the database stays controlled without losing useful history.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/devenia-revision-retention)](https://github.com/bjornfix/devenia-revision-retention/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net)

**Tested up to:** 7.0
**Stable tag:** 0.1.5
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## What It Does

Keeps recent WordPress revisions plus older anchor revisions so the database stays controlled without losing useful history.

This is a small WordPress maintenance plugin built for a specific operational problem. It stays focused so the behavior is easy to understand, verify, and keep enabled.

**Example:** "Protect this site from the maintenance issue this plugin handles." - Install the plugin, verify the expected behavior, and let it keep doing that one job.

## The Real Workflow

In practice, the useful path is simple:

1. install and activate the plugin
2. confirm the behavior on a staging or low-risk page
3. leave the plugin active when the behavior fits the site
4. check release notes before updating

The human's job is to decide whether the behavior fits the site.
The plugin's job is to apply that behavior consistently.

## Why This Feels Different

Most small WordPress maintenance problems get handled manually or with broad plugins that do more than needed.

This plugin is different because it keeps a narrow scope:

- one clear purpose
- predictable behavior
- normal WordPress installation and update flow
- public source and release history

That changes the experience from:

- `Remember to handle this manually every time`

to:

- `Install the focused plugin and verify the result`

## Before vs After

### Before

- the issue depends on manual attention
- behavior can drift between maintenance runs
- fixes are easy to forget when work is repetitive

### After

- the site has a focused plugin for the job
- behavior is consistent between maintenance runs
- releases and source are easy to inspect

## Who It Is For

This is a good fit for:

- WordPress site owners with the specific maintenance problem this plugin solves
- agencies maintaining many WordPress sites
- operators who prefer focused plugins over broad toolkits
- teams that want public source and release notes before installing a plugin

## Documentation

Start with the public plugin page:

- [Plugin Page](https://devenia.com/plugins/devenia-revision-retention/)

## Start Here

If you are new to the plugin, use this order:

1. Read the plugin page
2. Download the latest release
3. Install it on WordPress
4. Verify the expected behavior
5. Keep it active if the behavior matches the site

## Changelog

### 0.1.5

- Hardened the internal revision retention policy so dry-run and cleanup use the same explicit keep/delete decision.

### 0.1.4

- Fixed release packaging and aligned WordPress requirement metadata with the Abilities API integration.

### 0.1.3

- Improved the admin screen with plain-language policy summary, status cards, readable last-run results, and confirmation before manual cleanup.

### 0.1.2

- Kept the plugin focused on its own revision retention policy.

### 0.1.1

- Added MCP abilities for status and dry-run/run.

### 0.1.0

- Initial release.

## Contributing

PRs welcome. Keep changes focused on the plugin's stated WordPress maintenance behavior.

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com) - We've been doing SEO and web development since 1993.

## Links

- [Plugin Page](https://devenia.com/plugins/devenia-revision-retention/)
- [GitHub Releases](https://github.com/bjornfix/devenia-revision-retention/releases)
- [Devenia Plugins](https://devenia.com/plugins/)

## Star and Share

If this plugin helps solve a real WordPress maintenance problem, please:

- star the repo
- share it with people running WordPress sites
- point them to the plugin page so they can see what it does

Why do it?

Because practical WordPress maintenance tools are better when they are easy to find, easy to understand, and easy to verify before use.
