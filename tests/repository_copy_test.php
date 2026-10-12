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
final class repository_copy_test extends repository_fixture {
    /**
     * Native save keeps provenance; later native edits can change current metadata.
     */
    public function test_native_save_and_later_edit_preserve_original_provenance(): void {
        global $DB, $USER;
        [$repository, $token, $target] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_filebank');
        $draftid = file_get_unused_draft_itemid();
        $reference = $repository->get_file_reference($token);
        $repository->copy_to_area(
            $reference,
            [
                'itemid' => $draftid, 'filepath' => '/', 'filename' => 'lesson.txt',
            ]
        );
        $fs = get_file_storage();
        $draft = $fs->get_file(\context_user::instance($USER->id)->id, 'user', 'draft', $draftid, '/', 'lesson.txt');
        $draftsource = unserialize_object($draft->get_source());
        $this->assertIsObject($draftsource);
        $attributionuuid = json_decode($draftsource->source, true)['filebank'];
        $folder = $this->getDataGenerator()->create_module('folder', ['course' => $target->id]);
        $context = \context_module::instance($folder->cmid);
        file_save_draft_area_files($draftid, $context->id, 'mod_folder', 'content', 0, ['subdirs' => true]);
        $saved = $fs->get_file($context->id, 'mod_folder', 'content', 0, '/', 'lesson.txt');
        $this->assertSame('Material author', $saved->get_author());
        $this->assertSame($attributionuuid, json_decode($saved->get_source(), true)['filebank']);
        $editdraft = 0;
        file_prepare_draft_area($editdraft, $context->id, 'mod_folder', 'content', 0, ['subdirs' => true]);
        \repository::update_draftfile(
            $editdraft,
            '/',
            'lesson.txt',
            [
                'filename' => 'renamed-lesson.txt', 'author' => 'Later editor', 'license' => 'allrightsreserved',
            ]
        );
        file_save_draft_area_files($editdraft, $context->id, 'mod_folder', 'content', 0, ['subdirs' => true]);
        $saved = $fs->get_file($context->id, 'mod_folder', 'content', 0, '/', 'renamed-lesson.txt');
        $this->assertSame('Later editor', $saved->get_author());
        $this->assertSame('allrightsreserved', $saved->get_license());
        $attribution = $DB->get_record('local_filebank_author', ['uuid' => $attributionuuid], '*', MUST_EXIST);
        $this->assertSame('Material author', $attribution->author);
        $this->assertSame('cc', $attribution->license);
        $this->assertSame($attributionuuid, json_decode($saved->get_source(), true)['filebank']);
    }

    /**
     * A core file-size rejection must not leave a successful-copy provenance record.
     */
    public function test_failed_draft_copy_rolls_back_attribution(): void {
        global $DB, $USER;
        [$repository, $token] = $this->fixture();
        $this->preventResetByRollback();
        set_config('author_policy', 'editable_with_provenance', 'local_filebank');
        $reference = $repository->get_file_reference($token);
        $draftid = file_get_unused_draft_itemid();
        $before = $DB->count_records('local_filebank_author');
        try {
            $repository->copy_to_area(
                $reference,
                [
                    'itemid' => $draftid, 'filepath' => '/', 'filename' => 'lesson.txt',
                ],
                1
            );
            $this->fail('Core must reject this file-size limit.');
        } catch (\file_exception $exception) {
            $this->assertSame('maxbytesfile', $exception->errorcode);
        }
        $this->assertSame($before, $DB->count_records('local_filebank_author'));
        $this->assertFalse(
            get_file_storage()->get_file(
                \context_user::instance($USER->id)->id,
                'user',
                'draft',
                $draftid,
                '/',
                'lesson.txt'
            )
        );
    }

    /**
     * Policy changes after selection are checked again before draft creation.
     */
    public function test_policy_change_after_selection_blocks_copy(): void {
        global $DB;
        [$repository, $token] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_filebank');
        $reference = $repository->get_file_reference($token);
        set_config('author_policy', 'preserve_locked', 'local_filebank');
        try {
            $repository->copy_to_area(
                $reference,
                [
                    'itemid' => file_get_unused_draft_itemid(), 'filepath' => '/', 'filename' => 'lesson.txt',
                ]
            );
            $this->fail('An effective lock must refuse the copy.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('lockedunsupported', $exception->errorcode);
        }
        $this->assertSame(0, $DB->count_records('local_filebank_author'));
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
     * Copying ignores a spoofed importing-user author declaration.
     */
    public function test_draft_copy_preserves_selected_author(): void {
        global $USER;
        [$repository, $token] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_filebank');
        $reference = $repository->get_file_reference($token);
        $draftid = file_get_unused_draft_itemid();
        $repository->copy_to_area(
            $reference,
            [
                'contextid' => \context_user::instance($USER->id)->id, 'component' => 'user', 'filearea' => 'draft',
                'itemid' => $draftid, 'filepath' => '/', 'filename' => 'lesson.txt',
                'author' => 'Spoofed importing user', 'license' => 'allrightsreserved',
            ]
        );
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
