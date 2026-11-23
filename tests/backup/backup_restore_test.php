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

namespace assignsubmission_link\backup;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/phpunit/classes/restore_date_testcase.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Backup and restore of link submissions.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_assignsubmission_link_subplugin
 * @covers     \restore_assignsubmission_link_subplugin
 */
final class backup_restore_test extends \restore_date_testcase {
    use \mod_assign_test_generator;

    public function test_links_come_back_with_the_course(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, [
            'assignsubmission_link_enabled' => 1,
            'assignsubmission_link_maxlinks' => 2,
            'assignsubmission_link_sites' => 'github.com',
        ]);
        $this->setUser($student);
        $notices = [];
        $assign->save_submission((object)['userid' => $student->id,
            'assignsubmission_link' => ['https://github.com/me/app', 'https://github.com/me/app/pull/2']], $notices);
        $this->setAdminUser();

        $newcourseid = $this->backup_and_restore($course);

        $newassign = $DB->get_record('assign', ['course' => $newcourseid], '*', MUST_EXIST);
        $links = $DB->get_records('assignsubmission_link', ['assignment' => $newassign->id], 'sortorder');
        $this->assertSame(
            ['https://github.com/me/app', 'https://github.com/me/app/pull/2'],
            array_values(array_column($links, 'url'))
        );
        $submission = $DB->get_record('assign_submission', ['assignment' => $newassign->id, 'userid' => $student->id]);
        $this->assertEquals([$submission->id], array_values(array_unique(array_column($links, 'submission'))));
        $this->assertSame('github.com', $DB->get_field('assign_plugin_config', 'value', [
            'assignment' => $newassign->id, 'plugin' => 'link', 'subtype' => 'assignsubmission', 'name' => 'sites']));
    }
}
