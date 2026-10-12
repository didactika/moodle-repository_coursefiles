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
use local_filebank\local\catalog\material_source;
use local_filebank\local\policy\author_policy;
use repository_filebank\local\picker\filters;
use repository_filebank\local\picker\listing;
use repository_filebank\local\picker\native_picker;
use repository_filebank\local\picker\transfer;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Presents one content result with selectable authorized source occurrences.
 */
class repository_filebank extends repository {
    /**
     * @var int Profile revalidated for the current selected source.
     */
    private int $selectedprofile = 0;
    /**
     * @var array|null Session-approved native batch source.
     */
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
        $embedded = native_picker::embedded(
            $this->context,
            $this->get_name(),
            (array)($this->options['mimetypes'] ?? ['*'])
        );
        if ($embedded !== null) {
            return $embedded;
        }
        $state = listing::state($path, $page);
        return listing::data($this->courseid(), (int)($state['profileid'] ?? $this->profileid()), $this->get_name(), $state);
    }

    /**
     * Search native repository.
     *
     * @param string $searchtext Search text.
     * @param int $page Page.
     * @return array Listing.
     */
    public function search($searchtext, $page = 0) {
        return $this->get_listing(
            base64_encode(
                json_encode(
                    [
                        'profileid' => $this->profileid(), 'filters' => filters::from_request($searchtext),
                    ]
                )
            ),
            $page
        );
    }

    /**
     * Native search form with contextual filters.
     *
     * @return string HTML.
     */
    public function print_search() {
        return native_picker::search_form($this->id, $this->profileid());
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
            $reference = transfer::batch_reference($token, $this->context->id);
            $this->selectedbatch = $token;
            return $reference;
        }
        [$file, $this->selectedprofile] = transfer::source($token, $this->courseid());
        return transfer::reference($file);
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
        if ($this->selectedbatch !== null) {
            $token = $this->selectedbatch;
            $this->selectedbatch = null;
            return transfer::batch_copy($token, $this->context->id, $filerecord);
        }
        $file = repository::get_moodle_file($source);
        if (!$file) {
            throw new moodle_exception('materialunavailable', 'local_filebank');
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
            $record['source'] = serialize(
                (object)['source' => json_encode(
                    ['filebank' => $DB->get_field(
                        'local_filebank_author',
                        'uuid',
                        ['id' => $attributionid]
                    )]
                )]
            );
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
            throw new moodle_exception('targetrequired', 'local_filebank');
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
            $profiles = $DB->get_records('local_filebank', ['enabled' => 1], 'id', 'id', 0, 1);
            $id = $profiles ? (int)reset($profiles)->id : 0;
        }
        if (!$id) {
            throw new moodle_exception('noprofile', 'local_filebank');
        }
        return $id;
    }
}
