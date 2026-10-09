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
 * @package repository_multipicker
 * @copyright 2026 MultiPicker contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use local_multipicker\local\catalog\catalog;
use local_multipicker\local\catalog\material_source;
use local_multipicker\local\policy\author_policy;
use local_multipicker\output\display;
use repository_multipicker\local\picker\filters;
use repository_multipicker\output\picker;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Presents one content result with selectable authorized source occurrences.
 */
class repository_multipicker extends repository {
    /**
     * @var int Profile revalidated for the current selected source.
     */
    private int $selectedprofile = 0;
    /** @var array|null Session-approved native batch source. */
    private ?array $selectedbatch = null;
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
        $scope = \local_multipicker\local\selection\draft_scope::find(
            $this->context,
            optional_param('itemid', 0, PARAM_INT),
            sesskey()
        );
        if ($scope !== '' && in_array(optional_param('env', '', PARAM_ALPHA), ['filemanager', 'editor'], true)) {
            \local_multipicker\local\selection\draft_scope::select_picker(
                $scope,
                (array)($this->options['mimetypes'] ?? ['*']),
                optional_param('env', '', PARAM_ALPHA),
                sesskey()
            );
            return ['nologin' => true, 'nosearch' => true, 'norefresh' => true,
                'object' => ['type' => 'text/html', 'src' => (new moodle_url(
                    '/local/multipicker/selection/index.php',
                    ['scope' => $scope]
                ))->out(false)]];
        }
        $state = [];
        if ($path !== '') {
            $state = json_decode(base64_decode($path, true) ?: '', true);
            if (!is_array($state)) {
                throw new moodle_exception('invalidcursor', 'local_multipicker');
            }
        }
        $profileid = (int)($state['profileid'] ?? $this->profileid());
        $filters = $state['filters'] ?? filters::from_request('');
        $result = catalog::search(
            $this->courseid(),
            $profileid,
            $filters,
            $state['after'] ?? '',
            20
        );
        $listing = ['dynload' => true, 'nologin' => true, 'nosearch' => false,
            'list' => [], 'path' => [['name' => get_string('pluginname', 'repository_multipicker'), 'path' => '']]];
        foreach ($result['assets'] as $asset) {
            if (!empty($state['asset']) && $state['asset'] !== $asset['contenthash']) {
                continue;
            }
            if (!empty($state['asset'])) {
                foreach ($asset['occurrences'] as $occurrence) {
                    $listing['list'][] = picker::filenode($occurrence, $asset['filesize'], $profileid);
                }
            } else if (count($asset['occurrences']) === 1) {
                $listing['list'][] = picker::filenode($asset['occurrences'][0], $asset['filesize'], $profileid);
            } else {
                $first = $asset['occurrences'][0];
                $listing['list'][] = [
                    'title' => $first['filename'] . ' (' . count($asset['occurrences']) . ')',
                    'children' => [], 'thumbnail' => picker::foldericon(),
                    'path' => base64_encode(json_encode(['profileid' => $profileid, 'filters' => $filters,
                        'after' => $state['after'] ?? '', 'asset' => $asset['contenthash']])),
                ];
            }
        }
        if (empty($state['asset']) && $result['next'] !== '') {
            $listing['list'][] = [
                'title' => get_string('nextpage', 'repository_multipicker'),
                'children' => [], 'thumbnail' => picker::foldericon(),
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
            'profileid' => $this->profileid(), 'filters' => filters::from_request($searchtext),
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
        foreach ($DB->get_records('local_multipicker', ['enabled' => 1], 'name') as $profile) {
            $options[$profile->id] = $profile->name;
        }
        return $OUTPUT->render_from_template('repository_multipicker/local/picker/search_form', [
            'id' => 'multipicker-search-' . $this->id,
            'profiles' => display::options($options, (string)$this->profileid()),
            'filetypes' => display::options(display::filetypes(), ''),
            'modules' => display::options([
                '' => get_string('all'),
            ] + \local_multipicker\local\catalog\source_registry::module_names(), ''),
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
        if (is_array($token) && isset($token['batch'], $token['digest'])) {
            if (!is_string($token['batch']) || !is_string($token['digest'])) {
                throw new moodle_exception('invalidselection', 'local_multipicker');
            }
            [$scope, $review] = \local_multipicker\local\selection\draft_scope::approved(
                $token['batch'],
                $token['digest'],
                sesskey()
            );
            if ($scope['contextid'] !== $this->context->id) {
                throw new moodle_exception('formscopeunavailable', 'local_multipicker');
            }
            $this->selectedbatch = $token;
            $file = get_file_storage()->get_file_by_hash($review['operations'][0]['pathnamehash']);
            return file_storage::pack_reference([
                'contextid' => $file->get_contextid(), 'component' => $file->get_component(),
                'filearea' => $file->get_filearea(), 'itemid' => $file->get_itemid(),
                'filepath' => $file->get_filepath(), 'filename' => $file->get_filename(),
            ]);
        }
        if (!is_array($token) || empty($token['pathnamehash']) || empty($token['profileid'])) {
            throw new moodle_exception('materialunavailable', 'local_multipicker');
        }
        [$file, $cm] = material_source::resolve(
            $token['pathnamehash'],
            $this->courseid(),
            (int)$token['profileid']
        );
        author_policy::require_native_copy($file, $cm->course, $this->courseid());
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
        global $DB, $USER, $OUTPUT;
        if ($this->selectedbatch !== null) {
            $token = $this->selectedbatch;
            $this->selectedbatch = null;
            [$scope] = \local_multipicker\local\selection\draft_scope::approved($token['batch'], $token['digest'], sesskey());
            if ($scope['contextid'] !== $this->context->id || $scope['draftid'] !== (int)((array)$filerecord)['itemid']) {
                throw new moodle_exception('formscopeunavailable', 'local_multipicker');
            }
            $review = \local_multipicker\local\selection\draft_scope::apply($token['batch'], $token['digest'], sesskey());
            $context = context_user::instance($USER->id);
            $fs = get_file_storage();
            foreach ($review['operations'] as $operation) {
                $copied = $fs->get_file(
                    $context->id,
                    'user',
                    'draft',
                    $scope['draftid'],
                    $operation['filepath'],
                    $operation['filename']
                );
                if ($copied) {
                    return ['url' => moodle_url::make_draftfile_url(
                        $scope['draftid'],
                        $copied->get_filepath(),
                        $copied->get_filename()
                    )->out(false),
                        'id' => $scope['draftid'], 'file' => $copied->get_filename(),
                        'icon' => $OUTPUT->image_url('f/document')->out(false)];
                }
            }
            foreach ($fs->get_area_files($context->id, 'user', 'draft', $scope['draftid'], 'id', false) as $existing) {
                foreach ($review['operations'] as $operation) {
                    if ($existing->get_contenthash() === $operation['snapshot']['contenthash']) {
                        return ['url' => moodle_url::make_draftfile_url(
                            $scope['draftid'],
                            $existing->get_filepath(),
                            $existing->get_filename()
                        )->out(false),
                            'id' => $scope['draftid'], 'file' => $existing->get_filename(),
                            'icon' => $OUTPUT->image_url('f/document')->out(false)];
                    }
                }
            }
            throw new moodle_exception('batchconflict', 'local_multipicker');
        }
        $file = repository::get_moodle_file($source);
        if (!$file) {
            throw new moodle_exception('materialunavailable', 'local_multipicker');
        }
        $profileid = $this->selectedprofile ?: $this->profileid();
        [$file, $cm] = material_source::resolve(
            $file->get_pathnamehash(),
            $this->courseid(),
            $profileid
        );
        author_policy::require_native_copy($file, $cm->course, $this->courseid());
        $record = (array)$filerecord;
        $record['author'] = $file->get_author();
        $record['license'] = $file->get_license();
        $transaction = $DB->start_delegated_transaction();
        try {
            $attributionid = author_policy::capture($file);
            $record['source'] = serialize((object)['source' => json_encode(['multipicker' => $DB->get_field(
                'local_multipicker_attribution',
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
            throw new moodle_exception('targetrequired', 'local_multipicker');
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
            $profiles = $DB->get_records('local_multipicker', ['enabled' => 1], 'id', 'id', 0, 1);
            $id = $profiles ? (int)reset($profiles)->id : 0;
        }
        if (!$id) {
            throw new moodle_exception('noprofile', 'local_multipicker');
        }
        return $id;
    }
}
