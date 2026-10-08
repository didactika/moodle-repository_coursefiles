# Contributing

Use Moodle coding conventions and English source/documentation with English/Spanish UI strings. local_coursebridge owns business decisions and metadata; this repository owns native listing, selection and draft integration.

Install both packages into a disposable Moodle checkout and initialize its isolated PHPUnit database. Run from the core root:

```sh
vendor/bin/phpunit --test-suffix _test.php APPLICATION/repository/coursebridge/tests
```

Check Moodle coding standards and relevant repository behavior on Moodle 4.5 and 5.3. Tests cover malformed source tokens, strict policy refusal, initial author preservation and the rendered native search controls.

Native API tests also cover save/reopen/rename, changed current metadata with an unchanged original declaration, policy changes after selection and attribution rollback after a core file-size rejection. These are service/native File API checks, not complete interactive browser acceptance.

Presentation uses `repository_coursebridge/search_form`; the native picker owns dialogs and submission. Theme overrides preserve its field names, labels and server-side authorization.

Documentation and diagrams describe implemented behavior and current limitations. Development instructions are in AGENTS. License: GPL v3 or later.
