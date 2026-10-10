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
 * Repository data ownership declaration.
 *
 * @package repository_multipicker
 * @copyright 2026 Didactika.org
 * @author Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace repository_multipicker\privacy;

/**
 * Owns no independent records; the core draft subsystem and local plugin own them.
 */
final class provider implements \core_privacy\local\metadata\provider {
    /**
     * Declare linked subsystems.
     *
     * @param \core_privacy\local\metadata\collection $collection Metadata.
     * @return \core_privacy\local\metadata\collection Metadata.
     */
    public static function get_metadata(
        \core_privacy\local\metadata\collection $collection
    ): \core_privacy\local\metadata\collection {
        $collection->add_subsystem_link('files', [], 'privacy_metadata');
        $collection->add_subsystem_link('local_multipicker', [], 'privacy_metadata');
        return $collection;
    }
}
