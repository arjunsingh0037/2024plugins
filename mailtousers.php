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
 * Custom page to handle manual mail sending process
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use report_trainingenrolment\helper;

require_once(__DIR__.'/../../config.php');
$id   = optional_param('id', 0, PARAM_INT);
global $DB, $USER, $CFG;

$url = new moodle_url('/report/trainingenrolment/mailtousers.php');
$main_page_url = new moodle_url('/report/trainingenrolment/index.php');

$context       = context_system::instance();
require_login();
$context = context_system::instance();
$PAGE->set_context($context);

$title = get_string('bulk_mail', 'report_trainingenrolment');
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add(get_string('training_report', 'report_trainingenrolment'), $main_page_url);
$PAGE->navbar->add(get_string('send_mailtousers', 'report_trainingenrolment'));

require_capability('moodle/site:config', $context);
$PAGE->set_url($url);
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/report/trainingenrolment/styles.css'));
echo $OUTPUT->header();

$hash = array(
    'actions' => true,
    'main_url' => $main_page_url
);
echo $OUTPUT->render_from_template('report_trainingenrolment/mail_form', $hash);
$PAGE->requires->js_call_amd('report_trainingenrolment/tabledata', 'mailTemp');
echo $OUTPUT->footer();