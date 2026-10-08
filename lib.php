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
 * Native Moodle file picker repository.
 *
 * @package repository_coursebridge
 * @copyright 2026 CourseBridge contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Presents one content result with selectable authorized source occurrences.
 */
class repository_coursebridge extends repository {
    /**
     * @var int Profile revalidated for the current selected source.
     */
    private int $selectedprofile = 0;
    /**
     * Supported return type.
     *
     * @return int Internal copies.
     */
    public function supported_returntypes() {
        return FILE_INTERNAL;
    }

    /**
     * Accepted source types.
     *
     * @return string All native file types.
     */
    public function supported_filetypes() {
        return '*';
    }

    /**
     * Native file-storage integration.
     *
     * @return bool True.
     */
    public function has_moodle_files() {
        return true;
    }

    /**
     * No external login.
     *
     * @return bool True.
     */
    public function check_login() {
        return true;
    }

    /**
     * Repository includes controlled course metadata.
     *
     * @return bool True.
     */
    public function contains_private_data() {
        return true;
    }

    /**
     * Display filtered listing or an asset's origins.
     *
     * @param string $path JSON navigation state.
     * @param mixed $page Native page indicator.
     * @return array Listing.
     */
    public function get_listing($path = '', $page = '') {
        global $DB;
        $state = [];
        if ($path !== '') {
            $state = json_decode(base64_decode($path, true) ?: '', true);
            if (!is_array($state)) {
                throw new moodle_exception('invalidcursor', 'local_coursebridge');
            }
        }
        $profileid = (int)($state['profileid'] ?? $this->profileid());
        $filters = $state['filters'] ?? $this->filters('');
        $result = \local_coursebridge\local\catalog::search(
            $this->courseid(),
            $profileid,
            $filters,
            $state['after'] ?? '',
            20
        );
        $listing = ['dynload' => true, 'nologin' => true, 'nosearch' => false,
            'list' => [], 'path' => [['name' => get_string('pluginname', 'repository_coursebridge'), 'path' => '']]];
        foreach ($result['assets'] as $asset) {
            if (!empty($state['asset']) && $state['asset'] !== $asset['contenthash']) {
                continue;
            }
            if (!empty($state['asset'])) {
                foreach ($asset['occurrences'] as $occurrence) {
                    $listing['list'][] = $this->filenode($occurrence, $asset['filesize'], $profileid);
                }
            } else if (count($asset['occurrences']) === 1) {
                $listing['list'][] = $this->filenode($asset['occurrences'][0], $asset['filesize'], $profileid);
            } else {
                $first = $asset['occurrences'][0];
                $listing['list'][] = [
                    'title' => $first['filename'] . ' (' . count($asset['occurrences']) . ')',
                    'children' => [], 'thumbnail' => $this->foldericon(),
                    'path' => base64_encode(json_encode(['profileid' => $profileid, 'filters' => $filters,
                        'after' => $state['after'] ?? '', 'asset' => $asset['contenthash']])),
                ];
            }
        }
        if (empty($state['asset']) && $result['next'] !== '') {
            $listing['list'][] = [
                'title' => get_string('nextpage', 'repository_coursebridge'),
                'children' => [], 'thumbnail' => $this->foldericon(),
                'path' => base64_encode(json_encode(['profileid' => $profileid, 'filters' => $filters,
                    'after' => $result['next']])),
            ];
        }
        return $listing;
    }

    /**
     * Search native repository.
     *
     * @param string $searchtext Search text.
     * @param int $page Page.
     * @return array Listing.
     */
    public function search($searchtext, $page = 0) {
        return $this->get_listing(base64_encode(json_encode([
            'profileid' => $this->profileid(), 'filters' => $this->filters($searchtext),
        ])));
    }

    /**
     * Native search form with contextual filters.
     *
     * @return string HTML.
     */
    public function print_search() {
        global $DB, $OUTPUT;
        $options = [];
        foreach ($DB->get_records('local_coursebridge', ['enabled' => 1], 'name') as $profile) {
            $options[$profile->id] = $profile->name;
        }
        return $OUTPUT->render_from_template('repository_coursebridge/search_form', [
            'id' => 'coursebridge-search-' . $this->id,
            'profiles' => \local_coursebridge\output\display::options($options, (string)$this->profileid()),
            'filetypes' => \local_coursebridge\output\display::options(\local_coursebridge\output\display::filetypes(), ''),
            'modules' => \local_coursebridge\output\display::options([
                '' => get_string('all'), 'folder' => get_string('modulename', 'folder'),
                'resource' => get_string('modulename', 'resource'),
            ], ''),
        ]);
    }

