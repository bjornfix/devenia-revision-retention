# Devenia Revision Retention

Keeps recent WordPress revisions plus older anchor revisions so the database stays controlled without losing useful history.

Default policy:

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

## Release files

- `devenia-revision-retention.php`
- `readme.txt`
- `README.md`
- `CHANGELOG.md`
- `LICENSE`

## Changelog

### 0.1.3

- Improve the admin screen with plain-language policy summary, status cards, readable last-run results, and confirmation before manual cleanup.

### 0.1.2

- Keep the plugin focused on its own revision retention policy.

### 0.1.1

- Add MCP abilities for status and dry-run/run.

### 0.1.0

- Initial release.
