<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package repository_coursebridge
 * @copyright 2026 CourseBridge contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_coursebridge;

/**
 * Exercises the real core repository/draft contract.
 *
 */
final class repository_test extends \advanced_testcase {
    /**
     * Create a native repository with one permitted source.
     *
     * @return array Repository, source token and target.
     */
    private function fixture(): array {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/repository/coursebridge/lib.php');
        $config = json_decode(file_get_contents($CFG->dirroot . '/local/coursebridge/examples/institutional-profile.json'), true);
        $profileid = \local_coursebridge\local\profiles::save(json_encode($config));
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $source = $this->getDataGenerator()->create_course(['idnumber' => $uuid . '||MATH||2025']);
        $target = $this->getDataGenerator()->create_course(['idnumber' => $uuid . '||MATH||2026']);
        $folder = $this->getDataGenerator()->create_module('folder', ['course' => $source->id]);
        $file = get_file_storage()->create_file_from_string([
            'contextid' => \context_module::instance($folder->cmid)->id, 'component' => 'mod_folder',
            'filearea' => 'content', 'itemid' => 0, 'filepath' => '/', 'filename' => 'lesson.txt',
            'author' => 'Material author', 'license' => 'cc',
        ], 'lesson');
        $type = new \repository_type('coursebridge');
        $typeid = $type->create(false);
        $instanceid = $DB->get_field('repository_instances', 'id', ['typeid' => $typeid]);
        $repository = new \repository_coursebridge($instanceid, \context_course::instance($target->id));
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
        set_config('author_policy', 'editable_with_provenance', 'local_coursebridge');
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
     * Arbitrary native references cannot replace the signed occurrence token.
     */
    public function test_malformed_source_is_rejected(): void {
        [$repository] = $this->fixture();
        $this->assertFalse($repository->file_is_accessible(base64_encode('{"profileid":1}')));
    }
}
