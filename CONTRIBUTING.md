# Contributing

Use Moodle coding conventions and English source/documentation with English/Spanish UI strings. local_multipicker owns business decisions and metadata; this repository owns native listing, selection and draft integration.

Install both packages into a disposable Moodle checkout and initialize its isolated PHPUnit database. Run from the core root:

```sh
vendor/bin/phpunit --test-suffix _test.php APPLICATION/repository/multipicker/tests
```

Check Moodle coding standards and relevant repository behavior on Moodle 4.5 and 5.3. Tests cover malformed source tokens, strict policy refusal, initial author preservation and the rendered native search controls.

Native API tests also cover save/reopen/rename, changed current metadata with an unchanged original declaration, policy changes after selection and attribution rollback after a core file-size rejection. These are service/native File API checks, not complete interactive browser acceptance.

The [native browser scenarios](docs/06-native-picker-tests.md) additionally exercise published multi-edition content, grouping, filters, native draft selection/save, keyboard focus and axe checks of the search region. They use Moodle's isolated Behat site and real Firefox.

Presentation uses `repository_multipicker/local/picker/search_form`; the plugin supplies fields and a submit button, while the native picker owns dialogs and submission handling. Theme overrides preserve its field names, labels and server-side authorization.

Documentation and diagrams describe implemented behavior and current limitations. Development instructions are in AGENTS. License: GPL v3 or later.
