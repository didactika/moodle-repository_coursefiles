# Installation and compatibility

Install local_multipicker first, activate an enabled profile and refresh source materials. Install this package at `repository/multipicker` under the exact core's application directory and enable the repository in Moodle administration.

| Tested target | Application path | Runtime |
|---|---|---|
| Moodle 4.5.13, build 20260810 | Core root | PHP 8.3.33 / PostgreSQL 17.10 |
| Moodle 5.3, build 20261005 | Checkout's `public/` application directory | PHP 8.3.33 / PostgreSQL 17.10 |

![Dependency boundary](diagrams/components.svg)

The repository requires a native course context, target editing/reuse permissions and current source eligibility. Private/system upload contexts are rejected. Revoked or newly hidden/restricted sources cannot be selected from a stale listing.

The current component rename retains configured repository type/instance IDs. Deploy both packages together using the local plugin's `docs/21-component-rename.md` procedure. The repository installation hook preserves its old settings and references; no repository business tables are created.

The embedded browser supplies responsive server-rendered cards, exact named-capture filters, separate selection review and bounded content pages. Native Folder content editing and registered activity filemanager/editor settings retain their own save/cancel and permission contracts. See [the native picker checks](06-native-picker-tests.md) and the local package's current validation matrix.

Native file-type compatibility and author policy are separate checks. Strict policy refuses copying through editors with unprotected later author changes; editable-with-provenance preserves the initial declaration. This repository owns no custom AMD/React bundle. Its local dependency supplies a native AMD chooser adapter built on each target core, with a separate Moodle 5 distribution. Support for other core releases is not inferred.

Maintenance branches for this delivery: `feat/multipicker-workflows-45` and `feat/multipicker-workflows-5`. The Moodle 5 artifact declares Moodle 5.3 as its minimum; use the Moodle 4.5 artifact for that release. Shared PHP, database, capability and draft contracts are aligned. This package requires local_multipicker version 2026101001 or later.
