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

/**
 * filter
 *
 * @package    filter_stream
 * @copyright  2023 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_stream extends moodle_text_filter {
    private $height;
    private $width;

    public function __construct($context, array $localconfig) {
        parent::__construct($context, $localconfig);
        global $CFG;
    }

    public function filter($text, array $options = array()) {

        if (!is_string($text) or empty($text)) {
            // non string data can not be filtered anyway
            return $text;
        }

        if (strpos($text, 'watch') !== false) {

            // Define the pattern for matching URLs with any domain in text
            $pattern = '/<a\s+[^>]*href=(["\'])(https:\/\/(\S+?)\/watch\/(\d+))\1[^>]*>.*?<\/a>/i';

            // Replace matched URLs with the video tag
            $replacement = '<video controls width="100%">
                        <source src="https://$3/cdn/$4/embed.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>';

            $text = preg_replace($pattern, $replacement, $text);

            return $text;
        }

        return $text;
    }
}
