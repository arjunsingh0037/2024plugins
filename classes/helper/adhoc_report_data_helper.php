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
 * This class processes the data for the adhoc report
 *
 * @package   report_trainingenrolment
 * @category  Report plugin
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_trainingenrolment\helper;


use report_trainingenrolment\LingelExcelFormat;

class adhoc_report_data_helper
{
    const TABLE = 'report_covid_p_details';
    const MODULE_TYPE_ADMIN = 1;
    const MODULE_TYPE_FULL = 2;
    const MODULE_TYPE_PFIZER = 3;
    const MODULE_TYPE_AZ = 4;
    const MODULE_TYPE_MODERNA = 5;

    public static function process()
    {
        global $DB;

        $completionstable  = data_helper::REPORTING_TABLE;
        $participantstable = self::TABLE;
        // Check if the table exists
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists(self::TABLE)) {
            logger::fatal('Participant details table does not exist. Skipping');

            return true;
        }

        // Insert the missing ones
        $sql = "SELECT 
                    * 
                FROM {{$completionstable}}";

        $recordset = $DB->get_recordset_sql($sql);

        if (!$recordset->valid()) {
            logger::warn('No records found.');
            $recordset->close();

            return true;
        }

        $DB->delete_records(self::TABLE);

        foreach ($recordset as $record) {
            $rows = [];

            $row                = new \stdClass();
            $row->userid        = $record->userid;
            $row->firstname     = $record->firstname;
            $row->lastname      = $record->lastname;
            $row->ahpra_number  = $record->ahpra_number;
            $row->home_state    = $record->home_state;
            $row->home_postcode = $record->home_postcode;
            $row->user_type     = $record->user_type;
            $row->email         = $record->email;

            if ($record->admin_cm_enrolled) {
                $rowmod                = clone $row;
                $rowmod->module        = self::MODULE_TYPE_ADMIN;
                $rowmod->timecompleted = $record->admin_cm_date;

                $rows[] = $rowmod;
            }

            if ($record->full_cm_enrolled) {
                $rowmod                = clone $row;
                $rowmod->module        = self::MODULE_TYPE_FULL;
                $rowmod->timecompleted = $record->full_cm_date;

                $rows[] = $rowmod;
            }

            if ($record->pfizer_enrolled) {
                $rowmod                = clone $row;
                $rowmod->module        = self::MODULE_TYPE_PFIZER;
                $rowmod->timecompleted = $record->pfizer_date;

                $rows[] = $rowmod;
            }

            if ($record->astrazeneca_enrolled) {
                $rowmod                = clone $row;
                $rowmod->module        = self::MODULE_TYPE_AZ;
                $rowmod->timecompleted = $record->astrazeneca_date;

                $rows[] = $rowmod;
            }

            if ($record->moderna_enrolled) {
                $rowmod                = clone $row;
                $rowmod->module        = self::MODULE_TYPE_MODERNA;
                $rowmod->timecompleted = $record->moderna_date;

                $rows[] = $rowmod;
            }

            $DB->insert_records(self::TABLE, $rows);
        }

