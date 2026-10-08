# Native picker presentation

The Moodle core owns the picker dialog, file grid/list, selection controls and draft save. This plugin supplies readable list entries and a component template for its search fields.

`repository_coursebridge/search_form` contains labelled profile, filename, edition, course-shortname, activity and file-type controls. It uses native responsive form classes. The core wraps the fields in its search form and owns submission. Exact MIME values are represented by readable common file-type choices; the unfiltered listing accepts every native file type permitted by the target control.

Override the template at:

```text
theme/mytheme/templates/repository_coursebridge/search_form.mustache
```

Keep field names `cbprofile`, `s`, `cbedition`, `cbshortname`, `cbmodule` and `cbmime` when changing presentation. They are parsed on the server. Labels are associated with instance-specific IDs; dynamic values are escaped by Mustache.

The stable region hook is `data-region="coursebridge-search"`. A theme changes the search appearance without altering eligibility, token validation or author policy. Picker assets and dialogs continue to use native Moodle presentation.

Reference: [Moodle template overrides](https://moodledev.io/docs/4.5/apis/plugintypes/theme).
