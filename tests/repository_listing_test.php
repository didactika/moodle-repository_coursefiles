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
 * Native repository authorization and draft metadata tests.
 *
 * @package repository_filebank
 * @copyright 2026 Didactika.org
 * @author Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_filebank;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/repository_fixture.php');

/**
 * Focused native repository regression contracts.
 *
 * @covers \repository_filebank
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\repository_filebank::class)]
final class repository_listing_test extends repository_fixture {
    /**
     * A bound native field opens the embedded chooser with its exact server-issued scope.
     */
    public function test_bound_field_returns_embedded_chooser(): void {
        global $PAGE;
        [$repository, , $target] = $this->fixture();
        $PAGE->set_context(\context_course::instance($target->id));
        $control = new \local_filebank\form\scoped_filemanager(
            'files',
            'Files',
            null,
            ['subdirs' => true, 'maxfiles' => 3, 'accepted_types' => '*']
        );
        $scope = $control->open_scope(\context_course::instance($target->id));
        $_GET['itemid'] = $control->getValue();
        $_GET['env'] = 'filemanager';
        try {
            $listing = $repository->get_listing();
            $this->assertSame('text/html', $listing['object']['type']);
            $url = new \moodle_url($listing['object']['src']);
            $this->assertSame($scope, $url->get_param('scope'));
        } finally {
            unset($_GET['itemid'], $_GET['env']);
        }
    }

    /**
     * Organized picker helpers return a selectable, authorized native listing node.
     */
    public function test_listing_uses_authorized_occurrence_nodes(): void {
        [$repository, $token, $target] = $this->fixture();
        set_config('author_policy', 'editable_with_provenance', 'local_filebank');
        $locator = json_decode(base64_decode($token), true);
        $file = get_file_storage()->get_file_by_hash($locator['pathnamehash']);
        $sourcecourse = \context::instance_by_id($file->get_contextid())->get_course_context();
        \local_filebank\local\catalog\indexer::refresh($sourcecourse->instanceid);
        \local_filebank\local\catalog\indexer::refresh($target->id);
        $listing = $repository->get_listing();
        $this->assertCount(1, $listing['list']);
        $node = $listing['list'][0];
        $this->assertSame('lesson.txt', $node['title']);
        $this->assertSame('Material author', $node['author']);
        $this->assertTrue($repository->file_is_accessible($node['source']));
    }

    /**
     * Search controls remain labelled fields inside the native picker form.
     */
    public function test_search_template_labels_native_fields(): void {
        global $PAGE;
        [$repository, , $target] = $this->fixture();
        $PAGE->set_context(\context_course::instance($target->id));
        $PAGE->set_url('/course/view.php', ['id' => $target->id]);
        $html = $repository->print_search();
        $this->assertStringNotContainsString('<form', $html);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $controls = $xpath->query('//input | //select');
        $this->assertSame(6, $controls->length);
        $this->assertSame(1, $xpath->query('//button[@type="submit"]')->length);
        foreach ($controls as $control) {
            $id = $control->getAttribute('id');
            $this->assertSame(1, $xpath->query('//label[@for="' . $id . '"]')->length);
        }
    }
}
