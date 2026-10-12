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

use local_filebank\output\display;

/**
 * Native repository native picker contracts.
 */
final class native_picker {
    /**
     * Embedded.
     *
     * @param \context $context Owning native context.
     * @param string $name Configured repository name.
     * @param array $mimetypes Actual native accepted file types.
     * @return array|null Result.
     */
    public static function embedded($context, string $name, array $mimetypes): ?array {
        $scope = \local_filebank\local\selection\draft_scope::find(
            $context,
            optional_param('itemid', 0, PARAM_INT),
            sesskey()
        );
        if ($scope !== '' && in_array(optional_param('env', '', PARAM_ALPHA), ['filemanager', 'editor'], true)) {
            \local_filebank\local\selection\draft_state::select_repository($scope, $name, sesskey());
            \local_filebank\local\selection\draft_state::select_picker(
                $scope,
                $mimetypes,
                optional_param('env', '', PARAM_ALPHA),
                sesskey()
            );
            return ['nologin' => true, 'nosearch' => true, 'norefresh' => true,
                'object' => ['type' => 'text/html', 'src' => (new \moodle_url(
                    '/local/filebank/selection/index.php',
                    ['scope' => $scope]
                ))->out(false)]];
        }
        return null;
    }

    /**
     * Search form.
     *
     * @param int $repositoryid Repository instance.
     * @param int $profileid Selected enabled profile.
     * @return string Result.
     */
    public static function search_form(int $repositoryid, int $profileid): string {
        global $DB, $OUTPUT;
        $options = [];
        foreach ($DB->get_records('local_filebank', ['enabled' => 1], 'name') as $profile) {
            $options[$profile->id] = $profile->name;
        }
        return $OUTPUT->render_from_template(
            'repository_filebank/local/picker/search_form',
            [
                'id' => 'filebank-search-' . $repositoryid,
                'filterhelp' => $OUTPUT->help_icon('listfilters', 'local_filebank'),
                'profiles' => display::options($options, (string)$profileid),
                'filetypes' => display::options(display::filetypes(), ''),
                'modules' => display::options(
                    [
                        '' => get_string('all'),
                    ] + \local_filebank\local\catalog\source_registry::module_names(),
                    ''
                ),
            ]
        );
    }
}
