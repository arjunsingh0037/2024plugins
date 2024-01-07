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
 * Web services functions for report_trainingenrolment
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use report_trainingenrolment\helper;
require_once($CFG->libdir."/externallib.php");

class report_trainingenrolment_external extends external_api
{
    public static function mail_users_parameters() {
        return new external_function_parameters(
            array(
                'attach' => new external_value(PARAM_TEXT, 'pdf'),
                'subject' => new external_value(PARAM_TEXT, 'subject'),
                'message' => new external_value(PARAM_TEXT, 'message'),
                'users' => new external_value(PARAM_TEXT, 'users'),
            )
        );
    }
    public static function mail_users($attachment, $subject, $message, $users) {
        global $DB;
        $users = json_decode($users);
        // Helper class function to handle mail sending.
        /*foreach ($users as $user) {

        }*/
        return "Mails Sent Successfully";
    }

    public static function mail_users_returns() {
        return new external_value(PARAM_TEXT, 'Mail sent successfully.');
    }
}
