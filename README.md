<div align="center">

<img src="pix/icon.svg" width="96" alt="FileBank icon">

# FileBank for Moodle — Repository

*Select several course files and folders inside Moodle’s file picker*

[![Release](https://img.shields.io/github/v/release/didactika/moodle-repository_filebank?style=flat-square)](https://github.com/didactika/moodle-repository_filebank/releases)
[![Moodle](https://img.shields.io/badge/Moodle-4.0%E2%80%935.2-f98012?style=flat-square&logo=moodle&logoColor=white)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777bb4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL_v3-blue?style=flat-square)](LICENSE)
[![CI](https://github.com/didactika/moodle-repository_filebank/actions/workflows/ci.yml/badge.svg?branch=MOODLE_400_STABLE)](https://github.com/didactika/moodle-repository_filebank/actions/workflows/ci.yml)

[Overview](#overview) · [Installation](#installation) · [Usage](#usage) · [Configuration](#configuration) · [Troubleshooting](#troubleshooting)

</div>

FileBank (`repository_filebank`) exposes the FileBank catalog through Moodle’s native file picker, with recursive folder and file selection.

> [!IMPORTANT]
> Install both [FileBank local](https://github.com/didactika/moodle-local_filebank) and [FileBank repository](https://github.com/didactika/moodle-repository_filebank) from the same maintenance line. The standalone MultiPicker repository is a separate plugin.

## Overview

The repository component is the native picker entry for local_filebank. It uses the configured repository name, native Moodle icons and the owning form’s permissions and limits. The local companion provides the browser and transfer services; this repository does not duplicate them.

## Features

| Feature | Behavior |
|---|---|
| Related courses | Named regular-expression captures define course groups and up to nine configurable search filters. |
| Public material library | Folder, File, public Assignment attachments and other declared public providers; private user material remains excluded. |
| Multi-file selection | Grid, list and lazy recursive tree views share checks and unique-content counts. Existing destination content is excluded before pagination. |
| Native review | Resulting-file tree, folder removal checks and pencil rename dialogs before importing into the native draft. |
| Native limits | Accepted types, remaining file count, directory support and byte limits are enforced again at import. |

## Requirements

| Requirement | This distribution |
|---|---|
| Moodle | 4.0–5.2 (qualification matrix) |
| Maintenance line | MOODLE_400_STABLE |
| Release / maturity | 4.5.0 / stable |
| PHP and database | Moodle’s requirements for the selected release; local checks include Moodle 4.0/PHP 8.0 and Moodle 4.5/PHP 8.3 with PostgreSQL 17. |
| Companion | [local_filebank](https://github.com/didactika/moodle-local_filebank/tree/MOODLE_400_STABLE) from the same maintenance line. |

The shared AMD/Mustache distribution targets Moodle 4.0–5.2 with PHP 8.0 or newer, subject to each core release’s PHP requirements. Moodle 5.3 has a separate TypeScript/ESM distribution on main. The declared range is validated by CI; a supported range is not evidence that every matrix job has passed.

## Installation

Install the local companion first, then the repository component. Download the reviewed release ZIP and use Site administration → Plugins → Install plugins, or copy this repository into **repository/filebank** beneath the actual Moodle application directory. Moodle 5.3’s checked layout places that directory beneath public/.

Run the administrator upgrade screen or the native CLI upgrade:

```sh
php admin/cli/upgrade.php --non-interactive
```

Existing CourseBridge installations require a component rename: retain their records, policies and repository identities, migrate scheduled and queued task registrations, then remove the retired directories. Follow the detailed workspace rename procedure before upgrading an existing installation.

## Usage

1. Enable the repository in Site administration → Plugins → Repositories.
2. Set its visible name and configure related courses in the local companion.
3. Open Add files in an existing or new supported activity form and choose this repository.
4. Filter, expand folders or Select all. Review the resulting files and import them.
5. Save or cancel the native activity form; draft imports follow Moodle’s normal save/cancel behavior.

## Configuration

The repository instance name appears in both its sidebar entry and browser heading. Course relationships, public source areas, author policy and retention belong to the local companion. Unregistered controls retain native individual-file behavior.

Strict author protection refuses workflows where later native author changes cannot be prevented. Editable-with-provenance allows copying with the original declaration retained in the log. The original author and licence are preserved at copy time.

## Documentation and testing

Detailed explanations, the RUP UML catalogue, rendered diagrams and the exact validation matrix are maintained outside this plugin repository in the services workspace:

`projects/docs/in-progress/suite-filebank/moodle-repository_filebank/`

That local collection covers both maintenance lines and is not published online. This README, [CHANGELOG](CHANGELOG.md) and [LICENSE](LICENSE) remain in each distribution. The package ships English language strings only; Moodle language packs can provide translations.

Run the packaged PHPUnit and native Behat tests in a disposable Moodle checkout with both companions installed and independent test databases. Build frontend assets with the exact supported core tooling. Local validation distinguishes completed checks from pending remote pipelines; it does not claim untested runtime combinations.

## Security and privacy

Session-bound scopes, capabilities, live publication checks and native draft ownership are verified server-side. Private submissions, feedback and personal files are not public providers. Interactive selection is bounded to 200 source occurrences; oversized requests are refused rather than truncated. Migration destinations are native Folders; the plugin copies public files rather than recreating whole activities.

## Support and contributions

Report reproducible issues through [GitHub Issues](https://github.com/didactika/moodle-repository_filebank/issues), including the Moodle version, plugin release and steps without private data. Submit changes against the correct maintenance line and keep companion contracts aligned. Review [the changelog](CHANGELOG.md) and the documented test evidence before upgrading.

## License

Copyright © 2026 Didactika.org — Hector Arrechea <hectorlazaroarrechea@gmail.com>.

Licensed under the GNU General Public License v3 or later. See [LICENSE](LICENSE).

## Troubleshooting

| Problem | Check |
|---|---|
| No materials appear | Enable a matching profile, confirm source visibility and run catalog reconciliation. |
| Old component names remain after upgrading | Follow the component adoption procedure below before installing the standalone MultiPicker. |
| Import is unavailable | Check destination permissions, native file limits and unresolved review conflicts. |

## Existing MultiPicker installations

Back up the database and plugin directories. Place FileBank in `local/filebank` and `repository/filebank`, then inspect and apply the metadata adoption:

```bash
php local/filebank/cli/adopt_multipicker.php
php local/filebank/cli/adopt_multipicker.php --execute
# Move the retired local/multipicker and repository/multipicker directories outside Moodle.
php admin/cli/upgrade.php --non-interactive
```

The procedure preserves table rows and IDs, settings, permissions, repository instances and supported queued work. It does not copy or delete course file binaries. Finish adoption before installing the new independent `repository_multipicker`.
