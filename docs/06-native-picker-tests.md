# Native picker acceptance

The packaged [Behat feature](../tests/behat/native_picker.feature) runs inside Moodle's isolated acceptance site with Firefox and Boost. Its fixture uses native courses, modules, enrolment, File APIs and real Repository AJAX. The local dependency supplies the supported filemanager binding and embedded chooser.

![Search through the native picker](diagrams/sequences/uc01-search-materials.svg)

The teacher is enrolled only in the destination course. Earlier editions explicitly publish materials; hidden material is excluded. The destination already contains a file. Tests explicitly opt into editable-with-provenance while retaining the strict package default.

| Scenario | Verified behavior |
|---|---|
| Unique content and hidden sources | Filename search presents one lesson file across two editions and excludes hidden activity/files. |
| Combined filters and save | Filename, edition, exact shortname, activity type and MIME filters use the embedded native form; source author is reviewed; native save preserves the existing destination file. |
| Retained selection | A selected source folder remains selected after searching for an individual resource; review expands both. |

The repository feature passes **3 scenarios / 37 steps** on each exact required core. The local dependency's 32 scenarios cover configured repository naming, all-result selection, continued typing during pending result refresh, stable modal dimensions, whole-card/recursive selection, retained card nodes and scroll position, native pencil rename dialogs, separate folder-tree review, restricted Folder content permission, public Assignment handouts and Tiny multi-link save/reopen. Review-row selection, bounded filters and selection-time native file limits are also covered. Administration checks cover profile version comparison/restoration, empty guided profiles in Spanish, aligned native inputs, native document upload, blocked migration editing, migration exclusions across searches, selection-aware labelled bulk icons, in-place state changes with retained checks/toasts, direct Settings access and native report navigation. Grid/list/lazy recursive-tree views keep the same material nodes; initially collapsed folders load nested folder/file pages in place, and review folder checks preserve import approval. Native Save/Edit reopening excludes already-imported bytes after renaming, including identical earlier-edition occurrences and exhausted folders; help icons open core popovers and All is excluded from the filter count. Service suites also check undeclared/private areas, activated-profile catalogue refresh and unrelated fragment contexts.

The [local validation record](https://github.com/didactika/moodle-local_multipicker/blob/main/docs/07-validation-and-decisions.md) gives the exact core/runtime matrix: local 2026101001 and repository 2026101000, 141 PHPUnit tests per core, and 35 native scenarios / 514 steps in completed batches on each required target. Desktop/mobile inspection and explicit labels are useful evidence, not a complete accessibility certification.

## Run checks

Use a disposable Moodle checkout with independent Behat database/dataroot/URL, install both packages and initialize from the core checkout root:

```sh
# Moodle 4.5.
php admin/tool/behat/cli/init.php

# Moodle 5.3.
php public/admin/tool/behat/cli/init.php

# Use the configuration path printed by init.php.
php vendor/bin/behat --config /path/to/behat.yml --tags=@repository_multipicker
```

The local dependency provides the shared embedded-browser Behat context. Both component generators are isolated test fixtures, not administration actions. Source resource fixtures remove generated sample files before adding their declared materials.
