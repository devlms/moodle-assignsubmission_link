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
 * Strings for the link submission plugin.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['default'] = 'Enabled by default';
$string['default_help'] = 'New assignments take link submissions unless the teacher turns them off.';
$string['enabled'] = 'Links';
$string['enabled_help'] = 'Students hand in links, for example to a GitHub repository, a Google Doc, a Figma file or a video.';
$string['eventsubmissioncreated'] = 'Link submission created';
$string['eventsubmissionupdated'] = 'Link submission updated';
$string['invalidlink'] = 'Enter a full web link that starts with https:// or http://';
$string['invalidsites'] = 'This is not a site name: {$a}. Enter site names such as github.com, separated by commas.';
$string['link'] = 'Link';
$string['linknotallowed'] = 'Links must go to {$a}';
$string['linknumber'] = 'Link {$a}';
$string['links'] = 'Links';
$string['linksfilename'] = 'links.html';
$string['maxlinks'] = 'Links per submission';
$string['maxlinks_help'] = 'How many links a student can hand in, for example 2 for a code repository and a live demo.';
$string['pluginname'] = 'Link submissions';
$string['privacy:metadata:assignmentid'] = 'The assignment the links were handed in for';
$string['privacy:metadata:submissionid'] = 'The submission the links belong to';
$string['privacy:metadata:tablepurpose'] = 'Stores the links a student hands in for an assignment';
$string['privacy:metadata:url'] = 'The link';
$string['privacy:path'] = 'Links';
$string['sites'] = 'Allowed sites';
$string['sites_help'] = 'Leave empty to accept links to any site. To accept only some sites, enter them separated by commas, for example: github.com, docs.google.com. A site also allows its subdomains, so github.com allows gist.github.com.';
$string['sitesnote'] = 'Links to: {$a}';
$string['toomanylinks'] = 'You can hand in at most {$a} links.';
