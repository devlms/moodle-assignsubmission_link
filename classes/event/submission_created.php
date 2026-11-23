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

namespace assignsubmission_link\event;

/**
 * A student created their link submission.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_created extends \mod_assign\event\submission_created {
    #[\Override]
    protected function init() {
        parent::init();
        $this->data['objecttable'] = 'assign_submission';
    }

    #[\Override]
    public static function get_name() {
        return get_string('eventsubmissioncreated', 'assignsubmission_link');
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->userid' created a submission with '{$this->other['linkcount']}' links " .
            "in the assignment with course module id '$this->contextinstanceid'" .
            (empty($this->other['groupid']) ? '.' : " for the group with id '{$this->other['groupid']}'.");
    }

    #[\Override]
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['linkcount'])) {
            throw new \coding_exception('The \'linkcount\' value must be set in other.');
        }
    }

    #[\Override]
    public static function get_objectid_mapping() {
        return ['db' => 'assign_submission', 'restore' => 'submission'];
    }
}
