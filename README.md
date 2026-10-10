# MultiPicker — repository_multipicker

Materials from related courses inside Moodle's native file picker.

Install at `repository/multipicker`. This plugin requires **local_multipicker** and delegates grouping, access, publication and author policy to it. It owns no business tables or binary store.

![Picker responsibilities](docs/diagrams/components.svg)

## Install and use

Install local_multipicker first, install this package through Moodle's plugin upgrade procedure and enable **Materials from related courses** under Site administration → Plugins → Repositories → Manage repositories.

Open the native **Add files** action and choose the configured MultiPicker repository name. Supported Folder/File filemanagers and native Tiny Link editors expose visual multiple selection inside the picker; unregistered controls retain their ordinary individual selection. Filter by profile, file name, configured capture fields, course shortname, source activity and file type. Identical content appears once with its permitted origins. Choose an exact source declaration to copy into the native draft.

The target context comes from the repository instance. System/private-user upload contexts are rejected. The native control retains its allowed file types, maximum file count and size limits.

## Documentation

- [Picker workflow](docs/01-picker-workflow.md).
- [Authorization and draft architecture](docs/02-architecture.md).
- [Installation and exact core targets](docs/03-installation-and-compatibility.md).
- [Theme-overridable search fields](docs/04-presentation-and-theming.md).
- [Source folders and native entry contracts](docs/05-source-structure.md).
- [Native browser acceptance checks](docs/06-native-picker-tests.md).
- [UC01 search](docs/use-cases/uc01-search-materials.md) and [UC02 attach](docs/use-cases/uc02-attach-material.md).
- [UML sources and previews](docs/diagrams/README.md).
- [Development checks](CONTRIBUTING.md).
- [GitHub workflows and package dependency](docs/07-development-and-packaging.md).

Version: **alpha**. This maintenance distribution targets Moodle 5.3; Moodle 4.5 uses the separate Moodle 4.5 distribution. Initial source author/licence is preserved; strict copying is refused because later native author edits cannot be prevented by this package. Editable-with-provenance policy allows later native changes.

The native picker supplies the dialog and file presentation; the plugin's search fields use a theme-overridable Mustache template. The local dependency supplies the native chooser AMD adapter; this repository owns no frontend bundle. License: GPL v3 or later.

Folder checks select actual descendant files recursively. The local browser provides partial parent checks, grid/list/lazy recursive tree layouts, fixed filters/actions, result placeholders and resulting-file tree review with native pencil rename dialogs. Review folders select their descendant removal checks; Remove selected appears beside Import materials only while files are checked. Both plugins use the same supplied plugin icon; native control icons come from Moodle. Minimum local dependency: 2026101001. The validation record uses local_multipicker 2026101001.

The current actor's destination draft excludes already-used content from selection, independently of filename or path. Tree folders start collapsed and load bounded child pages in place. Review removal checks do not invalidate import approval.