    /**
     * Resolve authorization and return a native packed reference.
     *
     * @param string $source Picker source token.
     * @return string Native reference.
     */
    public function get_file_reference($source) {
        $token = json_decode(base64_decode($source, true) ?: '', true);
        if (!is_array($token) || empty($token['pathnamehash']) || empty($token['profileid'])) {
            throw new moodle_exception('materialunavailable', 'local_coursebridge');
        }
        [$file, $cm] = \local_coursebridge\local\material_source::resolve(
            $token['pathnamehash'],
            $this->courseid(),
            (int)$token['profileid']
        );
        \local_coursebridge\local\author_policy::require_native_copy($file, $cm->course, $this->courseid());
        $this->selectedprofile = (int)$token['profileid'];
        return file_storage::pack_reference([
            'contextid' => $file->get_contextid(), 'component' => $file->get_component(),
            'filearea' => $file->get_filearea(), 'itemid' => $file->get_itemid(),
            'filepath' => $file->get_filepath(), 'filename' => $file->get_filename(),
        ]);
    }

    /**
     * Revalidate tokens rather than relying on generic file browser access.
     *
     * @param string $source Source token.
     * @return bool Allowed.
     */
    public function file_is_accessible($source) {
        try {
            $this->get_file_reference($source);
            return true;
        } catch (moodle_exception $exception) {
            return false;
        }
    }

    /**
     * Preserve initial author/license while retaining the core draft lifecycle.
     *
     * @param string $source Packed reference produced above.
     * @param array|\stdClass $filerecord Native draft record.
     * @param int $maxbytes Per-file maximum.
     * @param int $areamaxbytes Draft maximum.
     * @return array Core response.
     */
    public function copy_to_area($source, $filerecord, $maxbytes = -1, $areamaxbytes = FILE_AREA_MAX_BYTES_UNLIMITED) {
        global $DB;
        $file = repository::get_moodle_file($source);
        if (!$file) {
            throw new moodle_exception('materialunavailable', 'local_coursebridge');
        }
        $profileid = $this->selectedprofile ?: $this->profileid();
        [$file, $cm] = \local_coursebridge\local\material_source::resolve(
            $file->get_pathnamehash(),
            $this->courseid(),
            $profileid
        );
        \local_coursebridge\local\author_policy::require_native_copy($file, $cm->course, $this->courseid());
        $record = (array)$filerecord;
        $record['author'] = $file->get_author();
        $record['license'] = $file->get_license();
        $transaction = $DB->start_delegated_transaction();
        try {
            $attributionid = \local_coursebridge\local\author_policy::capture($file);
            $record['source'] = serialize((object)['source' => json_encode(['coursebridge' => $DB->get_field(
                'local_coursebridge_attribution',
                'uuid',
                ['id' => $attributionid]
            )])]);
            $result = parent::copy_to_area($source, $record, $maxbytes, $areamaxbytes);
            $transaction->allow_commit();
            return $result;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }
    }

    /**
     * Server-resolved target course.
     *
     * @return int Course.
     */
    private function courseid(): int {
        $context = $this->context->get_course_context(false);
        if (!$context || $context->instanceid == SITEID) {
            throw new moodle_exception('targetrequired', 'local_coursebridge');
        }
        return (int)$context->instanceid;
    }

    /**
     * Selected enabled profile.
     *
     * @return int Profile.
     */
    private function profileid(): int {
        global $DB;
        $id = optional_param('cbprofile', 0, PARAM_INT);
        if (!$id) {
            $profiles = $DB->get_records('local_coursebridge', ['enabled' => 1], 'id', 'id', 0, 1);
            $id = $profiles ? (int)reset($profiles)->id : 0;
        }
        if (!$id) {
            throw new moodle_exception('noprofile', 'local_coursebridge');
        }
        return $id;
    }

    /**
     * Native request filters.
     *
     * @param string $query Filename query.
     * @return array Filters.
     */
    private function filters(string $query): array {
        return ['q' => $query, 'edition' => optional_param('cbedition', '', PARAM_TEXT),
            'module' => optional_param('cbmodule', '', PARAM_ALPHA),
            'shortname' => optional_param('cbshortname', '', PARAM_TEXT),
            'mimetype' => optional_param('cbmime', '', PARAM_TEXT)];
    }

    /**
     * Build a concrete occurrence node.
     *
     * @param array $occurrence Authorized metadata.
     * @param int $size Bytes.
     * @param int $profileid Profile.
     * @return array Node.
     */
    private function filenode(array $occurrence, int $size, int $profileid): array {
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
    private function foldericon(): string {
        global $OUTPUT;
        return $OUTPUT->image_url('f/folder')->out(false);
    }
}
