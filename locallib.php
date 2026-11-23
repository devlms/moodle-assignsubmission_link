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
 * Link submissions: students hand in links instead of files.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_link\link;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * The link submission plugin.
 */
class assign_submission_link extends assign_submission_plugin {
    /** @var int most links a teacher can ask for */
    public const MAX_LINKS = 10;

    /** @var string table holding the links */
    private const TABLE = 'assignsubmission_link';

    /**
     * Name of the plugin, shown as the column heading in the grading table.
     *
     * @return string
     */
    public function get_name() {
        return get_string('links', 'assignsubmission_link');
    }

    /**
     * How many links a student may hand in.
     *
     * @return int
     */
    private function max_links(): int {
        return max(1, min(self::MAX_LINKS, (int)$this->get_config('maxlinks')));
    }

    /**
     * The sites links must go to; empty for any site.
     *
     * @return string[]
     */
    private function sites(): array {
        return link::parse_sites((string)$this->get_config('sites'));
    }

    /**
     * The saved links of a submission, in order.
     *
     * @param int $submissionid
     * @return string[]
     */
    private function get_links(int $submissionid): array {
        global $DB;
        return array_values($DB->get_fieldset_select(
            self::TABLE,
            'url',
            'submission = ? ORDER BY sortorder, id',
            [$submissionid]
        ));
    }

    /**
     * The links from submitted data, trimmed, without blanks.
     *
     * @param stdClass $data
     * @return string[]
     */
    private function links_from(stdClass $data): array {
        $links = (array)($data->assignsubmission_link ?? []);
        return array_values(array_filter(array_map(fn($link) => trim((string)$link), $links), fn($link) => $link !== ''));
    }

    /**
     * Assignment settings: how many links, and which sites.
     *
     * @param MoodleQuickForm $mform
     */
    public function get_settings(MoodleQuickForm $mform) {
        $options = array_combine(range(1, self::MAX_LINKS), range(1, self::MAX_LINKS));
        $mform->addElement('select', 'assignsubmission_link_maxlinks', get_string('maxlinks', 'assignsubmission_link'), $options);
        $mform->setDefault('assignsubmission_link_maxlinks', $this->max_links());
        $mform->addHelpButton('assignsubmission_link_maxlinks', 'maxlinks', 'assignsubmission_link');
        $mform->hideIf('assignsubmission_link_maxlinks', 'assignsubmission_link_enabled', 'notchecked');

        $mform->addElement('text', 'assignsubmission_link_sites', get_string('sites', 'assignsubmission_link'), ['size' => 50]);
        $mform->setType('assignsubmission_link_sites', PARAM_TEXT);
        $mform->setDefault('assignsubmission_link_sites', implode(', ', $this->sites()));
        $mform->addHelpButton('assignsubmission_link_sites', 'sites', 'assignsubmission_link');
        $mform->hideIf('assignsubmission_link_sites', 'assignsubmission_link_enabled', 'notchecked');
    }

