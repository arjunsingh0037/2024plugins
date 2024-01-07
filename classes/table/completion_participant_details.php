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
 * @package   trainingenrolment
 * @category  report plugin
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_trainingenrolment\table;


use report_trainingenrolment\helper\adhoc_report_data_helper;

require_once $CFG->libdir . "/tablelib.php";

class completion_participant_details extends \table_sql
{
    protected $table = adhoc_report_data_helper::TABLE;

    public function __construct(\moodle_url $url)
    {
        parent::__construct('completion-participant-details-table');

        $fields = "id, module, userid, firstname, lastname, ahpra_number, email, home_state, timecompleted";
        $from   = " {{$this->table}}";
        $where  = "timecompleted IS NOT NULL and timecompleted <> 0";
        $params = [];

        $this->define_baseurl($url->out(false));
        $this->define_columns([
            'module',
            'fullname',
            'ahpra_number',
            'email',
            'home_state',
            'timecompleted',
        ]);
        $this->define_headers([
            get_string('module', 'report_trainingenrolment'),
            get_string('name'),
            get_string('ahpra_number', 'report_trainingenrolment'),
            get_string('email'),
            get_string('home_state', 'report_trainingenrolment'),
            get_string('completiondate', 'report_trainingenrolment'),
        ]);

        $this->sortable(true);
        $this->collapsible(false);
        $this->set_sql($fields, $from, $where, $params);
    }

    /**
     * Overrides the default col_fullname method
     * 
     * @param object $row
     * @return string
     */
    function col_fullname($row)
    {
        return $row->firstname . ' ' . $row->lastname;
    }

    /**
     * Returns the mdodule name
     *
     * @param $row
     * @return string
     */
    public function col_module($row)
    {
        switch ($row->module) {
            case adhoc_report_data_helper::MODULE_TYPE_ADMIN:
                $module = 'Admin';
                break;
            case adhoc_report_data_helper::MODULE_TYPE_FULL:
                $module = 'Core';
                break;
            case adhoc_report_data_helper::MODULE_TYPE_PFIZER:
                $module = 'Pfizer';
                break;
            case adhoc_report_data_helper::MODULE_TYPE_AZ:
                $module = 'AstraZeneca';
                break;
            case adhoc_report_data_helper::MODULE_TYPE_MODERNA:
                $module = 'Moderna';
                break;
            default:
                $module = '';
                break;
        }

        return $module;
    }

    /**
     * Returns the date in the user format
     *
     * @param $row
     * @return string
     */
    public function col_timecompleted($row)
    {
        return userdate($row->timecompleted);
    }
}
