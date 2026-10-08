# Installation and compatibility

Install local_coursebridge first, activate an enabled profile and refresh source materials. Install this package at `repository/coursebridge` under the exact core's application directory and enable the repository in Moodle administration.

| Tested target | Application path | Runtime |
|---|---|---|
| Moodle 4.5.13, build 20260810 | Core root | PHP 8.3.33 / PostgreSQL 17.10 |
| Moodle 5.3, build 20261005 | Checkout's `public/` application directory | PHP 8.3.33 / PostgreSQL 17.10 |

![Dependency boundary](diagrams/components.svg)

The repository requires a native course context, target editing/reuse permissions and current source eligibility. Private/system upload contexts are rejected. Revoked or newly hidden/restricted sources cannot be selected from a stale listing.

Checks on 2026-10-09 passed on both listed cores: eight PHPUnit tests and 31 assertions per core cover authorized listings, tokens, author policy, draft/native save behavior and labelled template controls with a submit button. Three native Firefox/Behat scenarios and 52 steps per core cover grouped editions, submitted filters, folder save preserving existing files and keyboard search. Moodle's axe check passes for the search region. See the [reproducible browser checks and their scope](06-native-picker-tests.md). Moodle coding standards report zero errors and warnings.

Separate visual checks open the real modal with each core's Boost stylesheet at 1366 px and inside a 390 px viewport without page-level horizontal overflow. The search template supplies the submit button; Moodle owns the surrounding form and AJAX handler. After updating template or language files, purge Moodle caches so existing installations load them.

Native file-type compatibility and author policy are separate checks. Strict policy refuses copying through editors with unprotected later author changes; editable-with-provenance preserves the initial declaration. There is no custom AMD/React bundle. Support for other core releases is not inferred.
