# Changelog

## [5.3.0] - 2026-10-10

- Native React/TypeScript view selector, core auto-initialization and ESM assets isolated from the Moodle 4.5 line.

- English-only source language strings; detailed documentation and UML moved to the central services workspace.

- Stable Moodle 5.3 distribution with Didactika copyright, author metadata and GPLv3 LICENSE.
- Correct Back navigation after changing layouts and compact lazy trees without broken grid grouping.
- Canonical selection counts overlapping content once across editions; existing draft content is excluded before browsing.
- Migration correspondence rows select a supported public source activity type and copy its authorized files into Folder destinations.
- One visible Local plugins entry, table-head profile selection and responsive correspondence controls.

- Native Moodle repository integration on separate Moodle 4.5 and 5.3 distributions.
- Exclude content already in the native destination draft, independently of filename/path, in scoped and ordinary picker search. Minimum local dependency: 2026101002.
- Shared initially collapsed recursive folder/file tree and review removal checks that preserve import approval.
- The configured repository name is shared by the sidebar and embedded chooser heading.
- Authorized materials and grouped origins from declared public course activity areas; private submissions remain excluded.
- Shared local chooser with grid/list/course-tree layouts, recursive multi-selection, native control limits, stable filtering and folder-tree review.
- Native help and core action icons; both plugin components use the supplied plugin icon.
- The local companion owns profiles, version history, migrations and component styles. This repository owns no business tables or frontend bundle.
