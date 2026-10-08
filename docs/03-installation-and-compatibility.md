# Installation and compatibility

Install local_coursebridge first, activate an enabled profile and refresh source materials. Install this package at `repository/coursebridge` under the exact core's application directory and enable the repository in Moodle administration.

| Tested target | Application path | Runtime |
|---|---|---|
| Moodle 4.5.13, build 20260810 | Core root | PHP 8.3.33 / PostgreSQL 17.10 |
| Moodle 5.3, build 20261005 | Checkout's `public/` application directory | PHP 8.3.33 / PostgreSQL 17.10 |

![Dependency boundary](diagrams/components.svg)

The repository requires a native course context, target editing/reuse permissions and current source eligibility. Private/system upload contexts are rejected. Revoked or newly hidden/restricted sources cannot be selected from a stale listing.

Checks on 2026-10-08 passed on both listed cores: four tests and thirteen assertions per core cover tokens, author policy, draft author preservation and labelled template controls. Moodle coding standards report zero errors and warnings. Firefox rendered the search template with each core's Boost stylesheet at 1366 px and 390 px without page-level overflow. These template fixture checks do not constitute full native picker session or accessibility acceptance.

Native file-type compatibility and author policy are separate checks. Strict policy refuses copying through editors with unprotected later author changes; editable-with-provenance preserves the initial declaration. There is no custom AMD/React bundle. Support for other core releases is not inferred.
