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
 * Performs the upgrade tasks
 *
 * @package   report_trainingenrolment
 * @category  Reports plugin
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_report_trainingenrolment_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021112807) {

        // Define table report_covid_completions to be created.
        $table = new xmldb_table('report_covid_completions');

        // Adding fields to table report_covid_completions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('firstaccess', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('lastaccess', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('cohorts', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('ugroups', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('user_type', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('ahpra_number', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('employer_name', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('profession', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('home_state', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('work_state', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('admin_cm_completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('admin_cm_date', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('full_cm_completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('full_cm_date', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('pfizer_completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('pfizer_date', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('astrazeneca_completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('astrazeneca_date', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('moderna_completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('moderna_date', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_covid_completions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for report_covid_completions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112807, 'report', 'trainingenrolment');
    }

    if ($oldversion < 2021112809) {

        // Define field id to be added to report_covid_completions.
        $table = new xmldb_table('report_covid_completions');
        $field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112809, 'report', 'trainingenrolment');
    }


    if ($oldversion < 2021112811) {

        // Define field admin_cm_duration to be added to report_covid_completions.
        $table = new xmldb_table('report_covid_completions');
        $field = new xmldb_field('admin_cm_duration', XMLDB_TYPE_INTEGER, '20', null, null, null, null,
            'admin_cm_date');

        // Conditionally launch add field admin_cm_duration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('full_cm_duration', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'full_cm_date');

        // Conditionally launch add field full_cm_duration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pfizer_duration', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'pfizer_date');

        // Conditionally launch add field pfizer_duration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('astrazeneca_duration', XMLDB_TYPE_INTEGER, '20', null, null, null, null,
            'astrazeneca_date');

        // Conditionally launch add field astrazeneca_duration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('moderna_duration', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'moderna_date');

        // Conditionally launch add field moderna_duration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112811, 'report', 'trainingenrolment');
    }

    if ($oldversion < 2021112812) {

        // Define field admin_cm_enrolled to be added to report_covid_completions.
        $table = new xmldb_table('report_covid_completions');
        $field = new xmldb_field('admin_cm_enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1',
            'work_state');

        // Conditionally launch add field admin_cm_enrolled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('full_cm_enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0',
            'admin_cm_duration');

        // Conditionally launch add field full_cm_enrolled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pfizer_enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0',
            'full_cm_duration');

        // Conditionally launch add field pfizer_enrolled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('astrazeneca_enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0',
            'pfizer_duration');

        // Conditionally launch add field astrazeneca_enrolled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('moderna_enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0',
            'astrazeneca_duration');

        // Conditionally launch add field moderna_enrolled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112812, 'report', 'trainingenrolment');
    }

    if ($oldversion < 2021112813) {

        // Define table report_covid_hits to be created.
        $table = new xmldb_table('report_covid_hits');

        // Adding fields to table report_covid_hits.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('day', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hits', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_covid_hits.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for report_covid_hits.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112813, 'report', 'trainingenrolment');
    }


    if ($oldversion < 2021112814) {

        // Define field admin_cm_enrol_time to be added to report_covid_completions.
        $table = new xmldb_table('report_covid_completions');
        $field = new xmldb_field('admin_cm_enrol_time', XMLDB_TYPE_INTEGER, '20', null, null, null, null,
            'admin_cm_enrolled');

        // Conditionally launch add field admin_cm_enrol_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('full_cm_enrol_time', XMLDB_TYPE_INTEGER, '20', null, null, null, null,
            'full_cm_enrolled');

        // Conditionally launch add field full_cm_enrol_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pfizer_enrol_time', XMLDB_TYPE_INTEGER, '20', null, null, null, null,
            'pfizer_enrolled');

        // Conditionally launch add field pfizer_enrol_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('astrazeneca_enrol_time', XMLDB_TYPE_INTEGER, '20', null, null, null, null,
            'astrazeneca_enrolled');

        // Conditionally launch add field astrazeneca_enrol_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('moderna_enrol_time', XMLDB_TYPE_INTEGER, '20', null, null, null, null,
            'moderna_enrolled');

        // Conditionally launch add field moderna_enrol_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112814, 'report', 'trainingenrolment');
    }


    if ($oldversion < 2021112816) {

        // Changing the default of field admin_cm_enrolled on table report_covid_completions to 0.
        $table = new xmldb_table('report_covid_completions');
        $field = new xmldb_field('admin_cm_enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0',
            'work_state');

        // Launch change of default for field admin_cm_enrolled.
        $dbman->change_field_default($table, $field);

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112816, 'report', 'trainingenrolment');
    }

    if ($oldversion < 2021112817) {

        // Define field home_postcode to be added to report_covid_completions.
        $table = new xmldb_table('report_covid_completions');
        $field = new xmldb_field('home_postcode', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'home_state');

        // Conditionally launch add field home_postcode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112817, 'report', 'trainingenrolment');
    }

    if ($oldversion < 2021112818) {

        // Define table report_covid_p_details to be created.
        $table = new xmldb_table('report_covid_p_details');

        // Adding fields to table report_covid_p_details.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('module', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '15', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('home_state', XMLDB_TYPE_CHAR, '4', null, null, null, null);
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table report_covid_p_details.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for report_covid_p_details.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112818, 'report', 'trainingenrolment');
    }

    if ($oldversion < 2021112819) {

        // Rename field ahpra_number on table report_covid_p_details to NEWNAMEGOESHERE.
        $table = new xmldb_table('report_covid_p_details');

        $field = new xmldb_field('idnumber', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'lastname');

        // Launch change of precision for field ahpra_number.
        $dbman->change_field_precision($table, $field);


        $field = new xmldb_field('idnumber', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'lastname');

        // Launch rename field ahpra_number.
        $dbman->rename_field($table, $field, 'ahpra_number');

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112819, 'report', 'trainingenrolment');
    }

    if ($oldversion < 2021112821) {

        // Define field home_postcode to be added to report_covid_p_details.
        $table = new xmldb_table('report_covid_p_details');
        $field = new xmldb_field('home_postcode', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'home_state');

        // Conditionally launch add field home_postcode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('user_type', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'home_postcode');

        // Conditionally launch add field user_type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021112821, 'report', 'trainingenrolment');
    }


    if ($oldversion < 2021121500) {

        // Define field deleted to be added to report_covid_completions.
        $table = new xmldb_table('report_covid_completions');
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'timemodified');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021121500, 'report', 'trainingenrolment');
    }


    if ($oldversion < 2021122001) {

        // Define field pfizer2_enrolled to be added to report_covid_completions.
        $table = new xmldb_table('report_covid_completions');
        $field = new xmldb_field('pfizer2_enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'moderna_duration');

        // Conditionally launch add field pfizer2_enrolled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pfizer2_enrol_time', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'pfizer2_enrolled');

        // Conditionally launch add field pfizer2_enrol_time.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pfizer2_completed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'pfizer2_enrol_time');

        // Conditionally launch add field pfizer2_completed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pfizer2_date', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'pfizer2_completed');

        // Conditionally launch add field pfizer2_date.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('pfizer2_duration', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'pfizer2_date');

        // Conditionally launch add field pfizer2_duration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Trainingenrolment savepoint reached.
        upgrade_plugin_savepoint(true, 2021122001, 'report', 'trainingenrolment');
    }





    return true;
}
