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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;

/**
 * Privacy provider: the links are part of the student's submission, which mod_assign finds for us.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \mod_assign\privacy\assignsubmission_provider,
    \mod_assign\privacy\assignsubmission_user_provider {
    /** @var string table holding the links */
    private const TABLE = 'assignsubmission_link';

    /**
     * What is stored.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(self::TABLE, [
            'assignment' => 'privacy:metadata:assignmentid',
            'submission' => 'privacy:metadata:submissionid',
            'url' => 'privacy:metadata:url',
        ], 'privacy:metadata:tablepurpose');
        return $collection;
    }

    /**
     * Contexts come from mod_assign.
     *
     * @param int $userid
     * @param contextlist $contextlist
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {
    }

    /**
     * Students come from mod_assign.
     *
     * @param \mod_assign\privacy\useridlist $useridlist
     */
    public static function get_student_user_ids(\mod_assign\privacy\useridlist $useridlist) {
    }

    /**
     * Users come from mod_assign.
     *
     * @param \core_privacy\local\request\userlist $userlist
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
    }

    /**
     * Export the student's links.
     *
     * @param assign_plugin_request_data $exportdata
     */
    public static function export_submission_user_data(assign_plugin_request_data $exportdata) {
        global $DB;
        // Like core submission plugins, a teacher's export does not include students' work.
        if ($exportdata->get_user() !== null) {
            return;
        }
        $submission = $exportdata->get_pluginobject();
        $links = array_values($DB->get_fieldset_select(
            self::TABLE,
            'url',
            'submission = ? ORDER BY sortorder, id',
            [$submission->id]
        ));
        if (!$links) {
            return;
        }
        $path = array_merge($exportdata->get_subcontext(), [get_string('privacy:path', 'assignsubmission_link')]);
        writer::with_context($exportdata->get_context())->export_data($path, (object)['links' => $links]);
    }

    /**
     * Delete every link of the assignment.
     *
     * @param assign_plugin_request_data $requestdata
     */
    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        global $DB;
        $DB->delete_records(self::TABLE, ['assignment' => $requestdata->get_assignid()]);
    }

    /**
     * Delete one submission's links.
     *
     * @param assign_plugin_request_data $deletedata
     */
    public static function delete_submission_for_userid(assign_plugin_request_data $deletedata) {
        global $DB;
        $DB->delete_records(self::TABLE, [
            'assignment' => $deletedata->get_assignid(),
            'submission' => $deletedata->get_pluginobject()->id,
        ]);
    }

    /**
     * Delete the links of several submissions.
     *
     * @param assign_plugin_request_data $deletedata
     */
    public static function delete_submissions(assign_plugin_request_data $deletedata) {
        global $DB;
        if (empty($deletedata->get_submissionids())) {
            return;
        }
        [$sql, $params] = $DB->get_in_or_equal($deletedata->get_submissionids(), SQL_PARAMS_NAMED);
        $params['assignid'] = $deletedata->get_assignid();
        $DB->delete_records_select(self::TABLE, "assignment = :assignid AND submission $sql", $params);
    }
}
