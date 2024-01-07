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
 *
 *
 * @package   report_trainingenrolment
 * @category  Report plugin
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_trainingenrolment\table\completion_participant_details;

require_once(__DIR__.'/../../config.php');
require_once $CFG->libdir."/adminlib.php";

require_login();

$download   = optional_param('download', '', PARAM_ALPHA);

$url   = new moodle_url('/report/trainingenrolment/participants.php');
$title = get_string('participantdetails','report_trainingenrolment');

admin_externalpage_setup('report_participantdetails');
$PAGE->set_heading($title);


require_capability('report/trainingenrolment:view', context_system::instance());

$table = new completion_participant_details($url);
$table->is_downloading($download, 'participants_completion_list', 'participants');
//$table->initialbars(true);

if ( ! $table->is_downloading()) {
    echo $OUTPUT->header();
    echo html_writer::start_div('card');
    echo html_writer::tag('h3', $title, ['class' => 'card-header']);
    echo html_writer::start_div('card-body');
}

echo $table->out(50, true);

if ( ! $table->is_downloading()) {
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo $OUTPUT->footer();
}
