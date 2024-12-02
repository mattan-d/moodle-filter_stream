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

defined('MOODLE_INTERNAL') || die;
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
    /**
     * Filters the given text to replace video links with embedded iframes,
     * and optionally includes audio-only support based on course shortname.
     *
     * @param string $text The text to filter.
     * @param array $options Additional options for filtering (not used).
     * @return string The filtered text with video or audio links replaced.
     */
    public function filter($text, array $options = []) {
        global $PAGE, $USER;

        if (!is_string($text) || empty($text)) {
            // Return non-string data unmodified.
            return $text;
        }

        // Check if audio-only mode is required based on the course shortname.
        $audio = $this->is_audio_only_mode($PAGE);

        if (strpos($text, 'watch') === false) {
            // Return text unmodified if no relevant links are detected.
            return $text;
        }

        // Retrieve the player dimensions from the plugin configuration.
        $playerwidth = get_config('filter_stream', 'width');
        $playerheight = get_config('filter_stream', 'height');

        // Process anchor tag links.
        $text = $this->replace_links_with_media(
                $text,
                '/<a\s+[^>]*href=(["\'])(https:\/\/(\S+?)\/watch\/(\d+))\1[^>]*>.*?<\/a>/i',
                $playerwidth,
                $playerheight,
                $audio,
                $USER
        );

        // Process plain text links.
        $text = $this->replace_links_with_media(
                $text,
                '/(https:\/\/(\S+?)\/watch\/(\d+))/i',
                $playerwidth,
                $playerheight,
                $audio,
                $USER
        );

        return $text;
    }

    /**
     * Checks if audio-only mode should be enabled based on the course shortname.
     *
     * @param stdClass $page The current page object.
     * @return bool True if audio-only mode is enabled, false otherwise.
     */
    private function is_audio_only_mode($page) {
        if (!isset($page->course->shortname)) {
            return false;
        }

        $pattern = '/.*-(HM|HB|HW|HS)/';
        return (bool) preg_match($pattern, $page->course->shortname);
    }

    /**
     * Replaces matched video links in the text with embedded media (video or audio).
     *
     * @param string $text The text containing video links.
     * @param string $pattern The regex pattern to match video links.
     * @param int $width The width of the iframe player.
     * @param int $height The height of the iframe player.
     * @param bool $audio Whether to enable audio-only mode.
     * @param stdClass $user The current user object for JWT payload generation.
     * @return string The text with video or audio links replaced by iframe tags.
     */
    private function replace_links_with_media($text, $pattern, $width, $height, $audio, $user) {
        return preg_replace_callback($pattern, function($matches) use ($width, $height, $audio, $user) {
            $videoid = $matches[count($matches) - 1]; // Last matched group is the video ID.
            $host = $matches[count($matches) - 2];   // Second to last matched group is the domain.

            $payload = [
                    'identifier' => $videoid,
                    'fullname' => fullname($user),
                    'email' => $user->email,
            ];

            $jwt = \mod_stream\local\jwt_helper::encode(get_config('stream', 'accountid'), $payload);

            if ($audio) {
                // Return an audio-only iframe if audio mode is enabled.
                return html_writer::tag('h1', get_string('audio_recording', 'filter_stream')) .
                        html_writer::empty_tag('hr') .
                        html_writer::tag('iframe', '', [
                                'src' => "https://$host/embed-audio/$videoid?onlyaudio=1&token=$jwt",
                                'width' => '100%',
                                'height' => '150px',
                                'frameborder' => '0',
                                'allowfullscreen' => 'allowfullscreen',
                        ]) .
                        html_writer::empty_tag('hr');
            }

            // Return a video iframe for standard mode.
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