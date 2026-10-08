# Native picker presentation

The Moodle core owns the picker dialog, file grid/list, selection controls and draft save. This plugin supplies readable list entries and a component template for its search fields.

`repository_coursebridge/local/picker/search_form` contains labelled profile, filename, edition, course-shortname, activity and file-type controls and a native primary Search button. The core wraps the fields in its search form and handles submission and AJAX results. Exact MIME values are represented by readable common file-type choices; the unfiltered listing accepts every native file type permitted by the target control.

Native flex and spacing utilities keep the field grid separate from the picker's toolbar float rules. Fields appear in two columns at desktop widths and one on small screens, without plugin CSS or JavaScript. All six controls and the submit button follow their document order for keyboard navigation.

Override the template at:

```text
theme/mytheme/templates/repository_coursebridge/local/picker/search_form.mustache
```

Keep field names `cbprofile`, `s`, `cbedition`, `cbshortname`, `cbmodule` and `cbmime` and a button with `type="submit"` when changing presentation. Do not add a nested form. Values are parsed on the server. Labels are associated with instance-specific IDs; dynamic values are escaped by Mustache.

The stable region hook is `data-region="coursebridge-search"`. A theme changes the search appearance without altering eligibility, token validation or author policy. Picker assets and dialogs continue to use native Moodle presentation.

Reference: [Moodle template overrides](https://moodledev.io/docs/4.5/apis/plugintypes/theme).
