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
 * Rules for submitted links and the list of allowed sites.
 *
 * @package    assignsubmission_link
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link {
    /** @var int longest link we keep */
    public const MAX_LENGTH = 2048;

    /** @var int longest label shown for a link */
    public const LABEL_LENGTH = 60;

    /**
     * Read a list of sites typed by a teacher: domains separated by commas, spaces or new lines.
     *
     * "https://www.figma.com/" becomes "figma.com". Words that are not domains are dropped.
     *
     * @param string $text
     * @return string[] lower case domains, without duplicates, in the order given
     */
    public static function parse_sites(string $text): array {
        $sites = [];
        foreach (preg_split('/[\s,;]+/', \core_text::strtolower($text), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $site = self::site_of($word);
            if ($site !== null && !in_array($site, $sites, true)) {
                $sites[] = $site;
            }
        }
        return $sites;
    }

    /**
     * The domain a typed word stands for, or null when it is not one.
     *
     * @param string $word
     * @return string|null
     */
    public static function site_of(string $word): ?string {
        $word = \core_text::strtolower(trim($word));
        if (str_contains($word, '://')) {
            $word = (string)parse_url($word, PHP_URL_HOST);
        }
        $word = preg_replace('/^www\./', '', rtrim($word, '/'));
        $label = '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?';
        if (strlen($word) > 253 || !preg_match("/^(?:{$label}\.)+[a-z]{2,63}$/", $word)) {
            return null;
        }
        return $word;
    }

    /**
     * Whether a link is a web link (http or https) to one of the sites, or to any site when none are set.
     *
     * A site also allows its subdomains: github.com allows gist.github.com, not github.com.example.org.
     *
     * @param string $url
     * @param string[] $sites from parse_sites()
     * @return bool
     */
    public static function is_allowed(string $url, array $sites): bool {
        if ($url === '' || strlen($url) > self::MAX_LENGTH || preg_match('/\s/', $url)) {
            return false;
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($url);
        if (
            !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])
                || isset($parts['user']) || isset($parts['pass'])
        ) {
            return false;
        }
        if (!$sites) {
            return true;
        }
        $host = \core_text::strtolower($parts['host']);
        foreach ($sites as $site) {
            if ($host === $site || str_ends_with($host, '.' . $site)) {
                return true;
            }
        }
        return false;
    }

    /**
     * A short label for a link: no scheme, no "www.", cut to LABEL_LENGTH characters.
     *
     * @param string $url
     * @return string plain text, not escaped
     */
    public static function label(string $url): string {
        $label = rtrim(preg_replace('~^https?://(www\.)?~i', '', $url), '/');
        if (\core_text::strlen($label) > self::LABEL_LENGTH) {
            $label = \core_text::substr($label, 0, self::LABEL_LENGTH - 1) . '…';
        }
        return $label;
    }
}
