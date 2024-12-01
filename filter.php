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
     * Filter the text content to embed videos from URLs.
     *
     * @param string $text The text content to filter.
     * @param array $options An array of options (not used in this method).
     * @return string The filtered text content.
     */
    public function filter($text, array $options = []) {
        global $USER;

        if (!is_string($text) || empty($text)) {
            // Non string data can not be filtered anyway.
            return $text;
        }

        if (strpos($text, 'watch') !== false) {

            $playerwidth = get_config('filter_stream', 'width');
            $playerheight = get_config('filter_stream', 'height');

            // Define the pattern for matching URLs with any domain in text.
            $pattern = '/<a\s+[^>]*href=(["\'])(https:\/\/(\S+?)\/watch\/(\d+))\1[^>]*>.*?<\/a>/i';

            if (preg_match($pattern, $text, $matches)) {
                $videoId = $matches[4];
                $payload = [
                        'identifier' => $videoId,
                        'fullname' => fullname($USER),
                        'email' => $USER->email,
                ];

                $jwt = \mod_stream\local\jwt_helper::encode(get_config('stream', 'accountid'), $payload);

                // Replace matched URLs with the video tag.
                $replacement = '<iframe src="https://$3/embed/$4?token=' . $jwt .
                        '" width="' . $playerwidth . '" height="' . $playerheight . '" frameborder="0" allowfullscreen></iframe>';
                $text = preg_replace($pattern, $replacement, $text);

                // Define the pattern for matching plain URLs with any domain in text.
                $plainpattern = '/(https:\/\/(\S+?)\/watch\/(\d+))/i';

                // Replace matched plain URLs with the video tag.
                $plainreplacement = '<iframe src="https://$3/embed/$4?token=' . $jwt .
                        '" width="' . $playerwidth . '" height="' . $playerheight . '" frameborder="0" allowfullscreen></iframe>';
                $text = preg_replace($plainpattern, $plainreplacement, $text);
            }

            return $text;
        }

        return $text;
    }
}
