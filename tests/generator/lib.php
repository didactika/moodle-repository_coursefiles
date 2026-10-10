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
 * Published course edition fixtures for browser acceptance.
 *
 * @package repository_filebank
 * @copyright 2026 Didactika.org
 * @author Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_filebank\local\profile\profiles;
use local_filebank\local\catalog\indexer;
use local_filebank\local\catalog\material_source;

/**
 * Native file picker fixtures, isolated by Moodle's test environment.
 */
final class repository_filebank_generator extends component_generator_base {
    /**
     * Create published editions and a teacher enrolled only in the destination.
     *
     * @param array $record Fixture name.
     * @return array Fixture identifiers.
     */
    public function create_picker_fixture(array $record): array {
        global $CFG, $DB, $USER;
        $previoususer = $USER;
        $this->set_user(get_admin());
        try {
            $example = $CFG->dirroot . '/local/filebank/examples/institutional-profile.json';
            $config = json_decode(file_get_contents($example), true);
            $config['name'] = $record['name'];
            $config['publication']['mode'] = 'explicit-family-publication';
            $profileid = profiles::save(json_encode($config));
            set_config('author_policy', 'editable_with_provenance', 'local_filebank');
            $uuid = \core\uuid::generate();
            $courses = [];
            foreach ([2024, 2025, 2026] as $edition) {
                $courses[$edition] = $this->datagenerator->create_course([
                    'fullname' => 'FileBank ' . $edition, 'shortname' => 'CB' . $edition,
                    'idnumber' => $uuid . '||CB||' . $edition,
                ]);
                $folder = $this->datagenerator->create_module('folder', [
                    'course' => $courses[$edition]->id, 'name' => $edition === 2026 ? 'Target materials' : 'Published materials',
                ]);
                $this->create_file(
                    $folder->cmid,
                    'mod_folder',
                    $edition === 2026 ? 'existing.txt' : 'lesson.txt',
                    $edition === 2026 ? 'Existing destination content' : 'Shared lesson content'
                );
                if ($edition === 2026) {
                    $targetcmid = $folder->cmid;
                } else {
                    material_source::publish($profileid, $folder->cmid, false);
                }
            }
            $resource = $this->datagenerator->create_module('resource', [
                'course' => $courses[2025]->id, 'name' => 'Published resource',
            ]);
            get_file_storage()->delete_area_files(
                context_module::instance($resource->cmid)->id,
                'mod_resource',
                'content',
                0
            );
            $this->create_file($resource->cmid, 'mod_resource', 'resource.txt', 'Resource content');
            material_source::publish($profileid, $resource->cmid, false);
            $hidden = $this->datagenerator->create_module('folder', [
                'course' => $courses[2025]->id, 'name' => 'Hidden materials', 'visible' => 0,
            ]);
            $this->create_file($hidden->cmid, 'mod_folder', 'hidden.txt', 'Hidden content');
            foreach ($courses as $course) {
                indexer::refresh($course->id);
            }
            $teacher = $this->datagenerator->create_user(['username' => 'cbteacher', 'password' => 'cbteacher']);
            $this->datagenerator->enrol_user($teacher->id, $courses[2026]->id, 'editingteacher');
            require_once($CFG->dirroot . '/repository/filebank/lib.php');
            $type = new repository_type('filebank');
            $type->create(false);
            return ['targetcmid' => $targetcmid, 'targetcourse' => $courses[2026]->id, 'profileid' => $profileid,
                'teacherid' => $teacher->id, 'wwwroot' => $CFG->wwwroot];
        } finally {
            $this->set_user($previoususer);
        }
    }

    /**
     * Store material through the native File API.
     *
     * @param int $cmid Source module.
     * @param string $component Native owner.
     * @param string $filename Filename.
     * @param string $content Content.
     */
    private function create_file(int $cmid, string $component, string $filename, string $content): void {
        get_file_storage()->create_file_from_string([
            'contextid' => context_module::instance($cmid)->id, 'component' => $component,
            'filearea' => 'content', 'itemid' => 0, 'filepath' => '/', 'filename' => $filename,
            'author' => 'Original teacher', 'license' => 'cc',
        ], $content);
    }
}
