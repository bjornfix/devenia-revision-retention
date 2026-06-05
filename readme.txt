=== Devenia Revision Retention ===
Contributors: devenia
Tags: revisions, cleanup, retention, database
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 0.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Keeps recent WordPress revisions plus older anchor revisions so the database stays controlled without losing useful history.

== Description ==

Devenia Revision Retention keeps useful WordPress revision history without allowing the database to grow without bounds.

The default policy keeps the latest 10 revisions for each supported post or page, plus one older anchor revision near 1, 2, 3, 4, and 10 weeks back. This keeps the common short rollback window and also preserves older reference points when recent revisions are not enough to reconstruct original content.

== Features ==

* Keeps the latest N revisions per parent post.
* Keeps configurable older anchor revisions by age.
* Uses WordPress native deletion APIs for revisions.
* Configurable post types, batch limits, and schedule interval.
* Clear status panel, manual dry-run, and protected manual cleanup from Tools > Revision Retention.
* Exposes MCP abilities for status and dry-run/run.

== Installation ==

1. Upload the plugin folder to wp-content/plugins.
2. Activate Devenia Revision Retention.
3. Go to Tools > Revision Retention.
4. Review the defaults and run a dry-run before manual cleanup.

== Changelog ==

= 0.1.4 =
* Fix release packaging and align WordPress requirement metadata with the Abilities API integration.

= 0.1.3 =
* Improve the admin screen with plain-language policy summary, status cards, readable last-run results, and confirmation before manual cleanup.

= 0.1.2 =
* Keep the plugin focused on its own revision retention policy.

= 0.1.1 =
* Add MCP abilities for status and dry-run/run.

= 0.1.0 =
* Initial release.
