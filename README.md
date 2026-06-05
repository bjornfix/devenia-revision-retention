# Devenia Revision Retention

Keep useful WordPress revision history without letting old revisions grow forever.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/devenia-revision-retention)](https://github.com/bjornfix/devenia-revision-retention/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net)

**Tested up to:** 7.0
**Stable tag:** 0.1.5
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## What It Does

This plugin keeps recent WordPress revisions plus older anchor revisions, so active sites can keep useful rollback points without keeping every old revision forever.

By default it keeps the latest 10 revisions for each supported post or page, plus one older revision near 1, 2, 3, 4, and 10 weeks back.

**Example:** "Keep recent edits available, but do not let five years of revision history accumulate without a policy." - The plugin runs a dry-run, shows what would be removed, and can then clean up revisions outside the keep policy.

## The Real Workflow

In practice, revision cleanup should be predictable and reviewable.

The normal pattern is:

1. install and activate the plugin
2. open **Tools > Revision Retention**
3. review the current keep policy
4. run a dry-run first
5. run cleanup only when the dry-run result looks right
6. leave scheduled cleanup enabled if the policy fits the site

The human's job is to choose the policy.
The plugin's job is to apply it consistently.

## Why This Feels Different

Most revision cleanup is either too blunt or too manual.

This plugin is different because it keeps a useful shape of history:

- recent revisions stay available for short rollback windows
- older anchor revisions stay available for context
- dry-run and cleanup use the same explicit keep/delete decision
- cleanup uses WordPress native revision deletion APIs

That changes the experience from:

- `Delete old revisions and hope the useful ones remain`

to:

- `Keep recent history plus older checkpoints, then remove the rest`

## Before vs After

### Before

- every revision can stay around indefinitely
- cleanup decisions are easy to postpone
- older rollback points may be lost if cleanup is too aggressive
- dry-run and cleanup behavior can be hard to reason about

### After

- the site keeps a clear recent rollback window
- older anchor revisions remain available
- cleanup can be reviewed before revisions are removed
- the keep/delete policy is explicit and consistent

## Who It Is For

This is a good fit for:

- WordPress sites with frequent content edits
- editorial teams that need rollback points without unlimited revision growth
- agencies maintaining sites where revision volume should stay predictable
- site owners who want a dry-run before revision cleanup

It is especially useful when revisions are valuable, but keeping every revision forever is not.

## Documentation

Start with the public plugin page:

- [Devenia Revision Retention](https://devenia.com/plugins/devenia-revision-retention/)

The admin screen is available at:

- **Tools > Revision Retention**

## Start Here

If you are new to the plugin, use this order:

1. Install **Devenia Revision Retention**
2. Open **Tools > Revision Retention**
3. Check the default policy
4. Run a dry-run
5. Review the last-run result
6. Enable scheduled cleanup if the policy is right for the site

If the site has unusual editorial requirements, adjust the latest revision count, anchor days, post types, and batch limits before running cleanup.

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

PRs welcome. Keep the plugin focused on WordPress revision retention and predictable cleanup behavior.

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com) - We've been doing SEO and web development since 1993.

## Links

- [Plugin Page](https://devenia.com/plugins/devenia-revision-retention/)
- [GitHub Releases](https://github.com/bjornfix/devenia-revision-retention/releases)
- [Devenia Plugins](https://devenia.com/plugins/)

## Star and Share

If this plugin helps keep WordPress revision history useful and controlled, please:

- star the repo
- share it with people running WordPress sites
- point them to the plugin page so they can see what it does

Why do it?

Because practical WordPress maintenance tools are better when they are easy to find, easy to understand, and easy to verify before use.
