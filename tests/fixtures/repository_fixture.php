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

/**
 * Native repository fixture shared by focused contract tests.
 */
abstract class repository_fixture extends \advanced_testcase {
    /**
     * Create a native repository with one permitted source.
     *
     * @return array Repository, source token and target.
     */
    protected function fixture(): array {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/repository/filebank/lib.php');
        $config = $this->getDataGenerator()->get_plugin_generator('local_filebank')->create_profile_config();
        $profileid = \local_filebank\local\profile\profiles::save(json_encode($config));
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $source = $this->getDataGenerator()->create_course(['idnumber' => $uuid . '||MATH||2025']);
        $target = $this->getDataGenerator()->create_course(['idnumber' => $uuid . '||MATH||2026']);
        $folder = $this->getDataGenerator()->create_module('folder', ['course' => $source->id]);
        $file = get_file_storage()->create_file_from_string(
            [
                'contextid' => \context_module::instance($folder->cmid)->id, 'component' => 'mod_folder',
                'filearea' => 'content', 'itemid' => 0, 'filepath' => '/', 'filename' => 'lesson.txt',
                'author' => 'Material author', 'license' => 'cc',
            ],
            'lesson'
        );
        $type = new \repository_type('filebank');
        $typeid = $type->create(false);
        $instanceid = $DB->get_field('repository_instances', 'id', ['typeid' => $typeid]);
        $repository = new \repository_filebank($instanceid, \context_course::instance($target->id));
        $token = base64_encode(json_encode(['profileid' => $profileid, 'pathnamehash' => $file->get_pathnamehash()]));
        return [$repository, $token, $target];
    }
}
