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
 * Restore of link submissions.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores the links of each submission.
 */
class restore_assignsubmission_link_subplugin extends restore_subplugin {
    /**
     * Paths to restore.
     *
     * @return restore_path_element[]
     */
    protected function define_submission_subplugin_structure() {
        return [new restore_path_element($this->get_namefor('submission'), $this->get_pathfor('/submission_link'))];
    }

    /**
     * Restore one link.
     *
     * @param array $data
     */
    public function process_assignsubmission_link_submission($data) {
        global $DB;
        $data = (object)$data;
        unset($data->id);
        $data->assignment = $this->get_new_parentid('assign');
        $data->submission = $this->get_mappingid('submission', $data->submission);
        if ($data->submission) {
            $DB->insert_record('assignsubmission_link', $data);
        }
    }
}
