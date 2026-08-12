# Release Notes

## 1.0.0-beta.2 — August 13, 2026

This beta improves the add-on's Marketplace presentation and adds automated
release validation. The notification features are unchanged from beta.1.

### Added

- Practical Marketplace use cases for policy acknowledgements, planned
  maintenance, content freezes, editorial workflow changes, incidents, and
  team onboarding.
- GitHub Actions validation for the complete application and Playwright browser
  test suites.
- Versioned release notes for Marketplace and GitHub releases.

### Changed

- Moved installation, configuration, workflow, persistence, reminder, and
  retention guidance from `README.md` to `DOCUMENTATION.md`.
- Refocused `README.md` on the add-on's value, features, and common use cases.
- Updated documentation assertions to validate `DOCUMENTATION.md` after the
  split.

### Compatibility

- Statamic 6
- PHP 8.3 or newer
- Laravel 12 or 13
- Flat-file and database-backed Statamic installations

**Full changelog:**
[v1.0.0-beta.1...v1.0.0-beta.2](https://github.com/1stevengrant/cp-notifications/compare/v1.0.0-beta.1...v1.0.0-beta.2)
