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

/**
 * Tests for the link rules.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignsubmission_link\link
 */
final class link_test extends \basic_testcase {
    public function test_sites_are_read_from_a_list(): void {
        $this->assertSame(
            ['github.com', 'docs.google.com', 'figma.com'],
            link::parse_sites(" GitHub.com, docs.google.com\nhttps://www.figma.com/ , github.com, not a site, ")
        );
        $this->assertSame([], link::parse_sites(''));
    }

    public function test_any_web_link_when_no_sites_are_set(): void {
        $this->assertTrue(link::is_allowed('https://example.org/my/project', []));
        $this->assertTrue(link::is_allowed('http://intranet.school.example/page?x=1', []));
    }

    public function test_only_web_links_are_accepted(): void {
        foreach (
            ['javascript:alert(1)', 'ftp://example.org/file', 'github.com/me/repo', 'https://', 'mailto:a@b.example',
                'data:text/html,hi', 'https://exa mple.org/', ''] as $bad
        ) {
            $this->assertFalse(link::is_allowed($bad, []), $bad);
        }
    }

    public function test_sites_match_the_domain_and_its_subdomains_only(): void {
        $sites = ['github.com', 'docs.google.com'];
        $this->assertTrue(link::is_allowed('https://github.com/me/repo', $sites));
        $this->assertTrue(link::is_allowed('https://gist.github.com/me/1', $sites));
        $this->assertTrue(link::is_allowed('https://DOCS.google.com/document/d/1', $sites));
        $this->assertFalse(link::is_allowed('https://google.com/', $sites));
        $this->assertFalse(link::is_allowed('https://github.com.evil.example/me', $sites));
        $this->assertFalse(link::is_allowed('https://notgithub.com/me', $sites));
        $this->assertFalse(link::is_allowed('https://github.com@evil.example/me', $sites));
    }

    public function test_short_label(): void {
        $this->assertSame('github.com/me/repo', link::label('https://www.github.com/me/repo/'));
        $label = link::label('https://docs.google.com/document/d/' . str_repeat('a', 200) . '/edit');
        $this->assertLessThanOrEqual(60, \core_text::strlen($label));
        $this->assertStringStartsWith('docs.google.com/document/', $label);
    }
}
