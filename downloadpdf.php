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
 * Training Enrolment Report PDF Extract
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use report_trainingenrolment\helper;
require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once (__DIR__ . '/vendor/autoload.php');
require_once("locallib.php");

$currentday = userdate(time(), '%d/%m/%Y');
$dated = optional_param('d', $currentday, PARAM_TEXT);
$today = strtotime(date('d-m-Y'));

$selected_raw = strtotime(str_replace('/', '-', $dated));
$yesterday = $selected_raw - 86400;

global $DB, $PAGE, $OUTPUT;
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->requires->css('/report/trainingenrolment/styles.css');

//Defines states array
$col_state = ['ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA'];
//Defines range for 3-7 days,7-14 days, 14plus days and not started before 14 days
$inactive_range = ['37', '714', '14plus', 'non14'];
//User types
$user_type = [0,1,2,3,4,5];
$total_en = $total37 = $total714 = $total14plus = $totalnon14 = 0;

//Gets core module values for enrolment table
$core_enrolments_values = helper::get_table_records($selected_raw, 'enrolled', 1, '0','admin_cm',0);
$full_enrolments_values = helper::get_table_records($selected_raw, 'enrolled', 1, '0','full_cm', $user_type);
$pfizer_enrolments_values = helper::get_table_records($selected_raw, 'enrolled', 1, '0','pfizer', $user_type);
$astra_enrolments_values = helper::get_table_records($selected_raw, 'enrolled', 1, '0','astrazeneca', $user_type);
$moderna_enrolments_values = helper::get_table_records($selected_raw, 'enrolled', 1, '0','moderna', $user_type);
$pfizer2_enrolments_values = helper::get_table_records($selected_raw, 'enrolled', 1, '0','pfizer2', $user_type);

//Gets core module values for incomplete table
foreach ($inactive_range as $range) {
    $core_incomplete_allvalues['admin_core_incomplete'.$range] = helper::get_table_records($selected_raw, 'completed', 0, $range, 'admin_cm');
    $full_incomplete_allvalues['full_core_incomplete'.$range] = helper::get_table_records($selected_raw, 'completed', 0, $range, 'full_cm', $user_type);
}
$admin_completion_values = helper::get_table_records($selected_raw, 'completed', 1, 'mean', 'admin_cm',0);
$core_completion_values = helper::get_table_records($selected_raw, 'completed', 1, 'mean', 'full_cm', $user_type);
$pfizer_completion_values = helper::get_table_records($selected_raw, 'completed', 1, 'mean', 'pfizer', $user_type);
$astra_completion_values = helper::get_table_records($selected_raw, 'completed', 1, 'mean', 'astrazeneca', $user_type);
$moderna_completion_values = helper::get_table_records($selected_raw, 'completed', 1, 'mean', 'moderna', $user_type);
$pfizer2_completion_values = helper::get_table_records($selected_raw, 'completed', 1, 'mean', 'pfizer2', $user_type);

$templatecontext = array(
    'actions' => false,
    'selecteddate' => $dated,
    'yesterday' => userdate($yesterday, '%d/%m/%Y'),
    'states' => $col_state,
    'admin_core_enrolment'=> $core_enrolments_values,
    'full_core_enrolment' => $full_enrolments_values,
    'pfizer_enrolment' => $pfizer_enrolments_values,
    'moderna_enrolment' => $moderna_enrolments_values,
    'astra_enrolment' => $astra_enrolments_values,
    'pfizer2_enrolment' => $pfizer2_enrolments_values,
    'admin_core_completion'=> $admin_completion_values,
    'full_core_completion'=> $core_completion_values,
    'pfizer_completion' => $pfizer_completion_values,
    'astra_completion' => $astra_completion_values,
    'moderna_completion' => $moderna_completion_values,
    'pfizer2_completion' => $pfizer2_completion_values,
    'mailurl' => $CFG->wwwroot.'/report/trainingenrolment/mailtousers.php',
    'hits' => helper::get_hits($selected_raw)
);
$templatecontext = array_merge($templatecontext, $core_incomplete_allvalues);
$templatecontext = array_merge($templatecontext, $full_incomplete_allvalues);

//Getting total core enrolment row data for enrolment and incomplete tables
$templatecontext['admin_total_core'] = helper::total_core_enrolment($templatecontext['admin_core_enrolment']['fr'],$templatecontext['full_core_enrolment']['fr']);
$templatecontext['admin_total_core']['increase_stats'] = ($templatecontext['admin_total_core']['increase_stats'] !=0) ? $templatecontext['admin_total_core']['increase_stats'] : '-';

$templatecontext['admin_total_core37'] = helper::total_core_enrolment($templatecontext['admin_core_incomplete37']['fr'],$templatecontext['full_core_incomplete37']['fr']);
$templatecontext['admin_total_core37']['increase_stats'] = ($templatecontext['admin_total_core37']['increase_stats'] !=0) ? $templatecontext['admin_total_core37']['increase_stats'] : '-';

$templatecontext['admin_total_core714'] = helper::total_core_enrolment($templatecontext['admin_core_incomplete714']['fr'],$templatecontext['full_core_incomplete714']['fr']);
$templatecontext['admin_total_core714']['increase_stats'] = ($templatecontext['admin_total_core714']['increase_stats'] !=0) ? $templatecontext['admin_total_core714']['increase_stats'] : '-';