    /**
     * Check the allowed sites (Moodle 5.0 and later call this; 4.5 drops words that are not sites on save).
     *
     * @param array $data
     * @param array $files
     * @return array errors by field name
     */
    public function settings_validation(array $data, array $files): array {
        if (empty($data['assignsubmission_link_enabled'])) {
            return [];
        }
        $words = preg_split('/[\s,;]+/', (string)($data['assignsubmission_link_sites'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $word) {
            if (link::site_of($word) === null) {
                return ['assignsubmission_link_sites' => get_string('invalidsites', 'assignsubmission_link', s($word))];
            }
        }
        return [];
    }

    /**
     * Save the assignment settings.
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('maxlinks', max(1, min(self::MAX_LINKS, (int)($data->assignsubmission_link_maxlinks ?? 1))));
        $this->set_config('sites', implode(' ', link::parse_sites((string)($data->assignsubmission_link_sites ?? ''))));
        return true;
    }

    /**
     * The link fields on the student's submission form.
     *
     * @param mixed $submission
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        $max = $this->max_links();
        $sites = $this->sites();
        $saved = $submission ? $this->get_links((int)$submission->id) : [];
        $error = $sites ? get_string('linknotallowed', 'assignsubmission_link', implode(', ', $sites))
            : get_string('invalidlink', 'assignsubmission_link');

        for ($i = 0; $i < $max; $i++) {
            $name = "assignsubmission_link[$i]";
            $label = $max === 1 ? get_string('link', 'assignsubmission_link')
                : get_string('linknumber', 'assignsubmission_link', $i + 1);
            $mform->addElement('text', $name, $label, ['size' => 60, 'inputmode' => 'url', 'placeholder' => 'https://']);
            $mform->setType($name, PARAM_RAW_TRIMMED);
            $mform->addRule($name, $error, 'callback', fn($value) => link::is_allowed(trim((string)$value), $sites), 'server');
            $mform->setDefault($name, $saved[$i] ?? '');
        }
        if ($sites) {
            $mform->addElement(
                'static',
                'assignsubmission_link_sites_note',
                '',
                get_string('sitesnote', 'assignsubmission_link', s(implode(', ', $sites)))
            );
        }
        return true;
    }

    /**
     * Save the links. Refuses the whole set when one link is not allowed, so nothing half saved is kept.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $DB, $USER;

        $links = $this->links_from($data);
        $max = $this->max_links();
        if (count($links) > $max) {
            $this->set_error(get_string('toomanylinks', 'assignsubmission_link', $max));
            return false;
        }
        $sites = $this->sites();
        foreach ($links as $url) {
            if (!link::is_allowed($url, $sites)) {
                $this->set_error($sites ? get_string('linknotallowed', 'assignsubmission_link', implode(', ', $sites))
                    : get_string('invalidlink', 'assignsubmission_link'));
                return false;
            }
        }

        $existed = $DB->record_exists(self::TABLE, ['submission' => $submission->id]);
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records(self::TABLE, ['submission' => $submission->id]);
        foreach ($links as $i => $url) {
            $DB->insert_record(self::TABLE, (object)[
                'assignment' => $this->assignment->get_instance()->id,
                'submission' => $submission->id,
                'url' => $url,
                'sortorder' => $i,
            ]);
        }
        $transaction->allow_commit();

        $groupid = 0;
        $groupname = null;
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupid = (int)$submission->groupid;
            $groupname = $DB->get_field('groups', 'name', ['id' => $groupid], MUST_EXIST);
        }
        $params = [
            'context' => $this->assignment->get_context(),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => $submission->id,
            'other' => [
                'submissionid' => $submission->id,
                'submissionattempt' => $submission->attemptnumber,
                'submissionstatus' => $submission->status,
                'linkcount' => count($links),
                'groupid' => $groupid,
                'groupname' => $groupname,
            ],
        ];
        if (!empty($submission->userid) && $submission->userid != $USER->id) {
            $params['relateduserid'] = $submission->userid;
        }
        if ($this->assignment->is_blind_marking()) {
            $params['anonymous'] = 1;
        }
        $event = $existed ? \assignsubmission_link\event\submission_updated::create($params)
            : \assignsubmission_link\event\submission_created::create($params);
        $event->set_assign($this->assignment);
        $event->trigger();
        return true;
    }

    /**
     * The links as a list of safe anchors that open in a new tab.
     *
     * @param string[] $links
     * @return string HTML
     */
    private function render_links(array $links): string {
        if (!$links) {
            return '';
        }
        $items = '';
        foreach ($links as $url) {
            $items .= html_writer::tag('li', html_writer::link(
                $url,
                s(link::label($url)),
                ['target' => '_blank', 'rel' => 'noopener noreferrer', 'title' => $url]
            ));
        }
        return html_writer::tag('ul', $items, ['class' => 'assignsubmission_link list-unstyled mb-0']);
    }

    /**
     * Links in the grading table and on the status page. They are short, so there is no "view" page.
     *
     * @param stdClass $submission
     * @param bool $showviewlink
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        $showviewlink = false;
        return $this->render_links($this->get_links((int)$submission->id));
    }

    /**
     * The full submission.
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        return $this->render_links($this->get_links((int)$submission->id));
    }

    /**
     * A links page for "Download all submissions" and portfolios.
     *
     * @param stdClass $submission
     * @param stdClass $user
     * @return array filename => [content]
     */
    public function get_files(stdClass $submission, stdClass $user) {
        $links = $this->get_links((int)$submission->id);
        if (!$links) {
            return [];
        }
        $items = '';
        foreach ($links as $url) {
            $items .= html_writer::tag('li', html_writer::link($url, s($url)));
        }
        $title = s(get_string('links', 'assignsubmission_link'));
        $page = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $title . '</title></head><body><ul>' .
            $items . '</ul></body></html>';
        return [get_string('linksfilename', 'assignsubmission_link') => [$page]];
    }

    /**
     * The links in the "submission received" messages (Moodle 5.0 and later).
     *
     * @param stdClass $submission
     * @return string[] plain text and HTML
     */
    public function submission_summary_for_messages(stdClass $submission): array {
        $links = $this->get_links((int)$submission->id);
        if (!$links) {
            return ['', ''];
        }
        return [implode("\n", $links) . "\n", $this->render_links($links)];
    }

    /**
     * Whether the saved submission has no links.
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        global $DB;
        return !$DB->record_exists(self::TABLE, ['submission' => $submission->id]);
    }

    /**
     * Whether submitted data has no links.
     *
     * @param stdClass $data
     * @return bool
     */
    public function submission_is_empty(stdClass $data) {
        return !$this->links_from($data);
    }

    /**
     * Copy the links to a new attempt.
     *
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     * @return bool
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;
        foreach ($DB->get_records(self::TABLE, ['submission' => $sourcesubmission->id]) as $record) {
            unset($record->id);
            $record->submission = $destsubmission->id;
            $DB->insert_record(self::TABLE, $record);
        }
        return true;
    }

    /**
     * Remove the links when the student removes the submission.
     *
     * @param stdClass $submission
     * @return bool
     */
    public function remove(stdClass $submission) {
        global $DB;
        if (!empty($submission->id)) {
            $DB->delete_records(self::TABLE, ['submission' => $submission->id]);
        }
        return true;
    }

    /**
     * Delete all links of the assignment.
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records(self::TABLE, ['assignment' => $this->assignment->get_instance()->id]);
        return true;
    }

    /**
     * Links accepted by mod_assign_save_submission (the Moodle app and other web service clients).
     *
     * @return array
     */
    public function get_external_parameters() {
        return [
            'assignsubmission_link' => new external_multiple_structure(
                new external_value(PARAM_RAW_TRIMMED, 'A link, http or https'),
                'The links for this submission, in order',
                VALUE_OPTIONAL
            ),
        ];
    }

    /**
     * Settings shown to web service clients.
     *
     * @return array
     */
    public function get_config_for_external() {
        return (array)$this->get_config();
    }
}
