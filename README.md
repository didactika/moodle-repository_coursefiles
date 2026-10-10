# MultiPicker repository (repository_multipicker)

Select multiple files and recursive folders from a shared public course-material library inside Moodle’s native file picker.

[![Moodle Plugin CI](https://github.com/didactika/moodle-repository_multipicker/actions/workflows/ci.yml/badge.svg?branch=MOODLE_405_STABLE)](https://github.com/didactika/moodle-repository_multipicker/actions/workflows/ci.yml)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)

## Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Documentation and testing](#documentation-and-testing)
- [Security and privacy](#security-and-privacy)
- [Support and contributions](#support-and-contributions)
- [License](#license)

## Overview

The repository component is the native picker entry for local_multipicker. It uses the configured repository name, native Moodle icons and the owning form’s permissions and limits. The local companion provides the browser and transfer services; this repository does not duplicate them.

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
| Moodle | 4.5 only |
| Maintenance line | MOODLE_405_STABLE |
| Release / maturity | 4.5.0 / stable |
| PHP and database | Moodle’s requirements for the selected release; completed local checks use PHP 8.3 and PostgreSQL 17. |
| Companion | [local_multipicker](https://github.com/didactika/moodle-local_multipicker/tree/MOODLE_405_STABLE) from the same maintenance line. |

Moodle 4.5 and 5.3 have separate distributions. Other 5.x versions are not declared supported. This line retains the Moodle 4.5 AMD/Mustache implementation.

## Installation

Install the local companion first, then the repository component. Download the reviewed release ZIP and use Site administration → Plugins → Install plugins, or copy this repository into **repository/multipicker** beneath the actual Moodle application directory. Moodle 5.3’s checked layout places that directory beneath public/.

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

`projects/docs/in-progress/suite-coursebridge/moodle-repository_multipicker/`

That local collection covers both maintenance lines and is not published online. This README, [CHANGELOG](CHANGELOG.md) and [LICENSE](LICENSE) remain in each distribution. The package ships English language strings only; Moodle language packs can provide translations.

Run the packaged PHPUnit and native Behat tests in a disposable Moodle checkout with both companions installed and independent test databases. Build frontend assets with the exact supported core tooling. Local validation distinguishes completed checks from pending remote pipelines; it does not claim untested runtime combinations.

## Security and privacy

Session-bound scopes, capabilities, live publication checks and native draft ownership are verified server-side. Private submissions, feedback and personal files are not public providers. Interactive selection is bounded to 200 source occurrences; oversized requests are refused rather than truncated. Migration destinations are native Folders; the plugin copies public files rather than recreating whole activities.

## Support and contributions

Report reproducible issues through [GitHub Issues](https://github.com/didactika/moodle-repository_multipicker/issues), including the Moodle version, plugin release and steps without private data. Submit changes against the correct maintenance line and keep companion contracts aligned. Review [the changelog](CHANGELOG.md) and the documented test evidence before upgrading.

## License

Copyright © 2026 Didactika.org — Hector Arrechea <hectorlazaroarrechea@gmail.com>.

Licensed under the GNU General Public License v3 or later. See [LICENSE](LICENSE).