$templatecontext['admin_total_core14plus'] = helper::total_core_enrolment($templatecontext['admin_core_incomplete14plus']['fr'],$templatecontext['full_core_incomplete14plus']['fr']);
$templatecontext['admin_total_core14plus']['increase_stats'] = ($templatecontext['admin_total_core14plus']['increase_stats'] !=0) ? $templatecontext['admin_total_core14plus']['increase_stats'] : '-';

$templatecontext['admin_total_corenon14'] = helper::total_core_enrolment($templatecontext['admin_core_incompletenon14']['fr'],$templatecontext['full_core_incompletenon14']['fr']);
$templatecontext['admin_total_corenon14']['increase_stats'] = ($templatecontext['admin_total_corenon14']['increase_stats'] !=0) ? $templatecontext['admin_total_corenon14']['increase_stats'] : '-';

//Getting completion report stats for enrolments table (total,increase,previous)
$templatecontext['enrolment_stats']['total'] = ($templatecontext['admin_core_enrolment']['fr']['total_sum_stats']+ $templatecontext['full_core_enrolment']['col_count_total']['total_sum_stats']);

$templatecontext['enrolment_stats']['increase'] = ($templatecontext['admin_core_enrolment']['fr']['increase_stats'] +
    $templatecontext['full_core_enrolment']['col_count_total']['increase_stats']);

$templatecontext['enrolment_stats']['previous'] = ($templatecontext['admin_core_enrolment']['fr']['previous_stats'] +
    $templatecontext['full_core_enrolment']['col_count_total']['previous_stats']);

$templatecontext['admincore_totalstats'] = number_format($templatecontext['enrolment_stats']['total']);
$templatecontext['admincore_increasestats'] = ($templatecontext['enrolment_stats']['increase'] != 0) ? number_format($templatecontext['enrolment_stats']['increase']) : '-';
$templatecontext['admincore_previousstats'] = number_format($templatecontext['enrolment_stats']['previous']);

//Getting completion report stats - core percentage (total,previous)
$templatecontext['core_stats']['total'] = round(((($templatecontext['full_core_completion']['col_count_total']['total_sum_stats'] + $templatecontext['admin_core_completion']['fr']['total_sum_stats'] ) / $templatecontext['enrolment_stats']['total'] ) * 100), 2).' %';
$templatecontext['core_stats']['previous'] = round(((($templatecontext['full_core_completion']['col_count_total']['previous_stats'] + $templatecontext['admin_core_completion']['fr']['previous_stats']) / $templatecontext['enrolment_stats']['previous'] ) * 100), 2).' %';

//Getting completion report stats - pfizer percentage (total,previous)
$templatecontext['pfizer_stats']['total'] = round((($templatecontext['pfizer_completion']['col_count_total']['total_sum_stats'] / $templatecontext['full_core_completion']['col_count_total']['total_sum_stats'] ) * 100), 2).' %';
$templatecontext['pfizer_stats']['previous'] = round((($templatecontext['pfizer_completion']['col_count_total']['previous_stats'] / $templatecontext['full_core_completion']['col_count_total']['previous_stats'] ) * 100), 2).' %';

//Getting completion report stats - pfizer2 percentage (total,previous)
$templatecontext['pfizer2_stats']['total'] = round((($templatecontext['pfizer2_completion']['col_count_total']['total_sum_stats'] / $templatecontext['full_core_completion']['col_count_total']['total_sum_stats'] ) * 100), 2).' %';
$templatecontext['pfizer2_stats']['previous'] = round((($templatecontext['pfizer2_completion']['col_count_total']['previous_stats'] / $templatecontext['full_core_completion']['col_count_total']['previous_stats'] ) * 100), 2).' %';

//Getting completion report stats - astra percentage (total,previous)
$templatecontext['astra_stats']['total'] = round((($templatecontext['astra_completion']['col_count_total']['total_sum_stats'] / $templatecontext['full_core_completion']['col_count_total']['total_sum_stats'] ) * 100), 2).' %';
$templatecontext['astra_stats']['previous'] = round((($templatecontext['astra_completion']['col_count_total']['previous_stats'] / $templatecontext['full_core_completion']['col_count_total']['previous_stats'] ) * 100), 2).' %';

//Getting completion report stats - moderna percentage (total,previous)
$templatecontext['moderna_stats']['total'] = round((($templatecontext['moderna_completion']['col_count_total']['total_sum_stats'] / $templatecontext['full_core_completion']['col_count_total']['total_sum_stats'] ) * 100), 2).' %';
$templatecontext['moderna_stats']['previous'] = round((($templatecontext['moderna_completion']['col_count_total']['previous_stats'] / $templatecontext['full_core_completion']['col_count_total']['previous_stats'] ) * 100), 2).' %';

//$templatecontext['total_enrolment_mean'] = helper::get_mean_totalenrolment($selected_raw, $col_state);

//Renders HTML templates file with data into PDF file
$html = $OUTPUT->render_from_template('report_trainingenrolment/tabledata', $templatecontext);

//generates PDF output file
$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [400, 500], 'tempDir' => $CFG->tempdir]);
$mpdf->simpleTables = false;
$stylesheet = file_get_contents('styles.css');
$mpdf->WriteHTML($stylesheet, 1);
$mpdf->WriteHTML($html, 2);

// PDF file postfix as selected date
$today = userdate(time(), get_string('strftimedatefullshort', 'core_langconfig'));
$today = str_replace("/", "", $today);

$filename = get_string('filename', 'report_trainingenrolment').$today.'.pdf';
$mpdf->Output($filename, 'D');

//Adds a new event when report is downloaded by user
report_trainingenrolment_events::report_downloaded($USER->id);
