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

use local_filebank\local\catalog\catalog;
use repository_filebank\output\picker;

/**
 * Native repository listing contracts.
 */
final class listing {
    /**
     * State.
     *
     * @param string $path Encoded native navigation state.
     * @param mixed $page Native page indicator echoed in the listing.
     * @return array Result.
     */
    public static function state(string $path, $page): array {
        $state = [];
        if ($path !== '') {
            $state = json_decode(base64_decode($path, true) ?: '', true);
            if (!is_array($state)) {
                throw new \moodle_exception('invalidcursor', 'local_filebank');
            }
        }
        $state['page'] = max(0, (int)$page);
        return $state;
    }

    /**
     * Data.
     *
     * @param int $courseid Authorized target course.
     * @param int $profileid Selected profile.
     * @param string $name Repository title.
     * @param array $state Validated navigation state.
     * @return array Result.
     */
    public static function data(int $courseid, int $profileid, string $name, array $state): array {
        $filters = $state['filters'] ?? filters::from_request('');
        $result = catalog::search(
            $courseid,
            $profileid,
            $filters,
            $state['after'] ?? '',
            20,
            optional_param('itemid', 0, PARAM_INT)
        );
        $listing = ['dynload' => true, 'nologin' => true, 'nosearch' => false,
            'list' => [], 'page' => $state['page'], 'path' => [['name' => $name, 'path' => '']]];
        foreach ($result['assets'] as $asset) {
            $listing['list'] = array_merge($listing['list'], self::nodes($asset, $state, $profileid, $filters));
        }
        if (empty($state['asset']) && $result['next'] !== '') {
            $listing['list'][] = [
                'title' => get_string('nextpage', 'repository_filebank'),
                'children' => [], 'thumbnail' => picker::foldericon(),
                'path' => base64_encode(
                    json_encode(
                        ['profileid' => $profileid, 'filters' => $filters,
                            'after' => $result['next']]
                    )
                ),
            ];
        }
        return $listing;
    }

    /**
     * Nodes.
     *
     * @param array $asset Authorized content occurrences.
     * @param array $state Navigation state.
     * @param int $profileid Selected profile.
     * @param array $filters Active catalogue restrictions.
     * @return array Result.
     */
    private static function nodes(array $asset, array $state, int $profileid, array $filters): array {
        $nodes = [];
        if (!empty($state['asset']) && $state['asset'] !== $asset['contenthash']) {
            return [];
        }
        if (!empty($state['asset'])) {
            foreach ($asset['occurrences'] as $occurrence) {
                $nodes[] = picker::filenode($occurrence, $asset['filesize'], $profileid);
            }
        } else if (count($asset['occurrences']) === 1) {
            $nodes[] = picker::filenode($asset['occurrences'][0], $asset['filesize'], $profileid);
        } else {
            $first = $asset['occurrences'][0];
            $nodes[] = [
                'title' => $first['filename'] . ' (' . count($asset['occurrences']) . ')',
                'children' => [], 'thumbnail' => picker::foldericon(),
                'path' => base64_encode(
                    json_encode(
                        ['profileid' => $profileid, 'filters' => $filters,
                            'after' => $state['after'] ?? '', 'asset' => $asset['contenthash']]
                    )
                ),
            ];
        }
        return $nodes;
    }
}
