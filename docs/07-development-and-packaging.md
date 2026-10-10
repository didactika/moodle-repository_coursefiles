# Development and packaging

This package uses the workflows supplied by [Didactika's Moodle plugin template](https://github.com/didactika/template-moodle-plugin), adapted for separate Moodle 4.5 and Moodle 5.3 distributions. Each distribution declares only its own supported core range in version.php.

CI installs didactika/moodle-local_multipicker before this repository plugin. Tests on support/moodle-5 select that local maintenance branch; main and support/moodle-45 select the Moodle 4.5 branch. Pull-request runs select the dependency according to the destination branch.

Release publication is manual. GitHub creation and pushes do not automatically create a release. Distinct maintenance lines must use distinct release numbers. GitHub workflows still require an actual remote run; the [native validation record](06-native-picker-tests.md) describes the local Docker checks.

The repository relies on the local plugin's core-built adapter and does not duplicate its frontend. See [the local build contract](https://github.com/didactika/moodle-local_multipicker/blob/main/docs/24-development-and-packaging.md). Install this package in repository/multipicker and install its dependency in local/multipicker.

Native repository coverage passes on both exact targets: three scenarios and 37 steps per target, including public-source filtering, deduplication and native import/save. The shared local companion also passes its administration, pagination and picker/editor batches; the full current record is maintained in its validation documentation.
