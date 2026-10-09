# Source structure

Moodle loads the repository entry class from `lib.php`. Its required interface methods remain there. Discovery, authorization and copying still delegate business decisions to local_multipicker.

```text
repository/multipicker/
├── lib.php                             repository_multipicker entry class
├── classes/
│   ├── local/picker/filters.php        supported native request filters
│   ├── output/picker.php               authorized file nodes and core icons
│   └── privacy/provider.php            Moodle Privacy API provider
├── templates/local/picker/
│   └── search_form.mustache            native search fields
├── db/access.php                       repository capability
├── lang/                               English and Spanish labels
└── version.php                         component and local-plugin dependency
```

`repository_multipicker\\local\\picker\\filters` reads the supported controls. `repository_multipicker\\output\\picker` formats authorized occurrences supplied by the local catalog. These helpers do not grant source access.

There are no standalone page controllers in this plugin: Moodle owns the file picker dialog and save lifecycle. The template is `repository_multipicker/local/picker/search_form`; [theme overrides](04-presentation-and-theming.md) use that same relative path.

Replace the complete plugin package and deploy the matching local_multipicker version **2026100802**. The companion plugin owns schema upgrades, administration and course pages. This repository owns no business tables.

References: [Repository plugin entry points](https://moodledev.io/docs/4.5/apis/plugintypes/repository), [template subdirectories](https://moodledev.io/docs/4.5/guides/templates).

