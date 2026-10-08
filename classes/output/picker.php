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
 * Native picker node presentation.
 *
 * @package repository_coursebridge
 * @copyright 2026 CourseBridge contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_coursebridge\output;

/**
 * Formats authorized occurrences for Moodle's picker listing.
 */
final class picker {
    /**
     * Build a concrete occurrence node.
     *
     * @param array $occurrence Authorized metadata.
     * @param int $size Bytes.
     * @param int $profileid Profile.
     * @return array Node.
     */
    public static function filenode(array $occurrence, int $size, int $profileid): array {
        global $OUTPUT;
        return ['title' => $occurrence['filename'], 'shorttitle' => $occurrence['shortname'] . ': '
            . $occurrence['modulename'] . $occurrence['filepath'] . $occurrence['filename'],
            'size' => $size, 'author' => $occurrence['author'], 'license' => $occurrence['license'],
            'source' => base64_encode(json_encode(['profileid' => $profileid,
                'pathnamehash' => $occurrence['pathnamehash']])),
            'thumbnail' => $OUTPUT->image_url(file_extension_icon($occurrence['filename']))->out(false)];
    }

    /**
     * Core folder icon.
     *
     * @return string URL.
     */
    public static function foldericon(): string {
        global $OUTPUT;
        return $OUTPUT->image_url('f/folder')->out(false);
    }
}
