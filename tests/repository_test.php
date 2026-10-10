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
 * @package repository_multipicker
 * @copyright 2026 Didactika.org
 * @author Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_multipicker;

/**
 * Exercises the real core repository/draft contract.
 *
 * @covers \repository_multipicker
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\repository_multipicker::class)]
final class repository_test extends \advanced_testcase {
    /**
     * A reviewed folder is transferred through the native repository as a bounded batch.
     */
    public function test_reviewed_batch_uses_native_draft_and_original_authorship(): void {
        global $PAGE, $USER, $DB;
        [$repository, $token, $target] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_multipicker');
        $PAGE->set_context(\context_course::instance($target->id));
        $locator = json_decode(base64_decode($token), true);
        $source = get_file_storage()->get_file_by_hash($locator['pathnamehash']);
        $sourcecontext = \context::instance_by_id($source->get_contextid());
        get_file_storage()->create_file_from_string(['contextid' => $sourcecontext->id,
            'component' => 'mod_folder', 'filearea' => 'content', 'itemid' => 0, 'filepath' => '/notes/',
            'filename' => 'second.txt', 'author' => 'Second author', 'license' => 'cc'], 'second');
        $control = new \local_multipicker\form\scoped_filemanager(
            'files',
            'Files',
            null,
            ['subdirs' => true, 'maxfiles' => 3, 'maxbytes' => 1024, 'accepted_types' => '*']
        );
        $scope = $control->open_scope(\context_course::instance($target->id));
        $review = \local_multipicker\local\selection\draft_scope::review(
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
        $this->assertSame(['Material author', 'Second author'], array_values(array_map(
            static fn($file) => $file->get_author(),
            $files
        )));
        $this->assertSame(2, $DB->count_records('local_multipicker_attribution'));
        $this->assertFalse($repository->file_is_accessible($batch));
    }

    /**
     * A skipped identical file at another path still returns a valid native response.
     */
    public function test_batch_with_only_content_skips_refreshes_existing_draft(): void {
        global $PAGE, $USER;
        [$repository, $token, $target] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_multipicker');
        $PAGE->set_context(\context_course::instance($target->id));
        $locator = json_decode(base64_decode($token), true);
        $source = get_file_storage()->get_file_by_hash($locator['pathnamehash']);
        $control = new \local_multipicker\form\scoped_filemanager(
            'files',
            'Files',
            null,
            ['subdirs' => true, 'maxfiles' => 1, 'accepted_types' => '*']
        );
        $scope = $control->open_scope(\context_course::instance($target->id));
        get_file_storage()->create_file_from_storedfile(['contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => (int)$control->getValue(),
            'filepath' => '/kept/', 'filename' => 'renamed.txt'], $source);
        $review = \local_multipicker\local\selection\draft_scope::review(
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
        $this->assertCount(1, get_file_storage()->get_area_files(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            (int)$control->getValue(),
            'id',
            false
        ));
    }

    /**
     * Invalid batch value types cannot bypass native source validation.
     */
    public function test_malformed_batch_is_refused(): void {
        [$repository] = $this->fixture();
        $this->assertFalse($repository->file_is_accessible(base64_encode(json_encode(
            ['batch' => [], 'digest' => []]
        ))));
    }

    /**
     * Organized picker helpers return a selectable, authorized native listing node.
     */
    public function test_listing_uses_authorized_occurrence_nodes(): void {
        [$repository, $token, $target] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_multipicker');
        $locator = json_decode(base64_decode($token), true);
        $file = get_file_storage()->get_file_by_hash($locator['pathnamehash']);
        $sourcecourse = \context::instance_by_id($file->get_contextid())->get_course_context();
        \local_multipicker\local\catalog\indexer::refresh($sourcecourse->instanceid);
        \local_multipicker\local\catalog\indexer::refresh($target->id);
        $listing = $repository->get_listing();
        $this->assertCount(1, $listing['list']);
        $node = $listing['list'][0];
        $this->assertSame('lesson.txt', $node['title']);
        $this->assertSame('Material author', $node['author']);
        $this->assertTrue($repository->file_is_accessible($node['source']));
    }

    /**
     * Native save keeps provenance; later native edits can change current metadata.
     */
    public function test_native_save_and_later_edit_preserve_original_provenance(): void {
        global $DB, $USER;
        [$repository, $token, $target] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_multipicker');
        $draftid = file_get_unused_draft_itemid();
        $reference = $repository->get_file_reference($token);
        $repository->copy_to_area($reference, [
            'itemid' => $draftid, 'filepath' => '/', 'filename' => 'lesson.txt',
        ]);
        $fs = get_file_storage();
        $draft = $fs->get_file(\context_user::instance($USER->id)->id, 'user', 'draft', $draftid, '/', 'lesson.txt');
        $draftsource = unserialize_object($draft->get_source());
        $this->assertIsObject($draftsource);
        $attributionuuid = json_decode($draftsource->source, true)['multipicker'];
        $folder = $this->getDataGenerator()->create_module('folder', ['course' => $target->id]);
        $context = \context_module::instance($folder->cmid);
        file_save_draft_area_files($draftid, $context->id, 'mod_folder', 'content', 0, ['subdirs' => true]);
        $saved = $fs->get_file($context->id, 'mod_folder', 'content', 0, '/', 'lesson.txt');
        $this->assertSame('Material author', $saved->get_author());
        $this->assertSame($attributionuuid, json_decode($saved->get_source(), true)['multipicker']);
        $editdraft = 0;
        file_prepare_draft_area($editdraft, $context->id, 'mod_folder', 'content', 0, ['subdirs' => true]);
        \repository::update_draftfile($editdraft, '/', 'lesson.txt', [
            'filename' => 'renamed-lesson.txt', 'author' => 'Later editor', 'license' => 'allrightsreserved',
        ]);
        file_save_draft_area_files($editdraft, $context->id, 'mod_folder', 'content', 0, ['subdirs' => true]);
        $saved = $fs->get_file($context->id, 'mod_folder', 'content', 0, '/', 'renamed-lesson.txt');
        $this->assertSame('Later editor', $saved->get_author());
        $this->assertSame('allrightsreserved', $saved->get_license());
        $attribution = $DB->get_record('local_multipicker_attribution', ['uuid' => $attributionuuid], '*', MUST_EXIST);
        $this->assertSame('Material author', $attribution->author);
        $this->assertSame('cc', $attribution->license);
        $this->assertSame($attributionuuid, json_decode($saved->get_source(), true)['multipicker']);
    }

    /**
     * A core file-size rejection must not leave a successful-copy provenance record.
     */
    public function test_failed_draft_copy_rolls_back_attribution(): void {
        global $DB, $USER;
        [$repository, $token] = $this->fixture();
        $this->preventResetByRollback();
        set_config('author_policy', 'editable_with_provenance', 'local_multipicker');
        $reference = $repository->get_file_reference($token);
        $draftid = file_get_unused_draft_itemid();
        $before = $DB->count_records('local_multipicker_attribution');
        try {
            $repository->copy_to_area($reference, [
                'itemid' => $draftid, 'filepath' => '/', 'filename' => 'lesson.txt',
            ], 1);
            $this->fail('Core must reject this file-size limit.');
        } catch (\file_exception $exception) {
            $this->assertSame('maxbytesfile', $exception->errorcode);
        }
        $this->assertSame($before, $DB->count_records('local_multipicker_attribution'));
        $this->assertFalse(get_file_storage()->get_file(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $draftid,
            '/',
            'lesson.txt'
        ));
    }

    /**
     * Policy changes after selection are checked again before draft creation.
     */
    public function test_policy_change_after_selection_blocks_copy(): void {
        global $DB;
        [$repository, $token] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_multipicker');
        $reference = $repository->get_file_reference($token);
        set_config('author_policy', 'preserve_locked', 'local_multipicker');
        try {
            $repository->copy_to_area($reference, [
                'itemid' => file_get_unused_draft_itemid(), 'filepath' => '/', 'filename' => 'lesson.txt',
            ]);
            $this->fail('An effective lock must refuse the copy.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('lockedunsupported', $exception->errorcode);
        }
        $this->assertSame(0, $DB->count_records('local_multipicker_attribution'));
    }

    /**
     * Create a native repository with one permitted source.
     *
     * @return array Repository, source token and target.
     */
    private function fixture(): array {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/repository/multipicker/lib.php');
        $config = json_decode(file_get_contents($CFG->dirroot . '/local/multipicker/examples/institutional-profile.json'), true);
        $profileid = \local_multipicker\local\profile\profiles::save(json_encode($config));
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $source = $this->getDataGenerator()->create_course(['idnumber' => $uuid . '||MATH||2025']);
        $target = $this->getDataGenerator()->create_course(['idnumber' => $uuid . '||MATH||2026']);
        $folder = $this->getDataGenerator()->create_module('folder', ['course' => $source->id]);
        $file = get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($folder->cmid)->id, 'component' => 'mod_folder',
            'filearea' => 'content', 'itemid' => 0, 'filepath' => '/', 'filename' => 'lesson.txt',
            'author' => 'Material author', 'license' => 'cc',
        ], 'lesson');
        $type = new \repository_type('multipicker');
        $typeid = $type->create(false);
        $instanceid = $DB->get_field('repository_instances', 'id', ['typeid' => $typeid]);
        $repository = new \repository_multipicker($instanceid, \context_course::instance($target->id));
        $token = base64_encode(json_encode(['profileid' => $profileid, 'pathnamehash' => $file->get_pathnamehash()]));
        return [$repository, $token, $target];
    }

    /**
     * Native author-lock coverage is never silently bypassed.
     */
    public function test_locked_source_token_is_refused(): void {
        [$repository, $token] = $this->fixture();
        $this->assertFalse($repository->file_is_accessible($token));
        $this->expectException(\moodle_exception::class);
        $repository->get_file_reference($token);
    }

    /**
     * Search controls remain labelled fields inside the native picker form.
     */
    public function test_search_template_labels_native_fields(): void {
        global $PAGE;
        [$repository, $token, $target] = $this->fixture();
        $PAGE->set_context(\context_course::instance($target->id));
        $PAGE->set_url('/course/view.php', ['id' => $target->id]);
        $html = $repository->print_search();
        $this->assertStringNotContainsString('<form', $html);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $controls = $xpath->query('//input | //select');
        $this->assertSame(6, $controls->length);
        $this->assertSame(1, $xpath->query('//button[@type="submit"]')->length);
        foreach ($controls as $control) {
            $id = $control->getAttribute('id');
            $this->assertSame(1, $xpath->query('//label[@for="' . $id . '"]')->length);
        }
    }

    /**
     * Copying ignores a spoofed importing-user author declaration.
     */
    public function test_draft_copy_preserves_selected_author(): void {
        global $USER;
        [$repository, $token] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_multipicker');
        $reference = $repository->get_file_reference($token);
        $draftid = file_get_unused_draft_itemid();
        $repository->copy_to_area($reference, [
            'contextid' => \context_user::instance($USER->id)->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $draftid, 'filepath' => '/', 'filename' => 'lesson.txt',
            'author' => 'Spoofed importing user', 'license' => 'allrightsreserved',
        ]);
        $draft = get_file_storage()->get_file(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $draftid,
            '/',
            'lesson.txt'
        );
        $this->assertSame('Material author', $draft->get_author());
        $this->assertSame('cc', $draft->get_license());
    }

    /**
     * Arbitrary native references cannot replace the authorized occurrence token.
     */
    public function test_malformed_source_is_rejected(): void {
        [$repository] = $this->fixture();
        $this->assertFalse($repository->file_is_accessible(base64_encode('{"profileid":1}')));
    }
}
