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
 * Training Enrolment Report Date Filter Form
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_trainingenrolment\form;

use moodleform;

class date_filter extends moodleform
{
    protected function definition()
    {
        global $DB;
        $mform = $this->_form;
        $attributes = [
            'startyear' => 2020,
            'stopyear'  => 2022
        ];
        $mform->addElement('header', 'filterheader', get_string('datefilter', 'report_trainingenrolment'));
        $mform->addElement('date_selector', 'dateselect', get_string('datelabel', 'report_trainingenrolment'), $attributes);
        $mform->addElement('submit', 'submitbutton', get_string('submitbtn', 'report_trainingenrolment'));
    }
}
