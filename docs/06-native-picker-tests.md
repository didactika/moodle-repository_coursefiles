# Native picker acceptance

The packaged [Behat feature](../tests/behat/native_picker.feature) runs inside Moodle's isolated acceptance site with Firefox and Boost. Its fixture uses the installed local plugin and native course, folder, resource, enrolment and File APIs; it does not mock Repository AJAX.

![Search through the native picker](diagrams/sequences/uc01-search-materials.svg)

The fixture teacher is enrolled only in the destination course. Two earlier editions explicitly publish identical lesson content; a hidden folder is excluded. The destination already contains a file. The fixture opts into editable-with-provenance only for these tests; production's strict default is unchanged.

| Scenario | Verified behavior |
|---|---|
| Grouped origins | Identical bytes appear once; expanding the group identifies both course shortnames. Hidden material does not appear. |
| Filter and save | Filename, edition, exact shortname, module and MIME filters reach the native AJAX handler. Selecting the result and saving the folder preserves the existing file. |
| Keyboard search | Tab reaches all six controls and Search in order. Enter on Search submits and filters the result. |

All scenarios run Moodle's axe accessibility check on `.coursebridge-search`, including labels, controls and the Search button. This automated region check is not a complete site or screen-reader certification.

On 2026-10-09, all **3 scenarios / 52 steps** passed on both Moodle 4.5.13 (build 20260810) and Moodle 5.3 (build 20261005), with PHP 8.3.33 and PostgreSQL 17.10. The repository PHPUnit suite separately passed **8 tests / 31 assertions** on each core.

## Run the packaged checks

Use a disposable Moodle checkout with its Behat database prefix, dataroot and URL separate from the normal site. Install both CourseBridge packages and initialize the native test environment from the core checkout root:

```sh
# Moodle 4.5.
php admin/tool/behat/cli/init.php

# Moodle 5.3.
php public/admin/tool/behat/cli/init.php

# Use the configuration path printed by init.php.
vendor/bin/behat --config /path/to/behat.yml --tags=@repository_coursebridge
```

Moodle's generic component generator step loads `tests/generator/behat_repository_coursebridge_generator.php` and `tests/generator/lib.php`. These fixtures belong to the test environment and are not administration actions.

Separate visual checks opened the real native modal at desktop width 1366 px and inside a same-origin 390 px viewport on each core. The search fields fit their available width with no page-level horizontal overflow. These checks use Firefox on Linux; other browsers and themes are outside this evidence.
