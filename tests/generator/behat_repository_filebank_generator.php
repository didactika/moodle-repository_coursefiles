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
 * Native browser fixture generator mapping.
 *
 * @package repository_filebank
 * @copyright 2026 Didactika.org
 * @author Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Makes published multi-edition materials available to native browser scenarios.
 */
final class behat_repository_filebank_generator extends behat_generator_base {
    /**
     * Describe component fixtures available to Moodle's generic generator step.
     *
     * @return array Entities.
     */
    protected function get_creatable_entities(): array {
        return ['picker fixtures' => [
            'singular' => 'picker fixture', 'datagenerator' => 'picker_fixture', 'required' => ['name'],
        ]];
    }
}
