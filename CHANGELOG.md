# Changelog
This file lists the changes shipped in each release, in English (this file) and German (`CHANGELOG_de-DE.md`).

The format is dictated by shopware-cli's changelog parser (used for the Shopware Store upload): one `#`-level
heading per version, matching the version in `composer.json` exactly, followed by a flat list of changes.

# Unreleased

- Fixed: the health report command no longer crashes.
- Fixed: the scheduled task check ignores inactive and skipped tasks and only reports tasks that are really overdue.
- Removed the unfinished sitemap check, which made every report fail.
- The health report always contains a check list, also when no checks are registered.
