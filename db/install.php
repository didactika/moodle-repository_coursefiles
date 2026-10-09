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
 * Retain configured repository instances when adopting the MultiPicker name.
 *
 * @package repository_multipicker
 * @copyright 2026 MultiPicker contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**

 * Reuse the old type ID so repository instances and file references remain valid.

 */
function xmldb_repository_multipicker_install(): void {
    global $DB;
    $legacy = $DB->get_record('repository', ['type' => 'coursebridge']);
    if (!$legacy) {
        return;
    }
    if ($DB->record_exists('repository', ['type' => 'multipicker'])) {
        throw new coding_exception('Both repository types exist; resolve the duplicate before importing legacy instances.');
    }
    unset_config('version', 'repository_coursebridge');
    $legacy->type = 'multipicker';
    $DB->update_record('repository', $legacy);
    foreach ($DB->get_records('config_plugins', ['plugin' => 'repository_coursebridge']) as $setting) {
        if ($setting->name !== 'version') {
            set_config($setting->name, $setting->value, 'repository_multipicker');
        }
    }
}
