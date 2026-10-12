<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Native repository authorization and draft metadata tests.
 *
 * @package repository_filebank
 * @copyright 2026 Didactika.org
 * @author Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_filebank;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/repository_fixture.php');

/**
 * Focused native repository regression contracts.
 *
 * @covers \repository_filebank
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\repository_filebank::class)]
final class repository_test extends repository_fixture {
    /**
     * A reviewed folder is transferred through the native repository as a bounded batch.
     */
    public function test_reviewed_batch_uses_native_draft_and_original_authorship(): void {
        global $PAGE, $USER, $DB;
        [$repository, $token, $target] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_filebank');
        $PAGE->set_context(\context_course::instance($target->id));
        $locator = json_decode(base64_decode($token), true);
        $source = get_file_storage()->get_file_by_hash($locator['pathnamehash']);
        $sourcecontext = \context::instance_by_id($source->get_contextid());
        get_file_storage()->create_file_from_string(
            ['contextid' => $sourcecontext->id,
                'component' => 'mod_folder', 'filearea' => 'content', 'itemid' => 0, 'filepath' => '/notes/',
                'filename' => 'second.txt', 'author' => 'Second author', 'license' => 'cc'],
            'second'
        );
        $control = new \local_filebank\form\scoped_filemanager(
            'files',
            'Files',
            null,
            ['subdirs' => true, 'maxfiles' => 3, 'maxbytes' => 1024, 'accepted_types' => '*']
        );
        $scope = $control->open_scope(\context_course::instance($target->id));
        $review = \local_filebank\local\selection\draft_state::review(
            $scope,
            $locator['profileid'],
            [['cmid' => $sourcecontext->instanceid, 'filepath' => '/']],
            sesskey()
        );
        $batch = base64_encode(json_encode(['batch' => $scope, 'digest' => $review['digest']]));
        $reference = $repository->get_file_reference($batch);
        $result = $repository->copy_to_area($reference, ['itemid' => $control->getValue()]);
        $this->assertSame((int)$control->getValue(), $result['id']);
        $files = get_file_storage()->get_area_files(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            (int)$control->getValue(),
            'filepath, filename',
            false
        );
        $this->assertCount(2, $files);
        $this->assertSame(
            ['Material author', 'Second author'],
            array_values(
                array_map(
                    static fn($file) => $file->get_author(),
                    $files
                )
            )
        );
        $this->assertSame(2, $DB->count_records('local_filebank_author'));
        $this->assertFalse($repository->file_is_accessible($batch));
    }

    /**
     * A skipped identical file at another path still returns a valid native response.
     */
    public function test_batch_with_only_content_skips_refreshes_existing_draft(): void {
        global $PAGE, $USER;
        [$repository, $token, $target] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_filebank');
        $PAGE->set_context(\context_course::instance($target->id));
        $locator = json_decode(base64_decode($token), true);
        $source = get_file_storage()->get_file_by_hash($locator['pathnamehash']);
        $control = new \local_filebank\form\scoped_filemanager(
            'files',
            'Files',
            null,
            ['subdirs' => true, 'maxfiles' => 1, 'accepted_types' => '*']
        );
        $scope = $control->open_scope(\context_course::instance($target->id));
        get_file_storage()->create_file_from_storedfile(
            ['contextid' => \context_user::instance($USER->id)->id,
                'component' => 'user', 'filearea' => 'draft', 'itemid' => (int)$control->getValue(),
                'filepath' => '/kept/', 'filename' => 'renamed.txt'],
            $source
        );
        $review = \local_filebank\local\selection\draft_state::review(
            $scope,
            $locator['profileid'],
            [['pathnamehash' => $source->get_pathnamehash()]],
            sesskey()
        );
        $this->assertSame('skip_content', $review['operations'][0]['decision']);
        $batch = base64_encode(json_encode(['batch' => $scope, 'digest' => $review['digest']]));
        $reference = $repository->get_file_reference($batch);
        $result = $repository->copy_to_area($reference, ['itemid' => $control->getValue()]);
        $this->assertSame('renamed.txt', $result['file']);
        $this->assertCount(
            1,
            get_file_storage()->get_area_files(
                \context_user::instance($USER->id)->id,
                'user',
                'draft',
                (int)$control->getValue(),
                'id',
                false
            )
        );
    }

    /**
     * Invalid batch value types cannot bypass native source validation.
     */
    public function test_malformed_batch_is_refused(): void {
        [$repository] = $this->fixture();
        $this->assertFalse(
            $repository->file_is_accessible(
                base64_encode(
                    json_encode(
                        ['batch' => [], 'digest' => []]
                    )
                )
            )
        );
    }
}
