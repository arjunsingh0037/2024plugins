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

use report_trainingenrolment\helper\adhoc_report_data_helper;

require_once(__DIR__.'/../../config.php');
require_once $CFG->libdir."/adminlib.php";

require_login();

$download   = optional_param('download', 0, PARAM_INT);

$url   = new moodle_url('/report/trainingenrolment/postcodes.php');
$title = get_string('postcodecompletions','report_trainingenrolment');

admin_externalpage_setup('report_postcodecompletions');
require_capability('report/trainingenrolment:view', context_system::instance());

if (!empty($download)) {
    adhoc_report_data_helper::download_postcode_excel();
}

$PAGE->set_heading($title);

echo $OUTPUT->header();

$report = new \report_trainingenrolment\output\postcode_report();
echo $OUTPUT->render($report);

echo $OUTPUT->footer();