        $recordset->close();
    }

    /**
     *
     */
    public static function get_data_by_postcodes()
    {
        global $DB;

        $sql = "SELECT home_state, user_type, home_postcode, COUNT(*) completions FROM {report_covid_p_details}
        WHERE module = 2
        AND timecompleted IS NOT NULL
        AND timecompleted NOT LIKE ''
        GROUP BY home_state,user_type,home_postcode";

        $recordset = $DB->get_recordset_sql($sql);

        $data = [];
        foreach ($recordset as $record) {
            if (empty($data[$record->home_state])) {
                $data[$record->home_state] = [];
            }

            if (empty($data[$record->home_state][$record->home_postcode])) {
                $data[$record->home_state][$record->home_postcode] = [];
            }

            if (empty($data[$record->home_state][$record->home_postcode][$record->user_type])) {
                $data[$record->home_state][$record->home_postcode][$record->user_type] = 0;
            }

            $data[$record->home_state][$record->home_postcode][$record->user_type] += $record->completions;
        }

        $recordset->close();

        $sql = "SELECT home_state, user_type, home_postcode, COUNT(*) completions FROM {report_covid_p_details}
        WHERE module = 1
        AND timecompleted IS NOT NULL
        AND timecompleted NOT LIKE ''
        AND user_type = 0
        GROUP BY home_state,user_type,home_postcode";

        $recordset = $DB->get_recordset_sql($sql);

        foreach ($recordset as $record) {
            if (empty($data[$record->home_state])) {
                $data[$record->home_state] = [];
            }

            if (empty($data[$record->home_state][$record->home_postcode])) {
                $data[$record->home_state][$record->home_postcode] = [];
            }

            if (empty($data[$record->home_state][$record->home_postcode]['admin'])) {
                $data[$record->home_state][$record->home_postcode]['admin'] = 0;
            }

            $data[$record->home_state][$record->home_postcode]['admin'] += $record->completions;
        }

        $recordset->close();


        return $data;
    }

    /**
     * Returns the postcode data with state mapping (accurate one)
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_data_by_postcodes_with_state_mapping()
    {
        global $DB, $CFG;

        require_once $CFG->libdir . "/csvlib.class.php";

        $sql = "SELECT user_type, home_postcode, COUNT(*) completions FROM {report_covid_p_details}
        WHERE module = 2
        AND timecompleted IS NOT NULL
        AND timecompleted NOT LIKE ''
        GROUP BY user_type,home_postcode";

        $recordset = $DB->get_recordset_sql($sql);

        $data = [];
        foreach ($recordset as $record) {
            if (empty($data[$record->home_postcode])) {
                $data[$record->home_postcode] = [];
            }

            if (empty($data[$record->home_postcode][$record->user_type])) {
                $data[$record->home_postcode][$record->user_type] = 0;
            }

            $data[$record->home_postcode][$record->user_type] += $record->completions;
        }

        $recordset->close();

        $sql = "SELECT user_type, home_postcode, COUNT(*) completions FROM {report_covid_p_details}
        WHERE module = 1
        AND timecompleted IS NOT NULL
        AND timecompleted NOT LIKE ''
        AND user_type = 0
        GROUP BY user_type,home_postcode";

        $recordset = $DB->get_recordset_sql($sql);

        foreach ($recordset as $record) {
            if (empty($data[$record->home_postcode])) {
                $data[$record->home_postcode] = [];
            }

            if (empty($data[$record->home_postcode]['admin'])) {
                $data[$record->home_postcode]['admin'] = 0;
            }

            $data[$record->home_postcode]['admin'] += $record->completions;
        }

        $recordset->close();

        $postcodecontent = file_get_contents($CFG->dirroot . '/report/trainingenrolment/PostcodeState20211130.csv');
        $reader          = new \csv_import_reader(\csv_import_reader::get_new_iid('report_postcodemapping'),
            'report_postcodemapping');
        $reader->load_csv_content($postcodecontent, null, null);
        $reader->init();

        $postcodes = [];
        while ($line = $reader->next()) {
            $postcodes[str_pad($line[0], 4, "0")] = $line[1];
        }

        $state_data = [];
        foreach ($data as $postcode => $postcode_data) {
            if (empty($postcodes[$postcode])) {
                continue;
            }

            if (empty($state_data[$postcodes[$postcode]])) {
                $state_data[$postcodes[$postcode]] = [];
            }

            if (empty($state_data[$postcodes[$postcode]][$postcode])) {
                $state_data[$postcodes[$postcode]][$postcode] = [];
            }

            foreach ($postcode_data as $user_type => $completions) {
                if (empty($state_data[$postcodes[$postcode]][$postcode][$user_type])) {
                    $state_data[$postcodes[$postcode]][$postcode][$user_type] = 0;
                }

                $state_data[$postcodes[$postcode]][$postcode][$user_type] += $completions;
            }
        }


        return $state_data;
    }

    /**
     * Downloads the Postcode data in excel
     */
    public static function download_postcode_excel()
    {
        global $CFG;

        require_once $CFG->libdir . "/excellib.class.php";

        $headings = [
            'Postcode',
            'Admin (Modules 1-3)',
            'Non-Ahpra',
            'Medical Practitioner',
            'Nurse/Midwife',
            'Paramedic',
            'Pharmacist',
            'ATSI',
        ];

        $state_data = self::get_data_by_postcodes_with_state_mapping();

        $format = new LingelExcelFormat();
        $format->set_bg_color('#000000');
        $format->set_color('#ffffff');

        $format_col = new LingelExcelFormat();
        $format_col->fill_none();
        $format_col->set_color('#000000');

        $workbook   = new \MoodleExcelWorkbook('postcode_report');
        $state_data = self::get_data_by_postcodes_with_state_mapping();

        foreach ($state_data as $state => $postcode_data) {
            $sheet = $workbook->add_worksheet($state);
            $col   = 0;
            $sheet->set_column(0, 7, 20, $format);
            foreach ($headings as $heading) {
                $sheet->write(0, $col, $heading);
                $col++;
            }
            $row = 1;
            $sheet->set_column(0, 7, 20, $format_col);
            foreach ($postcode_data as $postcode => $user_type) {
                $sheet->write($row, 0, $postcode);                                                           // Postcode
                if (!empty($user_type['admin'])) {
                    $sheet->write($row, 1,
                        $user_type['admin']);                                             // Admin modules
                }

                if (!empty($user_type[data_helper::USER_TYPE_NO_AHPRA_ID])) {
                    $sheet->write($row, 2,
                        $user_type[data_helper::USER_TYPE_NO_AHPRA_ID]);                  // No AHPRA ID
                }

                if (!empty($user_type[data_helper::USER_TYPE_MED])) {
                    $sheet->write($row, 3,
                        $user_type[data_helper::USER_TYPE_MED]);                          // Medical Practitioner
                }

                if (!empty($user_type[data_helper::USER_TYPE_NUR])) {
                    $sheet->write($row, 4, $user_type[data_helper::USER_TYPE_NUR]);                          // Nurse
                }

                if (!empty($user_type[data_helper::USER_TYPE_PAR])) {
                    $sheet->write($row, 5,
                        $user_type[data_helper::USER_TYPE_PAR]);                          // Paramedic
                }

                if (!empty($user_type[data_helper::USER_TYPE_PHA])) {
                    $sheet->write($row, 6,
                        $user_type[data_helper::USER_TYPE_PHA]);                          // Pharmacist
                }

                if (!empty($user_type[data_helper::USER_TYPE_ATSI])) {
                    $sheet->write($row, 7, $user_type[data_helper::USER_TYPE_ATSI]);                         // ATSI
                }

                $row++;
            }
        }

        $workbook->close();
        die;
    }
}
