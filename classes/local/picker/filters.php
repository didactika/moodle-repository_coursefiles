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
 * Native picker request filters.
 *
 * @package repository_multipicker
 * @copyright 2026 MultiPicker contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_multipicker\local\picker;

/**
 * Reads supported native request controls.
 */
final class filters {
    /**
     * Collect filters without exposing other request fields.
     *
     * @param string $query Filename query.
     * @return array Filters.
     */
    public static function from_request(string $query): array {
        return ['q' => $query, 'edition' => optional_param('cbedition', '', PARAM_TEXT),
            'module' => optional_param('cbmodule', '', PARAM_ALPHA),
            'shortname' => optional_param('cbshortname', '', PARAM_TEXT),
            'mimetype' => optional_param('cbmime', '', PARAM_TEXT)];
    }
}
