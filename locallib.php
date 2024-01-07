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
 * Event class for report_trainingenrolment
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Handling Events
 */
class report_trainingenrolment_events {
    public static function report_viewed($userid = 0) {
        $context = context_system::instance();
        $event = report_trainingenrolment\event\report_viewed::create(
            array(
                "context"  => $context,
                "relateduserid" => $userid
            )
        );
        $event->trigger();
    }

    public static function report_downloaded($userid = 0) {
        $context = context_system::instance();
        $event = report_trainingenrolment\event\report_downloaded::create(
            array(
                "context"  => $context,
                "relateduserid" => $userid
            )
        );
        $event->trigger();
    }

    public static function mail_sent($usermail) {
        $context = context_system::instance();
        $event = report_trainingenrolment\event\mail_sent::create(
            array(
                "context"  => $context,
                "other" => array(
                    "emailid" => $usermail,
                )
            )
        );
        $event->trigger();
    }

}
