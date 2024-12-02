<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once($CFG->dirroot . '/mod/stream/locallib.php');

/**
 * filter
 *
 * @package    filter_stream
 * @copyright  2023 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_stream extends moodle_text_filter {

    /**
     * Filters the given text to replace video links with embedded iframes.
     *
     * @param string $text The text to filter.
     * @param array $options Additional options for filtering (not used).
     * @return string The filtered text with video links replaced.
     */
    public function filter($text, array $options = []) {
        global $USER;

        if (!is_string($text) || empty($text)) {
            // Return non-string data unmodified.
            return $text;
        }

        if (strpos($text, 'watch') === false) {
            // Return text unmodified if no relevant links are detected.
            return $text;
        }

        // Retrieve the player dimensions from the plugin configuration.
        $playerwidth = get_config('filter_stream', 'width');
        $playerheight = get_config('filter_stream', 'height');

        // Process anchor tag links.
        $text = $this->replace_links_with_iframes(
                $text,
                '/<a\s+[^>]*href=(["\'])(https:\/\/(\S+?)\/watch\/(\d+))\1[^>]*>.*?<\/a>/i',
                $playerwidth,
                $playerheight,
                $USER
        );

        // Process plain text links.
        $text = $this->replace_links_with_iframes(
                $text,
                '/(https:\/\/(\S+?)\/watch\/(\d+))/i',
                $playerwidth,
                $playerheight,
                $USER
        );

        return $text;
    }

    /**
     * Replaces matched video links in the text with embedded iframe tags.
     *
     * @param string $text The text containing video links.
     * @param string $pattern The regex pattern to match video links.
     * @param int $width The width of the iframe player.
     * @param int $height The height of the iframe player.
     * @param stdClass $user The current user object for JWT payload generation.
     * @return string The text with video links replaced by iframe tags.
     */
    private function replace_links_with_iframes($text, $pattern, $width, $height, $user) {
        return preg_replace_callback($pattern, function($matches) use ($width, $height, $user) {
            $videoid = $matches[count($matches) - 1]; // Last matched group is the video ID.
            $host = $matches[count($matches) - 2];   // Second to last matched group is the domain.

            $payload = [
                    'identifier' => $videoid,
                    'fullname' => fullname($user),
                    'email' => $user->email,
            ];

            $jwt = \mod_stream\local\jwt_helper::encode(get_config('stream', 'accountid'), $payload);

            // Generate the iframe replacement.
            return html_writer::tag('iframe', '', [
                    'src' => "https://$host/embed/$videoid?token=$jwt",
                    'width' => $width,
                    'height' => $height,
                    'frameborder' => '0',
                    'allowfullscreen' => 'allowfullscreen',
            ]);
        }, $text);
    }
}
