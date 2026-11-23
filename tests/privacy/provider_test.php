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

namespace assignsubmission_link\privacy;

use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Privacy tests for link submissions.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignsubmission_link\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    use \mod_assign_test_generator;

    /**
     * Submit links for a student.
     *
     * @param \assign $assign
     * @param \stdClass $student
     * @param array $links
     * @return \stdClass the submission
     */
    private function submit(\assign $assign, \stdClass $student, array $links): \stdClass {
        $this->setUser($student);
        $notices = [];
        $assign->save_submission((object)['userid' => $student->id, 'assignsubmission_link' => $links], $notices);
        $this->setAdminUser();
        return $assign->get_user_submission($student->id, false);
    }

    /**
     * Count the links stored for an assignment.
     *
     * @param \assign $assign
     * @return int
     */
    private function count_links(\assign $assign): int {
        global $DB;
        return $DB->count_records('assignsubmission_link', ['assignment' => $assign->get_instance()->id]);
    }

    public function test_metadata(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('assignsubmission_link'));
        $this->assertCount(1, $collection->get_collection());
    }

    public function test_export_lists_the_students_links(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, ['assignsubmission_link_enabled' => 1, 'assignsubmission_link_maxlinks' => 2]);
        $submission = $this->submit($assign, $student, ['https://github.com/me/a', 'https://github.com/me/b']);

        $context = $assign->get_context();
        provider::export_submission_user_data(new assign_plugin_request_data($context, $assign, $submission, ['Attempt 1']));
        $data = writer::with_context($context)->get_data(['Attempt 1', get_string('privacy:path', 'assignsubmission_link')]);
        $this->assertSame(['https://github.com/me/a', 'https://github.com/me/b'], $data->links);
    }

    public function test_delete_for_one_student_and_for_the_context(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $one = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $two = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $three = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, ['assignsubmission_link_enabled' => 1]);
        $submission = $this->submit($assign, $one, ['https://a.example/1']);
        $this->submit($assign, $two, ['https://a.example/2']);
        $this->submit($assign, $three, ['https://a.example/3']);
        $context = $assign->get_context();

        provider::delete_submission_for_userid(new assign_plugin_request_data($context, $assign, $submission, [], $one));
        $this->assertSame(2, $this->count_links($assign));

        $request = new assign_plugin_request_data($context, $assign);
        $request->set_userids([$two->id]);
        $request->populate_submissions_and_grades();
        provider::delete_submissions($request);
        $this->assertSame(1, $this->count_links($assign));

        provider::delete_submission_for_context(new assign_plugin_request_data($context, $assign));
        $this->assertSame(0, $this->count_links($assign));
    }
}
