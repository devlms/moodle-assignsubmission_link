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
 * Backup of link submissions.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the links to each backed up submission.
 */
class backup_assignsubmission_link_subplugin extends backup_subplugin {
    /**
     * The links under the submission element.
     *
     * @return backup_subplugin_element
     */
    protected function define_submission_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $link = new backup_nested_element('submission_link', ['id'], ['url', 'sortorder', 'submission']);
        $subplugin->add_child($wrapper);
        $wrapper->add_child($link);
        $link->set_source_table('assignsubmission_link', ['submission' => backup::VAR_PARENTID], 'sortorder ASC');
        return $subplugin;
    }
}
