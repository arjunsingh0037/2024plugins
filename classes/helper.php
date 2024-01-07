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
 * Helper class for report_trainingenrolment
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_trainingenrolment;

use cache;
use coding_exception;
use context_course;
use core_user;
use dml_exception;
use moodle_exception;
use report_trainingenrolment\helper\logger;
use report_trainingenrolment_events;
use report_trainingenrolment\helper\data_helper;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class helper
{
    /**
     * Processes scheduled task
     *
     * @throws \dml_exception
     */
    public static function processemails() {
        global $DB;
        $config = get_config('report_trainingenrolment');
        $epdf = $config->pdf;
        $subject = $config->subject;
        $message = $config->message;
        $mailids = $config->emailids;

        try {
            self::generate_pdf();
            self::sendmailwithattachment($epdf, $subject, $message, $mailids);

            logger::success('Emails processed successfully!!!');
        } catch (\Exception $e) {
            logger::fatal('Unable to process emails. '. $e->getMessage());
        }
        return true;
    }

    /**
     * Send mail to users with PDF attachment
     *
     * @throws \dml_exception
     */
    public static function sendmailwithattachment($epdf, $subject, $message, $mailids) {
        global $DB, $CFG, $OUTPUT;
        require_once (__DIR__ . '../../locallib.php');
        logger::info('Sending emails');
        $emailuser = new stdClass();
        $allmails = array_filter(explode(',', $mailids));
        $attachment = $filename = '';
        if (!empty($allmails)) {
            logger::info('Found '. count($allmails) . ' emails to send the reports');
            foreach ($allmails as $ids) {
                $emailuser->email = $ids;
                $emailuser->id = -99;
                $fromuser = core_user::get_noreply_user();
                // Checks if directory and pdf file exists for current date.
                $today = userdate(time(), get_string('strftimedatefullshort', 'core_langconfig'));
                $today = str_replace("/", "", $today);
                $path = $CFG->dataroot.'/report/trainingenrolment';
                if (check_dir_exists($path, false, false)) {
                    $filename = get_string('filename', 'report_trainingenrolment').$today.'.pdf';
                    $file = $path.'/'.$filename;
                    if (file_exists($file)) {
                        $attachment = $file;
                    } else {
                        $filename = '';
                    }
                }
                ob_start();
                email_to_user($emailuser, $fromuser, $subject, $message, '', $attachment, $filename, false);
                report_trainingenrolment_events::mail_sent($ids);
                ob_end_clean();

                logger::info('Email sent to: '. $ids);
            }
        }

        return true;
    }

    /**
     * Outputs PDF generated file
     *
     * @throws \dml_exception
     */
    public static function generate_pdf() {
        global $DB, $CFG, $OUTPUT;
        require_once (__DIR__ . '../../vendor/autoload.php');
        $currentday = userdate(time(), '%d/%m/%Y');
        $dated = optional_param('d', $currentday, PARAM_TEXT);
        $today = strtotime(date('d-m-Y'));

        $selected_raw = strtotime(str_replace('/', '-', $dated));
        $yesterday = $selected_raw - 86400;
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
        $html = $OUTPUT->render_from_template('report_trainingenrolment/tabledata', $templatecontext);
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [400, 500], 'tempDir' => $CFG->tempdir]);
        $stylesheet = file_get_contents($CFG->dirroot.'/report/trainingenrolment/styles.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($html, 2);

        $today = userdate(time(), get_string('strftimedatefullshort', 'core_langconfig'));
        $today = str_replace("/", "", $today);

        $path = $CFG->dataroot.'/report/trainingenrolment';
        if (!file_exists($path)) {
            make_upload_directory('report/trainingenrolment');
        }
        $filename = $path.'/'.get_string('filename', 'report_trainingenrolment').$today.'.pdf';
        $mpdf->Output($filename, 'F');
        return true;
    }

    /**
     * Handles records for all table types
     *
     * @param int $dateselect selected date in form or current date
     * @param string $type selected type as enrolled or completed
     * @param int $typevalue type value as 0 or 1
     * @param string $range selected type as enrolled/completed
     * @param string $module module type as field name in table
     * @param int|null $usertype field name in table
     * @returns array to display table data
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_table_records ($dateselect, $type, $typevalue, $range=0, $module, $usertype = null) {
        global $DB;
        $total = 0;
        $groupbyuser = '';
        $enrolrecord = $enrolrecordtemp = $colcountadmin = $recordcount = [];
        $totalsum = $colcountsum = $previoussum = $colcount = [];
        $allstates = ['ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA'];
        if ($type == 'enrolled') {
            $conditions = "AND {$module}_enrol_time <= $dateselect";
        } else {
            if ($range == '37') {
                $rangefrom = $dateselect - (86400 * 3);
                $rangeto = $dateselect - (86400 * 7);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '714') {
                $rangefrom = $dateselect - (86400 * 7);
                $rangeto = $dateselect - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '14plus') {
                $rangeto = $dateselect - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) < $rangeto
                               AND {$module}_enrolled = 1";
            } else if ($range == 'non14') {
                $rangeto = $dateselect - (86400 * 14);
                $conditions = "AND (lastaccess IS NULL OR lastaccess = 0)
                               AND account_created < $rangeto AND {$module}_enrolled = 1";
            } else if ($range == 'mean') {
                $conditions = "AND {$module}_date <= $dateselect AND {$module}_enrolled = 1";
            }
        }
        // Combines module type (eg: admin_cm / full_cm) with enrolled/completed keyword.
        $fieldtype = $module.'_'.$type;
        if (isset($usertype) && !empty($usertype)) {
            foreach ($usertype as $utype) {
                $sql = "SELECT home_state, COUNT(userid) AS usercount,
                    AVG((`{$module}_duration`))/86400 AS mean
                    FROM {report_covid_completions}
                    WHERE $fieldtype = $typevalue
                    AND home_state IN ('ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA')
                    AND user_type = $utype
                    AND deleted=0  AND cohorts IS NOT NULL
                $conditions GROUP BY home_state";
                $recordcount[$utype] = $DB->get_records_sql($sql);
                foreach ($allstates as $state) {
                    if (!array_key_exists($state, $recordcount[$utype])) {
                        $recordcount[$utype][$state] = (object)['home_state' => $state, 'usercount' => 0, 'mean' => 0];
                    }
                    $colcount[$state][] = $recordcount[$utype][$state]->usercount;
                    $total = $total + $recordcount[$utype][$state]->usercount;
                    $recordcount[$utype][$state]->usercount = number_format($recordcount[$utype][$state]->usercount);
                    $recordcount[$utype][$state]->mean = round($recordcount[$utype][$state]->mean, 2);
                }
                $prevusertypecount = self::get_previoususertype_count($dateselect, $type, $typevalue, $range, $module, $utype);
                $recordcount[$utype]['typename'] = data_helper::USER_TYPES[$utype];
                $recordcount[$utype]['total'] = number_format($total);
                $recordcount[$utype]['increase'] = ($total - $prevusertypecount);
                $recordcount[$utype]['increase'] = ($recordcount[$utype]['increase'] != 0) ? number_format($recordcount[$utype]['increase']) : '-';
                $recordcount[$utype]['previous'] = number_format($prevusertypecount);
                $recordcount[$utype]['total_overall_mean'] = round(self::get_overall_meantype($dateselect, $type, $typevalue, $range, $module, $utype), 2);
                $recordcount[$utype]['previous_overall_mean'] = round(self::get_overall_meantype($dateselect - 86400, $type, $typevalue, $range, $module, $utype), 2);
                $recordcount[$utype]['increase_overall_mean'] = round(($recordcount[$utype]['total_overall_mean']) - ($recordcount[$utype]['previous_overall_mean']), 2);
                $recordcount[$utype]['increase_overall_mean'] = ($recordcount[$utype]['increase_overall_mean'] != 0) ? $recordcount[$utype]['increase_overall_mean'] : '-';
                $totalsum[] = $total;
                $previoussum[] = $prevusertypecount;
                $total = 0;
            }

            foreach ($colcount as $ck => $cc) {
                $colcountsum[$ck] = number_format(array_sum($cc));
            }
            $colcountsum['total_sum_stats'] = array_sum($totalsum);
            $colcountsum['increase_stats'] = (array_sum($totalsum)) - (array_sum($previoussum));
            //$colcountsum['increase_stats'] = ($colcountsum['increase_stats'] >= 0) ? $colcountsum['increase_stats'] : '0';
            $colcountsum['previous_stats'] = array_sum($previoussum);
            $colcountsum['total_sum'] = number_format(array_sum($totalsum));
            $colcountsum['increase'] = number_format((array_sum($totalsum)) - (array_sum($previoussum)));
            $colcountsum['increase'] = ($colcountsum['increase'] != 0) ? $colcountsum['increase'] : '-';
            $colcountsum['previous'] = number_format(array_sum($previoussum));

            $enrolrecordtemp = $recordcount;
            $enrolrecord = [
                'countdata' => json_decode(json_encode($enrolrecordtemp), true),
                'col_count_total' => $colcountsum,
                'fr' => $colcountsum,
                'total_mean_row' => self::get_total_meanrow($dateselect, $type, $typevalue, $module, $allstates)
            ];
        } else {
            $sql = "SELECT home_state, COUNT(userid) AS usercount,
               AVG((`{$module}_duration`))/86400 AS mean
                FROM {report_covid_completions}
                WHERE $fieldtype = $typevalue
                AND home_state IN ('ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA')
                AND deleted=0 AND cohorts IS NOT NULL
                $conditions GROUP BY home_state $groupbyuser";
            $recordcount = $DB->get_records_sql($sql);

            foreach ($allstates as $state) {
                if (!array_key_exists($state, $recordcount)) {
                    $recordcount[$state] = (object)['home_state' => $state, 'usercount' => 0, 'mean' => 0];
                }
                if ($module = 'admin_cm') {
                    $colcountadmin[$state] = $recordcount[$state]->usercount;
                }
            }
            ksort($recordcount);
            foreach ($recordcount as $rec) {
                $total = $total + $rec->usercount;
                $rec->usercount = number_format($rec->usercount);
                $rec->mean = round($rec->mean, 2);
                $enrolrecordtemp[] = $rec;
            }
            $previoustotal = self::get_previoustotal($dateselect, $type, $typevalue, $range, $module);
            $enrolrecord = [
                'countdata' => json_decode(json_encode($enrolrecordtemp), true),
                'total' => number_format($total),
                'increase' => ($total - $previoustotal),
                'previous' => number_format($previoustotal),
                'total_overall_mean' => round(self::get_overall_mean($dateselect, $type, $typevalue, $range, $module), 2),
                'previous_overall_mean' => round(self::get_overall_mean($dateselect - 86400, $type, $typevalue, $range, $module), 2)
            ];
            $enrolrecord ['increase_overall_mean'] = round(($enrolrecord['total_overall_mean']) - ($enrolrecord['previous_overall_mean']), 2);
            $enrolrecord ['increase_overall_mean'] = ($enrolrecord ['increase_overall_mean'] != 0) ? $enrolrecord ['increase_overall_mean'] : '-';
            $enrolrecord['increase'] = ($enrolrecord['increase'] != 0) ? number_format($enrolrecord['increase']) : '-';
            $colcountadmin['total_sum_stats'] = $total;
            $colcountadmin['increase_stats'] = ($total - $previoustotal);
            //$colcountadmin['increase_stats'] = ($colcountadmin['increase_stats'] >= 0) ? $colcountadmin['increase_stats'] : '0';
            $colcountadmin['previous_stats'] = $previoustotal;
            $enrolrecord['fr'] = $colcountadmin;
        }
        return $enrolrecord;
    }

    /**
     * Handles records for hits table
     *
     * @param int $dateselect selected date in form or current date
     * @returns array to display hits table data
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_hits($selecteddate) {
        global $DB;
        $hitsarray = [];
        $days = [$selecteddate - 86400, $selecteddate - (86400 * 2), $selecteddate - (86400 * 3)];
        $tablehits = $DB->get_records('report_covid_hits');
        foreach ($days as $day) {
            foreach ($tablehits as $hitvalues) {
                $existingdate = strtotime($hitvalues->day);
                if ($existingdate == $day) {
                    $hitsarray[] = [
                        'day' => userdate($existingdate, '%d/%m/%Y'),
                        'hit' => number_format($hitvalues->hits)
                    ];
                }
            }
        }
        return array_reverse($hitsarray);
    }

    /**
     * Handles previous day records for all table types state wise
     *
     * @param int $dateselect selected date in form or current date
     * @param string $type selected type as enrolled or completed
     * @param int $typevalue type value as 0 or 1
     * @param string $range selected type as enrolled/completed
     * @param string $module module type as field name in table
     * @returns int previous day total records for all states
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_previoustotal($dateselect, $type, $typevalue, $range = 0, $module) {
        global $DB;
        $prev = $dateselect - 86400;
        $recordcount = [];
        $total = 0;
        if ($type == 'enrolled') {
            $conditions = "AND {$module}_enrol_time <= $prev";
        } else {
            if ($range == '37') {
                $rangefrom = $prev - (86400 * 3);
                $rangeto = $prev - (86400 * 7);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '714') {
                $rangefrom = $prev - (86400 * 7);
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '14plus') {
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) < $rangeto AND {$module}_enrolled = 1";
            } else if ($range == 'non14') {
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (lastaccess IS NULL OR lastaccess = 0) AND account_created < $rangeto
                               AND {$module}_enrolled = 1";
            } else if ($range == 'mean') {
                $conditions = "AND {$module}_date <= $prev AND {$module}_enrolled = 1";
            }
        }
        // Combines module type (eg: admin_cm / full_cm) with enrolled/completed keyword.
        $fieldtype = $module.'_'.$type;
        $sql = "SELECT SUM(usercount) as sumcount FROM(SELECT COUNT(userid) as usercount, home_state
                FROM {report_covid_completions}
                WHERE $fieldtype = $typevalue
                AND home_state IN ('ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA')
                AND deleted=0 AND cohorts IS NOT NULL
                $conditions GROUP BY home_state) as previoustotal";
        $recordcount = $DB->get_record_sql($sql);
        return ($recordcount->sumcount) ? $recordcount->sumcount : '0';
    }

    /**
     * Handles previous day records for all state and user type cell
     *
     * @param int $dateselect selected date in form or current date
     * @param string $type selected type as enrolled or completed
     * @param int $typevalue type value as 0 or 1
     * @param string $range selected type as enrolled/completed
     * @param string $module module type as field name in table
     * @param int $utype user type as ( 0 or 1 ) same as field name in table
     * @returns int previous day total records for all usertype with states
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_previoususertype_count($dateselect, $type, $typevalue, $range = 0, $module, $utype) {
        global $DB;
        $prev = $dateselect - 86400;
        $recordcount = [];
        $total = 0;
        if ($type == 'enrolled') {
            $conditions = "AND {$module}_enrol_time <= $prev";
        } else {
            if ($range == '37') {
                $rangefrom = $prev - (86400 * 3);
                $rangeto = $prev - (86400 * 7);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '714') {
                $rangefrom = $prev - (86400 * 7);
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '14plus') {
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) < $rangeto AND {$module}_enrolled = 1";
            } else if ($range == 'non14') {
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (lastaccess IS NULL OR lastaccess = 0) AND account_created < $rangeto
                               AND {$module}_enrolled = 1";
            } else if ($range == 'mean') {
                $conditions = "AND {$module}_date <= $prev AND {$module}_enrolled = 1";
            }
        }
        // Combines module type (eg: admin_cm / full_cm) with enrolled/completed keyword.
        $fieldtype = $module.'_'.$type;
        $sql = "SELECT SUM(usercount) as sumcount FROM(SELECT COUNT(userid) as usercount, home_state
                FROM {report_covid_completions}
                WHERE $fieldtype = $typevalue
                AND home_state IN ('ACT','NSW','NT','QLD','SA','TAS','VIC','WA')
                AND user_type = $utype
                AND deleted=0 AND cohorts IS NOT NULL
                $conditions GROUP BY home_state) as previoustotal";
        $recordcount = $DB->get_record_sql($sql);
        return ($recordcount->sumcount) ? $recordcount->sumcount : '0';
    }

    /**
     * Outputs Total enrolment/complete row values
     *
     * @param array $admincore total core row values from final array
     * @param array $fullcore total full core row values from final array
     * @returns array adds total core and full cell row values
     * @throws \coding_exception
     */
    public static function total_core_enrolment($admincore, $fullcore) {
        $sumarray = array();
        foreach ($admincore as $k => $value) {
            if (isset($fullcore[$k])) {
                $sumarray[$k] = number_format($value + filter_var($fullcore[$k], FILTER_SANITIZE_NUMBER_INT));
            }
        }
        return $sumarray;
    }

    /**
     * Calculates overall mean
     *
     * @param int $dateselect selected date in form or current date
     * @param string $type selected type as enrolled or completed
     * @param int $typevalue type value as 0 or 1
     * @param string $range selected type as enrolled/completed
     * @param string $module module type as field name in table
     * @returns float overall mean value for current and previous date
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_overall_mean($dateselect, $type, $typevalue, $range = 0, $module) {
        global $DB;
        $prev = $dateselect;
        $recordcount = [];
        $total = 0;
        if ($type == 'enrolled') {
            $conditions = "AND {$module}_enrol_time <= $prev";
        } else {
            if ($range == '37') {
                $rangefrom = $prev - (86400 * 3);
                $rangeto = $prev - (86400 * 7);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '714') {
                $rangefrom = $prev - (86400 * 7);
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '14plus') {
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) < $rangeto AND {$module}_enrolled = 1";
            } else if ($range == 'non14') {
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (lastaccess IS NULL OR lastaccess = 0)
                               AND account_created < $rangeto AND {$module}_enrolled = 1";
            } else if ($range == 'mean') {
                $conditions = "AND {$module}_date <= $prev AND {$module}_enrolled = 1";
            }
        }
        // Combines module type (eg: admin_cm / full_cm) with enrolled/completed keyword.
        $fieldtype = $module.'_'.$type;
        $sql = "SELECT AVG((`{$module}_duration`))/86400  AS mean
                FROM {report_covid_completions}
                WHERE $fieldtype = $typevalue $conditions
                AND deleted=0 AND cohorts IS NOT NULL";
        $recordcount = $DB->get_record_sql($sql);
        return ($recordcount->mean) ? $recordcount->mean : '0';
    }

    /**
     * Calculates overall mean for range data
     *
     * @param int $dateselect selected date in form or current date
     * @param string $type selected type as enrolled or completed
     * @param int $typevalue type value as 0 or 1
     * @param string $range selected type as enrolled/completed
     * @param string $module module type as field name in table
     * @param int $utype user type as ( 0 or 1 ) same as field name in table
     * @returns float overall mean value for current and previous date in range type tables
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_overall_meantype($dateselect, $type, $typevalue, $range = 0, $module, $utype) {
        global $DB;
        $prev = $dateselect;
        $recordcount = [];
        $total = 0;
        if ($type == 'enrolled') {
            $conditions = "AND {$module}_enrol_time <= $prev";
        } else {
            if ($range == '37') {
                $rangefrom = $prev - (86400 * 3);
                $rangeto = $prev - (86400 * 7);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '714') {
                $rangefrom = $prev - (86400 * 7);
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) > $rangeto
                               AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) <= $rangefrom AND {$module}_enrolled = 1";
            } else if ($range == '14plus') {
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (CASE WHEN `lastaccess` IS NULL OR `lastaccess` = '0'
                               THEN `account_created` ELSE `lastaccess` END) < $rangeto AND {$module}_enrolled = 1";
            } else if ($range == 'non14') {
                $rangeto = $prev - (86400 * 14);
                $conditions = "AND (lastaccess IS NULL OR lastaccess = 0) AND account_created < $rangeto
                               AND {$module}_enrolled = 1";
            } else if ($range == 'mean') {
                $conditions = "AND {$module}_date <= $prev AND {$module}_enrolled = 1";
            }
        }
        // Combines module type (eg: admin_cm / full_cm) with enrolled/completed keyword.
        $fieldtype = $module.'_'.$type;
        $sql = "SELECT AVG((`{$module}_duration`))/86400 AS mean
                FROM {report_covid_completions}
                WHERE $fieldtype = $typevalue $conditions
                AND user_type = $utype
                AND deleted=0";
        $recordcount = $DB->get_record_sql($sql);
        return ($recordcount->mean) ? $recordcount->mean : '0';
    }

    /**
     * Calculates total mean for row
     * @param int $dateselect selected date in form or current date
     * @param string $type selected type as enrolled or completed
     * @param int $typevalue type value as 0 or 1
     * @param string $module module type as field name in table
     * @param array $states all states array
     * @returns float total mean value for final row
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_total_meanrow($dateselect, $type, $typevalue, $module, $states) {
        global $DB;
        // Combines module type (eg: admin_cm / full_cm) with enrolled/completed keyword.
        $fieldtype = $module.'_'.$type;
        $conditions = "AND {$module}_date <= $dateselect AND home_state IS NOT NULL";
        $conditionsprev = "AND {$module}_date <= ($dateselect - 86400) AND home_state IS NOT NULL";
        $finalrow = [];
        // Get total row data w.r.t states.
        $sql = "SELECT home_state,AVG((`{$module}_duration`))/86400 AS mean
                FROM {report_covid_completions}
                WHERE $fieldtype = $typevalue $conditions 
                AND deleted=0
                GROUP BY home_state";
        $recordcount = $DB->get_records_sql($sql);

        // Get combined total mean value for all states.
        $sqlcombined = "SELECT AVG((`{$module}_duration`))/86400 AS mean
                FROM {report_covid_completions}
                WHERE $fieldtype = $typevalue $conditions
                AND deleted=0";
        $recordcountcombined = $DB->get_record_sql($sqlcombined);
        $finalrow['total_overall_combined'] = round($recordcountcombined->mean, 2);

        // Get combined total previous day value for all states.
         $sqlprevious = "SELECT AVG((`{$module}_duration`))/86400 AS mean
                FROM {report_covid_completions}
                WHERE $fieldtype = $typevalue $conditionsprev
                AND deleted=0";
        $recordcountprevious = $DB->get_record_sql($sqlprevious);
        $finalrow['total_overall_previous'] = round($recordcountprevious->mean, 2);
        $finalrow['total_overall_increase'] = round(($recordcountcombined->mean) - ($recordcountprevious->mean), 2);
        $finalrow['total_overall_increase'] = ($finalrow['total_overall_increase'] != 0) ? $finalrow['total_overall_increase'] : '-';
        foreach ($recordcount as $rc) {
            $finalrow[$rc->home_state] = round($rc->mean, 2);
        }
        foreach ($states as $k => $state) {
            if (!array_key_exists($state, $finalrow)) {
                $finalrow[$state] = 0.00;
            }
        }
        return $finalrow;
    }

    /**
     * Calculates total enrolments mean row data for current & previous date
     *
     * @param int $dateselect selected date in form or current date
     * @param array $states all states array
     * @returns array current and previous date total counts
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_mean_totalenrolment($dateselect, $states) {
        global $DB;
        $average = $recordtotalcount = $recordpreviouscount = $finalrow = [];
        $modules = ['admin_cm', 'full_cm', 'pfizer', 'astrazeneca', 'moderna', 'pfizer2'];
        foreach ($modules as $module) {
            $sql = "SELECT `home_state`,SUM(({$module}_date) - firstaccess) AS total, COUNT(userid) as usercount
                    FROM `mdl_report_covid_completions`
                    WHERE {$module}_enrolled = 1 AND {$module}_date <= $dateselect
                    AND home_state IN ('ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA')
                    AND deleted=0
                    GROUP BY `home_state` ORDER BY `home_state` ASC";
            $recordcount = $DB->get_records_sql($sql);
            foreach ($recordcount as $rc) {
                $finalrow[$rc->home_state]["sum"][] = $rc->total;
                $finalrow[$rc->home_state]["count"][] = $rc->usercount;
            }

            $sqltotal = "SELECT SUM(({$module}_date) - firstaccess) AS total, COUNT(userid) as count
                    FROM `mdl_report_covid_completions`
                    WHERE {$module}_enrolled = 1 AND {$module}_date <= $dateselect
                    AND deleted=0";
            $totalrecords = $DB->get_record_sql($sqltotal);
            if ($totalrecords->total) {
                $add = $totalrecords->total;
                $count = $totalrecords->count;
            } else {
                $add = 0;
                $count = 0;
            }
            $recordtotalcount['sum'][] = $add;
            $recordtotalcount['count'][] = $count;

            $sqlprev = "SELECT SUM(({$module}_date) - firstaccess) AS totalprev, COUNT(userid) as count
                    FROM `mdl_report_covid_completions`
                    WHERE {$module}_enrolled = 1 AND {$module}_date <= ($dateselect-86400)
                    AND deleted=0";
            $totalprevs = $DB->get_record_sql($sqlprev);
            if ($totalprevs->totalprev) {
                $addprevious = $totalprevs->totalprev;
                $countprevious = $totalprevs->count;
            } else {
                $addprevious = 0;
                $countprevious = 0;
            }
            $recordpreviouscount['sum'][] = $addprevious;
            $recordpreviouscount['count'][] = $countprevious;

        }
        foreach ($states as $k => $state) {
            if (!array_key_exists($state, $finalrow)) {
                $finalrow[$state] = 0;
            }
            $average[$state] = round((array_sum($finalrow[$state]['sum']) / array_sum($finalrow[$state]['count'])) / 86400, 2);
        }
        $average['total'] = round((array_sum($recordtotalcount['sum']) / array_sum($recordtotalcount['count'])) / 86400, 2);
        $average['previous'] = round((array_sum($recordpreviouscount['sum']) / array_sum($recordpreviouscount['count'])) / 86400, 2);
        $average['increase'] = round(($average['total'] - $average['previous']), 2);
        $average['increase'] = ($average['increase'] != 0) ? $average['increase'] : '-';

        return $average;
    }

    /**
     * Get the value from cache
     *
     * @param $key
     * @param string $cachedef
     */
    public static function cache_get($key, $cachedef = 'dashboard') {
        $cache = self::cache($cachedef);
        return $cache->get($key);
    }

    /**
     * Stores the data into the plugin cache
     *
     * @param        $key
     * @param        $value
     * @param string $cachedef
     */
    public static function cache_set($key, $value, $cachedef = 'dashboard') {
        $cache = self::cache($cachedef);
        return $cache->set($key, $value);
    }

    /**
     * Get the cache object for this plugin
     *
     * @param string $type
     * @return mixed
     */
    private static function cache($type = 'dashboard') {
        return cache::make('report_trainingenrolment', $type);
    }
}
