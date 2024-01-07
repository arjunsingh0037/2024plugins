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
 * Training enrolment report observers.
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * this file should be used for all training enrolment report event definitions and handers.
 */
defined('MOODLE_INTERNAL') || die();

// List of observers.
$observers = [
    [
        'eventname' => 'core\event\report_viewed',
        'callback'  => '\report_trainingenrolment\event\report_viewed',
        'internal'  => false,
    ],

    [
        'eventname' => 'core\event\report_downloaded',
        'callback'  => '\report_trainingenrolment\event\report_downloaded',
        'internal'  => false,
    ],

    [
        'eventname' => 'core\event\mail_sent',
        'callback'  => '\report_trainingenrolment\event\mail_sent',
        'internal'  => false,
    ],
];
