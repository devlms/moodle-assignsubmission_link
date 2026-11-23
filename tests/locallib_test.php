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

namespace assignsubmission_link;

use mod_assign_test_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
require_once($CFG->dirroot . '/mod/assign/externallib.php');

/**
 * Tests for the link submission plugin.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assign_submission_link
 */
final class locallib_test extends \advanced_testcase {
    use mod_assign_test_generator;

    /**
     * An assignment that takes links, and an enrolled student.
     *
     * @param array $settings extra assignment settings
     * @return array [assign, student]
     */
    private function setup_assign(array $settings = []): array {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, $settings + [
            'assignsubmission_link_enabled' => 1,
            'assignsubmission_link_maxlinks' => 3,
        ]);
        return [$assign, $student];
    }

    /**
     * Save links as the student, the way the submission form does.
     *
     * @param \assign $assign
     * @param \stdClass $student
     * @param array $links
     * @return array [saved, notices]
     */
    private function submit(\assign $assign, \stdClass $student, array $links): array {
        $this->setUser($student);
        $notices = [];
        $saved = $assign->save_submission((object)['userid' => $student->id, 'assignsubmission_link' => $links], $notices);
        $this->setAdminUser();
        return [$saved, $notices];
    }

    /**
     * The links saved for the student, in order.
     *
     * @param \assign $assign
     * @param \stdClass $student
     * @return string[]
     */
    private function saved_links(\assign $assign, \stdClass $student): array {
        global $DB;
        $submission = $assign->get_user_submission($student->id, false);
        if (!$submission) {
            return [];
        }
        return array_values($DB->get_fieldset_select(
            'assignsubmission_link',
            'url',
            'submission = ? ORDER BY sortorder',
            [$submission->id]
        ));
    }

    public function test_settings_are_saved(): void {
        $this->resetAfterTest();
        [$assign] = $this->setup_assign([
            'assignsubmission_link_maxlinks' => 2,
            'assignsubmission_link_sites' => 'GitHub.com, https://docs.google.com/',
        ]);
        $plugin = $assign->get_submission_plugin_by_type('link');
        $this->assertNotEmpty($plugin->is_enabled());
        $this->assertEquals(2, $plugin->get_config('maxlinks'));
        $this->assertSame('github.com docs.google.com', $plugin->get_config('sites'));
    }

    public function test_student_submits_links_in_order(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign();
        [$saved] = $this->submit($assign, $student, ['https://github.com/me/essay', '', ' https://docs.google.com/d/1 ']);
        $this->assertTrue($saved);
        $this->assertSame(['https://github.com/me/essay', 'https://docs.google.com/d/1'], $this->saved_links($assign, $student));
        $submission = $assign->get_user_submission($student->id, false);
        $this->assertFalse($assign->get_submission_plugin_by_type('link')->is_empty($submission));
    }

    public function test_saving_again_replaces_the_links(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign();
        $this->submit($assign, $student, ['https://github.com/me/one', 'https://github.com/me/two']);
        $this->submit($assign, $student, ['https://github.com/me/three']);
        $this->assertSame(['https://github.com/me/three'], $this->saved_links($assign, $student));
    }

    public function test_link_to_another_site_is_refused(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign(['assignsubmission_link_sites' => 'github.com']);
        [$saved, $notices] = $this->submit($assign, $student, ['https://github.com/me/ok', 'https://pastebin.example/x']);
        $this->assertFalse($saved);
        $this->assertStringContainsString('github.com', implode(' ', $notices));
        $this->assertSame([], $this->saved_links($assign, $student));
    }

    public function test_more_links_than_allowed_are_refused(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign(['assignsubmission_link_maxlinks' => 1]);
        [$saved] = $this->submit($assign, $student, ['https://a.example/', 'https://b.example/']);
        $this->assertFalse($saved);
        $this->assertSame([], $this->saved_links($assign, $student));
    }

    public function test_blank_links_are_an_empty_submission(): void {
        $this->resetAfterTest();
        [$assign] = $this->setup_assign();
        $plugin = $assign->get_submission_plugin_by_type('link');
        $this->assertTrue($plugin->submission_is_empty((object)['assignsubmission_link' => ['', '  ']]));
        $this->assertTrue($plugin->submission_is_empty((object)[]));
        $this->assertFalse($plugin->submission_is_empty((object)['assignsubmission_link' => ['', 'https://a.example/']]));
    }

    public function test_links_are_shown_safely_and_open_in_a_new_tab(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign();
        $this->submit($assign, $student, ['https://example.org/?q=%22%3E%3Cscript%3E', 'https://github.com/me/repo']);
        $submission = $assign->get_user_submission($student->id, false);
        $plugin = $assign->get_submission_plugin_by_type('link');

        $showviewlink = true;
        $html = $plugin->view_summary($submission, $showviewlink);
        $this->assertFalse($showviewlink);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('href="https://github.com/me/repo"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('noopener', $html);
        $this->assertStringContainsString('github.com/me/repo</a>', $html);
        $this->assertSame($html, $plugin->view($submission));
    }

    public function test_download_all_includes_a_links_page(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign();
        $this->submit($assign, $student, ['https://github.com/me/repo']);
        $submission = $assign->get_user_submission($student->id, false);
        $files = $assign->get_submission_plugin_by_type('link')->get_files($submission, $student);
        $this->assertCount(1, $files);
        $content = reset($files)[0];
        $this->assertStringEndsWith('.html', key($files));
        $this->assertStringContainsString('href="https://github.com/me/repo"', $content);
    }

    public function test_new_attempt_can_start_from_the_previous_links(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign();
        $this->submit($assign, $student, ['https://github.com/me/v1']);
        $old = $assign->get_user_submission($student->id, false);
        $new = $assign->get_user_submission($student->id, true, 1);
        $assign->get_submission_plugin_by_type('link')->copy_submission($old, $new);
        global $DB;
        $this->assertSame(['https://github.com/me/v1'], array_values($DB->get_fieldset_select(
            'assignsubmission_link',
            'url',
            'submission = ?',
            [$new->id]
        )));
    }

    public function test_message_summary_lists_the_links(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign();
        $this->submit($assign, $student, ['https://github.com/me/repo']);
        $submission = $assign->get_user_submission($student->id, false);
        [$text, $html] = $assign->get_submission_plugin_by_type('link')->submission_summary_for_messages($submission);
        $this->assertStringContainsString('https://github.com/me/repo', $text);
        $this->assertStringEndsWith("\n", $text);
        $this->assertStringContainsString('href="https://github.com/me/repo"', $html);
    }

    public function test_web_service_submission(): void {
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign();
        $this->setUser($student);
        $result = \mod_assign_external::save_submission(
            $assign->get_instance()->id,
            ['assignsubmission_link' => ['https://github.com/me/app']]
        );
        $this->assertSame([], $result);
        $this->assertSame(['https://github.com/me/app'], $this->saved_links($assign, $student));
    }

    public function test_deleting_the_assignment_deletes_the_links(): void {
        global $DB;
        $this->resetAfterTest();
        [$assign, $student] = $this->setup_assign();
        $this->submit($assign, $student, ['https://github.com/me/repo']);
        $assign->delete_instance();
        $this->assertSame(0, $DB->count_records('assignsubmission_link'));
    }
}
