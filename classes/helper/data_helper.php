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
 * This report prepares the data table used for reporting
 *
 * @package   report_trainingenrolment
 * @category  Report plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_trainingenrolment\helper;


use Mpdf\Tag\P;
use PhpOffice\PhpSpreadsheet\Calculation\DateTime;
use report_trainingenrolment\db_handler;
use report_trainingenrolment\event\data_sync_failed;

class data_helper
{
    /**
     * Reporting table
     */
    const REPORTING_TABLE = 'report_covid_completions';
    const HITS_TABLE = 'report_covid_hits';
    const USER_TYPE_NO_AHPRA_ID = 0;
    const DEFAULT_SYNC_TIME = 86400;
    const USER_TYPE_MED = 1;
    const USER_TYPE_NUR = 2;
    const USER_TYPE_PAR = 3;
    const USER_TYPE_PHA = 4;
    const USER_TYPE_ATSI = 5;

    const ADMIN_STAFF = 1;
    const IMMUNISING_PROFESSIONALS = 2;
    const STATE_AND_TERRITORY_WORK_FORCE = 3;

    const USER_TYPES = [
        'No Ahpra ID',
        'Medical Practitioner',
        'Nurse/Midwife',
        'Paramedic',
        'Pharmacist',
        'ATSI'
    ];

    const ADDITIONAL_MODULES = [
        'pfizer',
        'astrazeneca',
        'moderna',
        'pfizer2'
    ];

    static $remotedb = null;
    static $usertable = 'user';
    static $scormtrackstable = 'scorm_scoes_track';
    static $userlastaccess = 'userlastaccess';
    static $userattributestable = 'auth_custom_user_attributes';
    static $cmtable = 'course_modules';
    static $cmcompletionstable = 'course_modules_completion';
    static $cohortmemberstable = 'cohort_members';
    static $logstore = 'logstore_standard_log';
    static $currentdb = null;

    /**
     * Processes the data table
     *
     * @throws \dml_exception
     */
    public static function process()
    {
        global $CFG;

        static::$currentdb = new db_handler();
        static::$currentdb->connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname, $CFG->prefix);
        logger::add('Starting the data process');

        ini_set('memory_limit', '2048M');

        $limit = ini_get('memory_limit');

        logger::add('Memory limit: ' . $limit);

        // Get the time from config
        $time = self::get_sync_time();

