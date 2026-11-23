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
 * Generator for link submissions: create_submission(['link' => [...]]).
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/mod/assign/tests/generator/assignsubmission_subplugin_generator.php");

/**
 * Generator for link submissions.
 */
class assignsubmission_link_generator extends assignsubmission_subplugin_generator {
    /**
     * Add the links to the submission data.
     *
     * @param stdClass $submission
     * @param assign $assign
     * @param array $data with 'link' as a list of links, or links separated by spaces in Behat
     */
    public function add_submission_data(stdClass $submission, assign $assign, array $data): void {
        if (array_key_exists('link', $data)) {
            $links = is_array($data['link']) ? $data['link'] : preg_split('/\s+/', trim($data['link']));
            $submission->assignsubmission_link = array_values($links);
        }
    }
}
