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
 * Native Moodle file picker repository.
 *
 * @package repository_filebank
 * @copyright 2026 Didactika.org
 * @author Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_filebank\local\picker;

use local_filebank\local\catalog\material_source;
use local_filebank\local\policy\author_policy;

/**
 * Native repository transfer contracts.
 */
final class transfer {
    /**
     * Batch reference.
     *
     * @param array $token Session-approved source token.
     * @param int $contextid Actual owning repository context.
     * @return string Result.
     */
    public static function batch_reference(array $token, int $contextid): string {
        if (!is_string($token['batch']) || !is_string($token['digest'])) {
            throw new \moodle_exception('invalidselection', 'local_filebank');
        }
        [$scope, $review] = \local_filebank\local\selection\draft_scope::approved(
            $token['batch'],
            $token['digest'],
            sesskey()
        );
        if ($scope['contextid'] !== $contextid) {
            throw new \moodle_exception('formscopeunavailable', 'local_filebank');
        }
        $file = get_file_storage()->get_file_by_hash($review['operations'][0]['pathnamehash']);
        return self::reference($file);
    }

    /**
     * Source.
     *
     * @param mixed $token Decoded single-file token.
     * @param int $courseid Authorized target course.
     * @return array Result.
     */
    public static function source($token, int $courseid): array {
        if (!is_array($token) || empty($token['pathnamehash']) || empty($token['profileid'])) {
            throw new \moodle_exception('materialunavailable', 'local_filebank');
        }
        [$file, $cm] = material_source::resolve(
            $token['pathnamehash'],
            $courseid,
            (int)$token['profileid']
        );
        author_policy::require_native_copy($file, $cm->course, $courseid);
        return [$file, (int)$token['profileid']];
    }

    /**
     * Reference.
     *
     * @param \stored_file $file Fresh authorized source.
     * @return string Result.
     */
    public static function reference(\stored_file $file): string {
        return \file_storage::pack_reference(
            [
                'contextid' => $file->get_contextid(), 'component' => $file->get_component(),
                'filearea' => $file->get_filearea(), 'itemid' => $file->get_itemid(),
                'filepath' => $file->get_filepath(), 'filename' => $file->get_filename(),
            ]
        );
    }

    /**
     * Batch copy.
     *
     * @param array $token Approved batch token.
     * @param int $contextid Owning native context.
     * @param array|\stdClass $filerecord Native draft record.
     * @return array Result.
     */
    public static function batch_copy(array $token, int $contextid, $filerecord): array {
        global $USER;
        [$scope] = \local_filebank\local\selection\draft_scope::approved($token['batch'], $token['digest'], sesskey());
        if ($scope['contextid'] !== $contextid || $scope['draftid'] !== (int)((array)$filerecord)['itemid']) {
            throw new \moodle_exception('formscopeunavailable', 'local_filebank');
        }
        $review = \local_filebank\local\selection\draft_state::apply($token['batch'], $token['digest'], sesskey());
        $context = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $copied = self::copied_file($fs, $context->id, $scope['draftid'], $review['operations']);
        if (!$copied) {
            throw new \moodle_exception('batchconflict', 'local_filebank');
        }
        return self::response($scope['draftid'], $copied);
    }

    /**
     * Copied file.
     *
     * @param \file_storage $fs Native storage service.
     * @param int $contextid Draft owner context.
     * @param int $draftid Actual native draft.
     * @param array $operations Approved operations.
     * @return \stored_file|null Result.
     */
    private static function copied_file(\file_storage $fs, int $contextid, int $draftid, array $operations): ?\stored_file {
        foreach ($operations as $operation) {
            $copied = $fs->get_file($contextid, 'user', 'draft', $draftid, $operation['filepath'], $operation['filename']);
            if ($copied) {
                return $copied;
            }
        }
        $hashes = array_fill_keys(array_column(array_column($operations, 'snapshot'), 'contenthash'), true);
        foreach ($fs->get_area_files($contextid, 'user', 'draft', $draftid, 'id', false) as $existing) {
            if (isset($hashes[$existing->get_contenthash()])) {
                return $existing;
            }
        }
        return null;
    }

    /**
     * Response.
     *
     * @param int $draftid Native draft item.
     * @param \stored_file $copied Copied or identical existing draft file.
     * @return array Result.
     */
    private static function response(int $draftid, \stored_file $copied): array {
        global $OUTPUT;
        return ['url' => \moodle_url::make_draftfile_url(
            $draftid,
            $copied->get_filepath(),
            $copied->get_filename()
        )->out(false),
            'id' => $draftid, 'file' => $copied->get_filename(),
            'icon' => $OUTPUT->image_url('f/document')->out(false)];
    }
}
