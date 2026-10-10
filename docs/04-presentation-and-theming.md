# Native picker presentation

The Moodle core owns the picker dialog, file grid/list, selection controls and draft save. This plugin supplies readable list entries and a component template for its search fields.

`repository_multipicker/local/picker/search_form` contains labelled profile, filename, edition, course-shortname, activity and file-type controls and a native primary Search button. The core wraps the fields in its search form and handles submission and AJAX results. Exact MIME values are represented by readable common file-type choices; the unfiltered listing accepts every native file type permitted by the target control.

Native flex and spacing utilities keep the field grid separate from the picker's toolbar float rules. Fields appear in two columns at desktop widths and one on small screens, without plugin CSS or JavaScript. All six controls and the submit button follow their document order for keyboard navigation.


Override the template at:

```text
theme/mytheme/templates/repository_multipicker/local/picker/search_form.mustache
```

Keep field names `cbprofile`, `s`, `cbedition`, `cbshortname`, `cbmodule` and `cbmime` and a button with `type="submit"` when changing presentation. Do not add a nested form. Values are parsed on the server. Labels are associated with instance-specific IDs; dynamic values are escaped by Mustache.

The stable region hook is `data-region="multipicker-search"`. A theme changes the search appearance without altering eligibility, token validation or author policy. Picker assets and dialogs continue to use native Moodle presentation.

Reference: [Moodle template overrides](https://moodledev.io/docs/4.5/apis/plugintypes/theme).
Supported filemanagers display the local plugin's embedded visual browser inside the native picker. Its `local_multipicker/local/selection/browser` and `local_multipicker/local/selection/review` templates belong to that dependency and can be overridden by the active theme. The individual search disclosure explains this distinction; it never redirects to another Settings form. No custom JavaScript bundle is owned by this repository plugin: the local plugin supplies the tested native chooser adapter.

The scoped browser has automatic Fragment filters, functional Cards/List/Tree controls, folder navigation buttons and a separate tree-review page with Back. Its scoped CSS follows the active theme variables and releases parent modal styling when another repository is selected. The repository supplies pix/icon.svg for the native sidebar; navigation and layout controls use native core icons.

The embedded browser uses compact filters, themed chevrons, small folder-arrow buttons and file/folder badges. Clicking a card toggles its native checkbox; buttons, links and labels retain their own actions. Folder navigation places Back at the leading edge and source course/path information at the opposite edge. Selected materials appear on the separate review screen rather than below the result cards. These controls are provided by `local_multipicker`, so themes override that component's templates to change the batch interface.

The supplied pix/icon.svg matches local_multipicker's icon. Other controls use native core icons. The local browser renders compact white/native-host surfaces and skeleton result placeholders; custom themes override its Mustache/CSS through the ordinary plugin conventions. Moodle 5.3 native navigation remains in the separate local compatibility distribution.

Batch selection shares the local component's native Back button, core arrow icon and `multipicker-back` hook. Folder navigation, selection review, migration execution and profile history place that action on the left. Administration rows also share one responsive local editor: native fields remain within the available form column, with visible field labels at narrow widths. This repository does not duplicate the local component's styles or administration controls. See the dependency's [presentation contract](https://github.com/didactika/moodle-local_multipicker/blob/main/docs/11-presentation-and-theming.md) for theme overrides.

Native search fields include core help icons supplied by the local companion. Administration, public-material browsing and embedded selection reuse the local templates and theme variables; the repository does not duplicate administrative actions or migration logic. Review checkboxes and native file limits retain the same contracts on both maintenance lines.

Tree folders start collapsed and expand in place down to native file leaves. Each child page reuses local/selection/card through local/selection/tree_children, with branch-only placeholders and bounded pagination. The shared local destination_content service excludes bytes already in the current owning draft before result grouping or paging; the ordinary native fallback also supplies the current itemid. Review folder removal checks preserve the approved import action.
