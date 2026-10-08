# Repository architecture

repository_coursebridge extends Moodle's `repository` class and supports `FILE_INTERNAL`. local_coursebridge is its versioned dependency and sole business-data owner. Keep this package focused on UI/native-draft integration.

![Analysis responsibilities](diagrams/analysis-classes.svg)

![Design contracts](diagrams/design-classes.svg)

## Listing and filtering

`get_listing()` decodes navigation state, delegates authorized search to the local catalog, emits one node per content result and exposes matching occurrences in a child listing. A content cursor finishes a grouped hash before moving on, avoiding duplicate cards across pages. `print_search()` uses native HTML fields; `search()` delegates to the same server service.

## Selection and copying

![Draft sequence](diagrams/sequences/uc02-attach-material.svg)

`get_file_reference()` decodes profile/native locator, checks the current target course and source permissions, enforces current author coverage and returns a Moodle packed reference. `file_is_accessible()` uses that check rather than trusting a bare native file locator. Core session/source-key protections remain in place.

`copy_to_area()` revalidates immediately before copying, preserves initial source author/license, captures original attribution and calls the core draft copier. Caller-submitted author values do not override the source declaration. Full later native editor protection is not provided by the repository alone.

This plugin has no XMLDB installation tables. The Privacy provider declares linked core file/local-plugin ownership. Business-table access is confined to discovery/lookup contracts; copying never manipulates physical content storage.