        try {
            self::copy_tables_from_remote();
            self::update_deleted_users();
        } catch (\Exception $e) {
            logger::fatal('Unable to copy data from remote db: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
            die; // We can not continue if this fails
        }

        try {
            self::add_missing_users();
        } catch (\Exception $e) {
            logger::fatal('Unable to add missing users: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::remove_ignored_users();
        } catch (\Exception $e) {
            logger::fatal('Unable to add missing users: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::add_enrolment_data($time);
        } catch (\Exception $e) {
            logger::fatal('Unable to add enrolment data: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::set_scorm_enrolment_and_startdates();
        } catch (\Exception $e) {
            logger::fatal('Unable to set SCORM start dates: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::add_completion_data($time);
        } catch (\Exception $e) {
            logger::fatal('Could not add completion data: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }


        try {
            self::add_profile_data($time);
        } catch (\Exception $e) {
            logger::fatal('Unable to add profile data: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::generate_hits_data();
        } catch (\Exception $e) {
            logger::fatal('Unable to generate hits data: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::update_firstaccess_date();
        } catch (\Exception $e) {
            logger::fatal('Unable to update the firstaccess date: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::update_lastaccess_date();
        } catch (\Exception $e) {
            logger::fatal('Unable to update the lastaccess date: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::fix_states();
        } catch (\Exception $e) {
            logger::fatal('Unable to fix states: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        try {
            self::update_lastaccess_table();
        } catch (\Exception $e) {
            logger::fatal('Unable to update lastaccess: ' . $e->getMessage());
            logger::fatal($e->getTraceAsString());
        }

        logger::success('Data processing completed successfully');
    }

    /**
     * Adds the missing users into the database
     *
     * @param null|int $time if time is passed then the script
     *                       will only fetch the records that were
     *                       created after this time
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function add_missing_users($time = null)
    {
        $reporting_table = self::REPORTING_TABLE;
        $usertable       = static::$usertable;
        logger::add('Adding missing users');

        $sql = "SELECT 
                    id as userid, 
                    firstname, 
                    lastname, 
                    email,
                    firstaccess, 
                    lastaccess,
                    institution as employer,
                    idnumber as ahpra_number,
                    timecreated as account_created,
                    timecreated
                FROM {{$usertable}}
                WHERE id NOT IN (
                    SELECT DISTINCT(userid) FROM {{$reporting_table}}
                ) AND id > 1";
        if (!empty($time)) {
            $sql .= " AND timecreated >= {$time}";
        } else {
            $time = time();
        }

        $recordset = static::$currentdb->get_recordset_sql($sql);

        $rows = [];
        foreach ($recordset as $record) {
            $record->user_type = self::get_user_type_from_ahpra_number($record->ahpra_number);

            $record->lastaccess = 0;
            $record->timecreated  = $time;
            $record->timemodified = $time;

            $rows[] = $record;

            if (count($rows) >= 5000) {
                static::$currentdb->insert_records($reporting_table, $rows);
                $rows = [];
            }
        }

        $recordset->close();

        static::$currentdb->insert_records($reporting_table, $rows);

        logger::add('Missing users added');
    }

    /**
     * Removes the ignored users from the reporting table
     */
    public static function remove_ignored_users()
    {
        global $DB;

        logger::add('Removing ignored users');

        $ignored_users = get_config('report_trainingenrolment', 'ignore_users');
        $ignored_users = explode(",", $ignored_users);

        if (!empty($ignored_users)) {
            list($insql, $inparams) = $DB->get_in_or_equal($ignored_users);

            $DB->delete_records_select(self::REPORTING_TABLE, "userid $insql", $inparams);
        }

        logger::add('Ignored users removed');
    }


    /**
     * Updates the lastaccess date from the last access table
     */
    public static function update_lastaccess_date()
    {
        logger::info('Fixing the lastaccess dates');

        $lastaccesstable = static::$userlastaccess;
        $timecheck       = time() - (86400 * 2);

        $sql              = "SELECT count(*) from {{$lastaccesstable}}";
        $totalrecords     = static::$currentdb->count_records_sql($sql, compact('time'));
        $recordsatonetime = 10000;
        $pages            = $totalrecords / $recordsatonetime;

        for ($i = 0; $i < $pages; $i++) {
            logger::info(date('d-m-Y H:i:s'));
            logger::info('Starting round ' . ($i + 1));
            $limitfrom = $i * $recordsatonetime;

            $sql = "UPDATE mdl_report_covid_completions rcc
                INNER JOIN (
                    SELECT userid, timeaccess FROM {{$lastaccesstable}}
                    WHERE timeaccess >= {$timecheck}
                    LIMIT {$limitfrom}, {$recordsatonetime}
                ) as ul
                ON rcc.userid = ul.userid
                SET rcc.lastaccess = ul.timeaccess
            ";
            static::$currentdb->execute($sql);

            logger::info(date('d-m-Y H:i:s'));
            logger::info('Completed round ' . ($i + 1));
        }

        logger::info('Fixed last access dates');
    }

    /**
     * Adds the profile data to the reporting table
     *
     * @param $time
     * @throws \dml_exception
     */
    public static function add_profile_data($time)
    {
        logger::info('Adding user profile data.');
        $userattributestable = static::$userattributestable;

        $sql              = "SELECT count(*) from {{$userattributestable}}";
        $totalrecords     = static::$currentdb->count_records_sql($sql, compact('time'));
        $recordsatonetime = 10000;
        $pages            = $totalrecords / $recordsatonetime;

        for ($i = 0; $i < $pages; $i++) {
            logger::info(date('d-m-Y H:i:s'));
            logger::info('Starting round ' . ($i + 1));
            $limitfrom = $i * $recordsatonetime;

            $sql = "UPDATE mdl_report_covid_completions rcc
                INNER JOIN (
                    SELECT 
                           userid, 
                            TRIM(TRAILING '\"' FROM TRIM(TRAILING '\";' FROM (SUBSTRING(data, ( INSTR( data, CONCAT( 'home_state', '\";' ) ) + CHAR_LENGTH( 'home_state') + 7 ),3 )))) AS `state`,
                            SUBSTRING(data, ( INSTR( data, CONCAT( 'home_postcode', '\";' ) ) + CHAR_LENGTH( 'home_postcode') + 7 ),4 ) as postcode 
                    FROM mdl_auth_custom_user_attributes_rt 
                    LIMIT {$limitfrom}, {$recordsatonetime}
                ) as cua
                ON cua.userid = rcc.userid
                SET rcc.home_state = cua.state,
                    rcc.home_postcode = cua.postcode
                WHERE rcc.home_state IS NULL
                OR rcc.home_state LIKE ''
                OR rcc.home_postcode IS NULL
                OR rcc.home_postcode LIKE ''";
            static::$currentdb->execute($sql);

            logger::info(date('d-m-Y H:i:s'));
            logger::info('Completed round ' . ($i + 1));
        }

        logger::info('User profile data complete');
    }

    /**
     * Adds the enrolment data
     *
     * @param $time
     * @throws \dml_exception
     */
    public static function add_enrolment_data($time)
    {
        logger::info('Adding Enrolment data');

        self::set_cohorts_and_enrolment_status();

        logger::info('Ernolment data added successfully');
    }

    /**
     * Adds the completion data to the reporting table
     *
     * @param null $time
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function add_completion_data($time = null)
    {
        logger::info('Adding completion data');

        $config = get_config('report_trainingenrolment');

        if (
            empty($config->module_3)
            || empty($config->module_6)
            || empty($config->pfizer_modules)
            || empty($config->astrazeneca_modules)
            || empty($config->moderna_modules)
            || empty($config->pfizer2_modules)
        ) {
            logger::fatal('Modules not cofigured. Exiting!!!');
        }

        $admin_modules       = explode(",", $config->module_3);
        $core_modules        = explode(",", $config->module_6);
        $pfizer_modules      = explode(",", $config->pfizer_modules);
        $astrazeneca_modules = explode(",", $config->astrazeneca_modules);
        $moderna_modules     = explode(",", $config->moderna_modules);
        $pfizer2_modules     = explode(",", $config->pfizer2_modules);

        $reporting_table    = self::REPORTING_TABLE;
        $cmcompletionstable = static::$cmcompletionstable;

        list($insql, $inparams) = static::$currentdb->get_in_or_equal($admin_modules);
        $sql = "
                UPDATE {{$reporting_table}} rcc
                LEFT JOIN (
                SELECT userid, MIN(timemodified) completiontime
                FROM {{$cmcompletionstable}} 
                WHERE completionstate IN (1,2)
                AND coursemoduleid {$insql}
                GROUP BY userid
                ) as cmc
                ON cmc.userid = rcc.userid
                SET admin_cm_date = cmc.completiontime,
                admin_cm_completed = 1,
                admin_cm_duration = cmc.completiontime - rcc.firstaccess
                WHERE (rcc.admin_cm_date = 0 OR rcc.admin_cm_date IS NULL)
                AND admin_cm_enrolled = 1
                AND cmc.completiontime IS NOT NULL
        ";
        static::$currentdb->execute($sql, $inparams);


        // Core module
        list($insql, $inparams) = static::$currentdb->get_in_or_equal($core_modules);
        $sql = "
                UPDATE {{$reporting_table}} rcc
                LEFT JOIN (
                SELECT userid, MIN(timemodified) completiontime
                FROM {{$cmcompletionstable}} 
                WHERE completionstate IN (1,2)
                AND coursemoduleid {$insql}
                GROUP BY userid
                ) as cmc
                ON cmc.userid = rcc.userid
                SET full_cm_date = cmc.completiontime,
                full_cm_completed = 1,
                full_cm_duration = cmc.completiontime - rcc.firstaccess
                WHERE (rcc.full_cm_date = 0 OR rcc.full_cm_date IS NULL)
                AND full_cm_enrolled = 1
                AND cmc.completiontime IS NOT NULL
        ";
        static::$currentdb->execute($sql, $inparams);

        // Pfizer
        list($insql, $inparams) = static::$currentdb->get_in_or_equal($pfizer_modules);
        $sql = "
                UPDATE {{$reporting_table}} rcc
                LEFT JOIN (
                SELECT userid, MIN(timemodified) completiontime
                FROM {{$cmcompletionstable}} 
                WHERE completionstate IN (1,2)
                AND coursemoduleid {$insql}
                GROUP BY userid
                ) as cmc
                ON cmc.userid = rcc.userid
                SET pfizer_date = cmc.completiontime,
                pfizer_completed = 1,
                pfizer_duration = cmc.completiontime - rcc.pfizer_enrol_time
                WHERE (rcc.pfizer_date = 0 OR rcc.pfizer_date IS NULL)
                AND pfizer_enrolled = 1
                AND cmc.completiontime IS NOT NULL
        ";
        static::$currentdb->execute($sql, $inparams);

        // AstraZeneca
        list($insql, $inparams) = static::$currentdb->get_in_or_equal($astrazeneca_modules);
        $sql = "
                UPDATE {{$reporting_table}} rcc
                LEFT JOIN (
                SELECT userid, MIN(timemodified) completiontime
                FROM {{$cmcompletionstable}} 
                WHERE completionstate IN (1,2)
                AND coursemoduleid {$insql}
                GROUP BY userid
                ) as cmc
                ON cmc.userid = rcc.userid
                SET astrazeneca_date = cmc.completiontime,
                astrazeneca_completed = 1,
                astrazeneca_duration = cmc.completiontime - rcc.astrazeneca_enrol_time
                WHERE (rcc.astrazeneca_date = 0 OR rcc.astrazeneca_date IS NULL)
                AND astrazeneca_enrolled = 1
                AND cmc.completiontime IS NOT NULL
        ";
        static::$currentdb->execute($sql, $inparams);

        // Moderna
        list($insql, $inparams) = static::$currentdb->get_in_or_equal($moderna_modules);
        $sql = "
                UPDATE {{$reporting_table}} rcc
                LEFT JOIN (
                SELECT userid, MIN(timemodified) completiontime
                FROM {{$cmcompletionstable}} 
                WHERE completionstate IN (1,2)
                AND coursemoduleid {$insql}
                GROUP BY userid
                ) as cmc
                ON cmc.userid = rcc.userid
                SET moderna_date = cmc.completiontime,
                moderna_completed = 1,
                moderna_duration = cmc.completiontime - rcc.moderna_enrol_time
                WHERE (rcc.moderna_date = 0 OR rcc.moderna_date IS NULL)
                AND moderna_enrolled = 1
                AND cmc.completiontime IS NOT NULL
        ";
        static::$currentdb->execute($sql, $inparams);

        // Pfizer Paediatric
        list($insql, $inparams) = static::$currentdb->get_in_or_equal($pfizer2_modules);
        $sql = "
                UPDATE {{$reporting_table}} rcc
                LEFT JOIN (
                SELECT userid, MIN(timemodified) completiontime
                FROM {{$cmcompletionstable}} 
                WHERE completionstate IN (1,2)
                AND coursemoduleid {$insql}
                GROUP BY userid
                ) as cmc
                ON cmc.userid = rcc.userid
                SET pfizer2_date = cmc.completiontime,
                pfizer2_completed = 1,
                pfizer2_duration = cmc.completiontime - rcc.pfizer2_enrol_time
                WHERE (rcc.pfizer2_date = 0 OR rcc.pfizer2_date IS NULL)
                AND pfizer2_enrolled = 1
                AND cmc.completiontime IS NOT NULL
        ";
        static::$currentdb->execute($sql, $inparams);
    }

    /**
     * Returns the user type based on the AHPRA number
     *
     * @param $ahpra_number
     * @return string
     */
    public static function get_user_type_from_ahpra_number($ahpra_number)
    {
        if (strpos($ahpra_number, 'MED') === 0) {
            return self::USER_TYPE_MED;
        }

        if (strpos($ahpra_number, 'NMW') === 0) {
            return self::USER_TYPE_NUR;
        }

        if (strpos($ahpra_number, 'PAR') === 0) {
            return self::USER_TYPE_PAR;
        }

        if (strpos($ahpra_number, 'PHA') === 0) {
            return self::USER_TYPE_PHA;
        }

        if (strpos($ahpra_number, 'ATS') === 0) {
            return self::USER_TYPE_ATSI;
        }

        return self::USER_TYPE_NO_AHPRA_ID;
    }

    /**
     * Sets the module completion time
     *
     * @param $modules
     * @param $record
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function set_module_completion_time(&$record, $modules, $type = 'admin_cm')
    {
        list($insql, $inparams) = static::$currentdb->get_in_or_equal($modules);
        $cmcompletionstable = static::$cmcompletionstable;
        $completion         = static::$currentdb->get_record_select(
            $cmcompletionstable,
            "userid = {$record->userid} AND completionstate IN (1,2) AND coursemoduleid $insql",
            $inparams,
            'MIN(timemodified) timecompleted'
        );

        if (!empty($completion->timecompleted)) {
            $record->{$type . '_completed'} = 1;
            $record->{$type . '_date'}      = $completion->timecompleted;
            $record->{$type . '_duration'}  = $completion->timecompleted - $record->account_created;
        }
    }


    /**
     * Returns the state code from index
     *
     * @param $code
     * @return string
     */
    public static function get_state($index)
    {
        if (empty($index)) {
            return '';
        }

        $states = ['ACT', 'TAS', 'WA', 'NT', 'SA', 'VIC', 'NSW', 'QLD'];

        if (!empty($states[$index])) {
            return $states[$index];
        }

        if (in_array($index, $states)) {
            return $index;
        }

        return '';
    }

    /**
     * Returns the sync time
     *
     * @return int
     * @throws \dml_exception
     */
    public static function get_sync_time()
    {
        get_config('report_trainingenrolment', 'dashboard_sync_time');
        if (empty($sync_time)) {
            $sync_time = self::DEFAULT_SYNC_TIME * 3; // By default we should only sync stuff that has happened in the last 24 hours
        }

        return time() - $sync_time;
    }

    /**
     * Updates the enrolment status
     *
     * @param $record
     * @return bool
     * @throws \dml_exception
     */
    public static function set_cohorts_and_enrolment_status()
    {
        $cohortmemberstable = static::$cohortmemberstable;
        $completionstable   = self::REPORTING_TABLE;

        $sql = "UPDATE {{$completionstable}} rcc
                            LEFT JOIN (
                                select userid, GROUP_CONCAT(cohortid) as cohorts
                                FROM {{$cohortmemberstable}}
                                group by userid
                            ) as cm
                            ON cm.userid = rcc.userid
                            SET rcc.cohorts = cm.cohorts
                            WHERE rcc.cohorts IS NULL";
        static::$currentdb->execute($sql);

        // Admin staff
        $sql = "
                UPDATE {{$completionstable}}
                SET admin_cm_enrolled = 1,
                admin_cm_enrol_time = account_created
                WHERE cohorts LIKE '%1%'";
        static::$currentdb->execute($sql);

        // Immunising professionals
        $sql = "
                UPDATE {{$completionstable}}
                SET full_cm_enrolled = 1,
                full_cm_enrol_time = account_created
                WHERE cohorts LIKE '%2%'";
        static::$currentdb->execute($sql);

        return true;
    }

    /**
     * Adds the data to the hits table
     *
     * @throws \dml_exception
     */
    public static function generate_hits_data($starttime = null)
    {
        global $remotedb;

        logger::info('Starting the hits table');

        date_default_timezone_set("Australia/Melbourne");
        $currentdate        = date('d-m-Y');
        $today              = strtotime($currentdate);
        $yesterday          = strtotime($currentdate) - 86400;
        $daybeforeyesterday = strtotime($currentdate) - (86400 * 2);

        $hits_daybeforeyesterday = $remotedb->count_records_select(
            'logstore_standard_log',
            "timecreated >= :daybeforeyesterday AND timecreated <= :yesterday",
            compact('daybeforeyesterday', 'yesterday'),
            'COUNT(userid)'
        );

        $hits_yesterday = $remotedb->count_records_select(
            'logstore_standard_log',
            "timecreated >= :yesterday AND timecreated <= :today",
            compact('yesterday', 'today'),
            'COUNT(userid)'
        );

        if (!$record = static::$currentdb->get_record(self::HITS_TABLE,
            ['day' => date('d-m-Y', $daybeforeyesterday)])) {
            $record               = new \stdClass();
            $record->timemodified = time();
            $record->day          = date('d-m-Y', $daybeforeyesterday);
            $record->hits         = $hits_daybeforeyesterday;

            static::$currentdb->insert_record(self::HITS_TABLE, $record);
        } else {
            $record->hits         = $hits_daybeforeyesterday;
            $record->timemodified = time();

            static::$currentdb->update_record(self::HITS_TABLE, $record);
        }

        if (!$record = static::$currentdb->get_record(self::HITS_TABLE, ['day' => date('d-m-Y', $yesterday)])) {
            $record               = new \stdClass();
            $record->timemodified = time();
            $record->day          = date('d-m-Y', $yesterday);
            $record->hits         = $hits_yesterday;

            static::$currentdb->insert_record(self::HITS_TABLE, $record);
        } else {
            $record->hits         = $hits_yesterday;
            $record->timemodified = time();

            static::$currentdb->update_record(self::HITS_TABLE, $record);
        }

        logger::info('Hits table updated successfully');
    }

    /**
     * Updates the first access date in the reporting table
     *
     * @throws \dml_exception
     */
    public static function update_firstaccess_date()
    {
        logger::info('Updating the firstaccess date');
        $sql = "UPDATE {report_covid_completions} cc
                INNER JOIN {user} u
                ON cc.userid = u.id
                SET cc.firstaccess = u.firstaccess 
                WHERE cc.firstaccess <> u.firstaccess
                AND u.firstaccess is NOT NULL
                AND u.firstaccess <> 0";

        static::$currentdb->execute($sql);

        logger::info('Firstaccess date updated');
    }

    /**
     * Returns the scorm package start date for the user
     *
     * @param        $userid
     * @param string $type
     * @return bool|mixed
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function get_scorm_start_date($userid, $type = 'pfizer')
    {
        $cmids = get_config('report_trainingenrolment', $type . '_modules');

        if (empty($cmids)) {
            return false;
        }

        $cmids = explode(",", $cmids);

        $cmtable = static::$cmtable;
        list($insql, $inparams) = static::$currentdb->get_in_or_equal($cmids);
        $instances = static::$currentdb->get_records_select_menu($cmtable, "id $insql", $inparams, '', 'id,instance');

        $scormtrackstable = static::$scormtrackstable;
        list($insql, $inparams) = static::$currentdb->get_in_or_equal(array_values($instances));
        $sql = "SELECT MIN(`value`) as starttime from {{$scormtrackstable}} WHERE element LIKE 'x.start.time' AND scormid {$insql} AND userid = ? ";
        array_push($inparams, $userid);
        $startdate = static::$currentdb->get_field_sql($sql, $inparams);

        if (empty($startdate)) {
            return false;
        }

        return $startdate;
    }


    /**
     * Copies the missing table data from remote
     *
     * @return bool
     * @throws \dml_exception
     */
    private static function copy_tables_from_remote()
    {
        global $remotedb, $CFG;

        logger::info('Starting the copy table task from remote db.');
        $remotedbhost = get_config('report_trainingenrolment', 'dbhost');
        $remotedbname = get_config('report_trainingenrolment', 'dbname');
        $remotedbuser = get_config('report_trainingenrolment', 'dbuser');
        $remotedbpass = get_config('report_trainingenrolment', 'dbpass');

        if (empty($remotedbhost) || empty($remotedbname) || empty($remotedbuser) || empty($remotedbpass)) {
            logger::warn('Remote DB not configured. Exiting early!!');
            $remotedb = static::$currentdb;

            return false;
        }

        $dbclass  = get_class(static::$currentdb);
        $remotedb = new $dbclass();
        $remotedb->connect($remotedbhost, $remotedbuser, $remotedbpass, $remotedbname, $CFG->prefix);

        // NOW copy tables
        static::$usertable           = 'user_rt';
        static::$userattributestable = 'auth_custom_user_attributes_rt';
        static::$cmtable             = 'course_modules_rt';
        static::$cmcompletionstable  = 'course_modules_completion_rt';
        static::$cohortmemberstable  = 'cohort_members_rt';
        static::$logstore            = 'logstore_standard_log_rt';
        static::$scormtrackstable    = 'scorm_scoes_track_rt';
        static::$userlastaccess      = 'user_lastaccess_rt';

        self::check_and_create_table_for_remotedb_with_copy('user', static::$usertable);
        self::check_and_create_table_for_remotedb_with_copy('auth_custom_user_attributes',
            static::$userattributestable);
        self::check_and_create_table_for_remotedb_with_copy('course_modules', static::$cmtable);
        self::udpate_course_modules_completion_table_incomplete_records();
        self::check_and_create_table_for_remotedb_with_copy('course_modules_completion', static::$cmcompletionstable);
        self::check_and_create_table_for_remotedb_with_copy('cohort_members', static::$cohortmemberstable);
        self::check_and_create_table_for_remotedb_with_copy('scorm_scoes_track', static::$scormtrackstable);
        self::check_and_create_table_for_remotedb_with_copy('user_lastaccess', static::$userlastaccess);
        //self::check_and_create_table_for_remotedb_with_copy('logstore_standard_log', static::$userattributestable);

        logger::info('Copy table task from remote db completed successfully');
    }


    /**
     * Check if the remote table exists create one if it doesn't exist
     *
     * @param $table
     * @return bool
     * @throws \ddl_exception
     * @throws \dml_exception
     */
    private static function check_and_create_table_for_remotedb_with_copy($table, $remotetable)
    {
        global $CFG, $remotedb;

        $dbman = static::$currentdb->get_manager();
        if (!$dbman->table_exists($remotetable)) {
            logger::info("Table '{$remotetable}' doesn't exist. Creating now!!!");
            $usertable = $CFG->prefix . $remotetable;
            $sql       = "CREATE TABLE {$usertable} AS SELECT * from {{$table}}";
            static::$currentdb->execute($sql);
            static::$currentdb->execute("TRUNCATE TABLE {$usertable}");
        } else {
            logger::info("Table '{$remotetable}' exists.");
        }

        if ($table == 'scorm_scoes_track') {
            return self::create_and_copy_scorm_tracks_table($table, $remotetable);
        }

        if ($table == 'user_lastaccess') {
            self::update_lastaccess_table();
            logger::info('Now adding new records in the user_lastaccess_rt table');
        }

        // Get the max userid and copy user table
        $maxid = static::$currentdb->get_field($remotetable, 'MAX(id) id', []);
        logger::info('Max id found: ' . $maxid);
        if (empty($maxid)) {
            $maxid = 0;
        }

        $sql = "SELECT * FROM {{$table}} WHERE id > :maxid";

        $missingrecords = $remotedb->get_recordset_sql($sql, compact('maxid'));
        if (!$missingrecords->valid()) {
            return true;
        }

        logger::info('Missing records found. Now copying');

        foreach ($missingrecords as $record) {
            static::$currentdb->import_record($remotetable, $record);
        }

        $missingrecords->close();

        return true;
    }

    /**
     * Create the SCORM table copy
     *
     * @param $table
     * @param $remotetable
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function create_and_copy_scorm_tracks_table($table, $remotetable)
    {
        global $remotedb;
        // Get the max userid and copy user table
        $maxid = static::$currentdb->get_field($remotetable, 'MAX(id) id', []);
        logger::info('Max id found: ' . $maxid);
        $users = static::$currentdb->get_records_select(static::$usertable, "id > 1");
        if (empty($maxid)) {
            // Fetch the records using mysql recordset
            foreach ($users as $user) {
                $sql     = "SELECT * FROM {{$table}} WHERE id > :maxid and element LIKE 'x.start.time' and userid = :userid";
                $params  = [
                    'maxid'  => 0,
                    'userid' => $user->id
                ];
                $records = $remotedb->get_records_sql($sql, $params);
                foreach ($records as $record) {
                    static::$currentdb->import_record($remotetable, $record);
                }
            }

            return true;
        }

        $sql = "SELECT * FROM {{$table}} WHERE id > :maxid and element LIKE 'x.start.time'";

        $missingrecords = $remotedb->get_recordset_sql($sql, compact('maxid'));
        if (!$missingrecords->valid()) {
            return true;
        }

        logger::info('Missing records found. Now copying');

        foreach ($missingrecords as $record) {
            static::$currentdb->import_record($remotetable, $record);
        }

        $missingrecords->close();

        return true;
    }


    /**
     * Sets the
     * @throws \dml_exception
     */
    private static function set_scorm_enrolment_and_startdates()
    {
        // Pfizer
        $scormtrackstable = self::$scormtrackstable;
        $completionstable = self::REPORTING_TABLE;
        $sql              = "UPDATE {{$completionstable}} rcc
                LEFT JOIN (
                    SELECT userid, MIN(`value`) as starttime
                    FROM {{$scormtrackstable}} sst
                    WHERE sst.scormid in (7,14)
                    GROUP By userid
                ) as sct
                ON rcc.userid = sct.userid
                SET rcc.pfizer_enrolled = 1, pfizer_enrol_time = sct.starttime
                WHERE sct.starttime IS NOT NULL";
        static::$currentdb->execute($sql, []);

        // AZ
        $sql = "UPDATE {{$completionstable}} rcc
                LEFT JOIN (
                    SELECT userid, MIN(`value`) as starttime
                    FROM {{$scormtrackstable}} sst
                    WHERE sst.scormid in (8,15)
                    GROUP By userid
                ) as sct
                ON rcc.userid = sct.userid
                SET rcc.astrazeneca_enrolled = 1, astrazeneca_enrol_time = sct.starttime
                WHERE sct.starttime IS NOT NULL";
        static::$currentdb->execute($sql, []);

        // Moderna
        $sql = "UPDATE {{$completionstable}} rcc
                LEFT JOIN (
                    SELECT userid, MIN(`value`) as starttime
                    FROM {{$scormtrackstable}} sst
                    WHERE sst.scormid in (16)
                    GROUP By userid
                ) as sct
                ON rcc.userid = sct.userid
                SET rcc.moderna_enrolled = 1, moderna_enrol_time = sct.starttime
                WHERE sct.starttime IS NOT NULL";
        static::$currentdb->execute($sql, []);

        // Pfizer Paediatric
        $sql = "UPDATE {{$completionstable}} rcc
                LEFT JOIN (
                    SELECT userid, MIN(`value`) as starttime
                    FROM {{$scormtrackstable}} sst
                    WHERE sst.scormid in (17)
                    GROUP By userid
                ) as sct
                ON rcc.userid = sct.userid
                SET rcc.pfizer2_enrolled = 1, pfizer2_enrol_time = sct.starttime
                WHERE sct.starttime IS NOT NULL";
        static::$currentdb->execute($sql, []);
    }

    /**
     * Fix the states
     *
     * @throws \dml_exception
     */
    private static function fix_states()
    {
        logger::info('Fixing states');

        $reportingtable = self::REPORTING_TABLE;
        $sql            = "UPDATE {{$reportingtable}} SET home_state = ? WHERE home_state = ?";
        $states         = ['ACT', 'TAS', 'WA', 'NT', 'SA', 'VIC', 'NSW', 'QLD'];
        foreach ($states as $index => $state) {
            static::$currentdb->execute($sql, [$state, $index]);
        }

        logger::info('States fixed successfully!!!');
    }

    /**
     * Updates the deleted users in the user table
     *
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function update_deleted_users()
    {
        global $remotedb, $DB;

        $records         = $remotedb->get_records_select('user', "deleted = :deleted", ['deleted' => 1]);
        $reporting_table = self::REPORTING_TABLE;
        $usertable       = self::$usertable;

        logger::info('found ' . count($records) . ' deleted records');

        if (empty($records)) {
            logger::info('No deleted users found');

            return true;
        }

        $ids = [];
        foreach ($records as $record) {
            $ids[] = $record->id;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($ids);
        $sql = "UPDATE {{$usertable}} SET deleted = 1 WHERE id $insql";
        static::$currentdb->execute($sql, $inparams);

        $sql = "UPDATE {{$reporting_table}} SET deleted = 1 WHERE userid $insql";

        static::$currentdb->execute($sql, $inparams);

        logger::info('Deleted records updated successfully');
    }

    /**
     * Updates the completion date for incomplete records
     * in the course_module_completion table
     *
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function udpate_course_modules_completion_table_incomplete_records()
    {
        global $DB, $remotedb;

        // Check the ones that have not been marked complete and sync those
        $incompletes   = static::$currentdb->get_records_select(self::$cmcompletionstable, "completionstate = 0", null,
            '', 'id');
        $incompleteids = [];
        foreach ($incompletes as $incomplete) {
            $incompleteids[] = $incomplete->id;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($incompleteids);

        $completions = $remotedb->get_records_select('course_modules_completion',
            "completionstate in (1,2) and id $insql", $inparams);

        if (empty($completions)) {
            return true;
        }


        foreach ($completions as $completion) {
            static::$currentdb->update_record(static::$cmcompletionstable, $completion);
        }
    }


    /**
     * Updates the user_lastaccess_rt_table
     *
     * @return bool
     */
    private static function update_lastaccess_table()
    {
        global $remotedb;

        logger::info('Updating the user lastaccess table');

        // Get the records that have changed since the max time from current table
        $useraccesstable = self::$userlastaccess;
        $maxlastaccess_sql = "SELECT MAX(timeaccess) as lastaccess from {{$useraccesstable}}";
        $maxlastaccess = static::$currentdb->get_field_sql($maxlastaccess_sql);
        $maxlastaccess = $maxlastaccess - (86400 * 2);
        $lastaccessrecords = $remotedb->get_records_select('user_lastaccess', "courseid = 2 AND timeaccess > :maxlastaccess", compact('maxlastaccess'));

        if (empty($lastaccessrecords)) {
            logger::info('No lastaccess updates found');

            return  true;
        }

        foreach ($lastaccessrecords as $record) {
            static::$currentdb->update_record(self::$userlastaccess, $record);
        }

        logger::success('Updated '.count($lastaccessrecords).' in the user_lastacess_rt table');
    }
}
