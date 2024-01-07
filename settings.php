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
 * Report settings
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Ensure the configurations for this site are set.
if ($ADMIN->fulltree) {
    //sends pdf attachment
    $name        = 'report_trainingenrolment/pdf';
    $visiblename = get_string('enablepdf', 'report_trainingenrolment');
    $description = get_string('enablepdf_help', 'report_trainingenrolment');
    $default     = 1;
    $setting     = new admin_setting_configcheckbox($name, $visiblename, $description, $default);
    $settings->add($setting);

    $name = 'report_trainingenrolment/subject';
    $visiblename = get_string('subject', 'report_trainingenrolment');
    $description = get_string('subject_help', 'report_trainingenrolment');
    $default = '';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/message';
    $visiblename = get_string('message', 'report_trainingenrolment');
    $description = get_string('message_help', 'report_trainingenrolment');
    $default = '';
    $paramtype = PARAM_RAW_TRIMMED;
    $settings->add(new admin_setting_configtextarea($name, $visiblename, $description, $default, $paramtype));

    $name = 'report_trainingenrolment/emailids';
    $visiblename = get_string('emailids', 'report_trainingenrolment');
    $description = get_string('emailids_help', 'report_trainingenrolment');
    $default = '';
    $settings->add(new admin_setting_configtextarea($name, $visiblename, $description, $default));

    // Sync settings
    $settings->add(new admin_setting_heading('report_trainingenrolment/syncsettingsheader','Sync settings',''));

    $name = 'report_trainingenrolment/dashboard_sync_time';
    $visiblename = get_string('dashboard_sync_time', 'report_trainingenrolment');
    $description = get_string('dashboard_sync_time_desc', 'report_trainingenrolment');
    $default = '86400';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/ignore_users';
    $visiblename = get_string('ignore_users', 'report_trainingenrolment');
    $description = get_string('ignore_users_desc', 'report_trainingenrolment');
    $default = '1,5,6,2,11,3,45150';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));


    // Course modules mapping
    $settings->add(new admin_setting_heading('report_trainingenrolment/coursemodulesheader','Course modules mapping',''));

    $name = 'report_trainingenrolment/module_1';
    $visiblename = get_string('module_1', 'report_trainingenrolment');
    $description = get_string('module_1_desc', 'report_trainingenrolment');
    $default = '2,24';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/module_2';
    $visiblename = get_string('module_2', 'report_trainingenrolment');
    $description = get_string('module_2_desc', 'report_trainingenrolment');
    $default = '3,25';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/module_3';
    $visiblename = get_string('module_3', 'report_trainingenrolment');
    $description = get_string('module_3_desc', 'report_trainingenrolment');
    $default = '4,26';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/module_4';
    $visiblename = get_string('module_4', 'report_trainingenrolment');
    $description = get_string('module_4_desc', 'report_trainingenrolment');
    $default = '5,27';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/module_5';
    $visiblename = get_string('module_5', 'report_trainingenrolment');
    $description = get_string('module_5_desc', 'report_trainingenrolment');
    $default = '6';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/module_6';
    $visiblename = get_string('module_6', 'report_trainingenrolment');
    $description = get_string('module_6_desc', 'report_trainingenrolment');
    $default = '7,28';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/pfizer_modules';
    $visiblename = get_string('pfizer_modules', 'report_trainingenrolment');
    $description = get_string('pfizer_modules_desc', 'report_trainingenrolment');
    $default = '8,29';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/astrazeneca_modules';
    $visiblename = get_string('astrazeneca_modules', 'report_trainingenrolment');
    $description = get_string('astrazeneca_modules_desc', 'report_trainingenrolment');
    $default = '18,30';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/moderna_modules';
    $visiblename = get_string('moderna_modules', 'report_trainingenrolment');
    $description = get_string('moderna_modules_desc', 'report_trainingenrolment');
    $default = '34';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/pfizer2_modules';
    $visiblename = get_string('pfizer2_modules', 'report_trainingenrolment');
    $description = get_string('pfizer2_modules_desc', 'report_trainingenrolment');
    $default = '40';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));


    $settings->add(new admin_setting_heading('report_trainingenrolment/remotedbheading','Remote DB to copy data from',''));

    $name = 'report_trainingenrolment/dbhost';
    $visiblename = get_string('dbhost', 'report_trainingenrolment');
    $description = get_string('dbhost_desc', 'report_trainingenrolment');
    $default = '';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/dbname';
    $visiblename = get_string('dbname', 'report_trainingenrolment');
    $description = get_string('dbname_desc', 'report_trainingenrolment');
    $default = '';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/dbuser';
    $visiblename = get_string('dbuser', 'report_trainingenrolment');
    $description = get_string('dbuser_desc', 'report_trainingenrolment');
    $default = '';
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $default));

    $name = 'report_trainingenrolment/dbpass';
    $visiblename = get_string('dbpass', 'report_trainingenrolment');
    $description = get_string('dbpass_desc', 'report_trainingenrolment');
    $default = '';
    $settings->add(new admin_setting_configpasswordunmask($name, $visiblename, $description, $default));
}

// Create the reporting page.
$category = new admin_category('covid_dashboard', 'COVID Reports');

$ADMIN->add('reports', $category);


$page = new admin_externalpage(
    'report_trainingenrolment',
    get_string('pluginname', 'report_trainingenrolment'),
    "$CFG->wwwroot/report/trainingenrolment/index.php",
    'report/trainingenrolment:view'
);

$ADMIN->add('covid_dashboard', $page);

$page = new admin_externalpage(
    'report_participantdetails',
    get_string('participantdetails', 'report_trainingenrolment'),
    "$CFG->wwwroot/report/trainingenrolment/participants.php",
    'report/trainingenrolment:view'
);

$ADMIN->add('covid_dashboard', $page);

$page = new admin_externalpage(
    'report_postcodecompletions',
    get_string('postcodecompletions', 'report_trainingenrolment'),
    "$CFG->wwwroot/report/trainingenrolment/postcodes.php",
    'report/trainingenrolment:view'
);

$ADMIN->add('covid_dashboard', $page);
