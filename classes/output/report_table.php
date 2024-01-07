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
 * Table definition for the training enrolment report
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace report_trainingenrolment\output;

defined('MOODLE_INTERNAL') || die;

use report_trainingenrolment\trainingenrolment;
use table_sql;

require_once("$CFG->libdir/tablelib.php");

/**
 * Class that manages how data is displayed in the training enrolment report.
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_table extends table_sql {

    public function __construct($course) {
        $uniqid = 'report-trainingenrolment' . ($course ? '-' . $course->id : '');
        parent::__construct($uniqid);

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        if (!$course) {
            $headers[] = get_string('course');
            $columns[] = 'coursename';
        }

        $columns = array_merge($columns, [
            'changetype',
            'modifierid',
            'timemodified'
        ]);

        $headers = array_merge($headers, [
            get_string('change', 'report_trainingenrolment'),
            get_string('changedby', 'report_trainingenrolment'),
            get_string('timemodified', 'report_trainingenrolment')
        ]);

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->useridfield = 'userid';
    }

    /**
     * Format the timemodified cell.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_timemodified($row) {
        return userdate($row->timemodified, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Format the change cell to show what the update was.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_changetype($row) {
        return trainingenrolment::get_change_description($row->changetype);
    }

    /**
     * Format the modifierid cell. This is the userid that made the change.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_modifierid($row) {
        $modifier = new \stdClass();
        $modifier->{$this->useridfield} = $row->modifierid;

        foreach (get_all_user_name_fields() as $namefield) {
            if (isset($row->{'modifier'.$namefield})) {
                $modifier->$namefield = $row->{'modifier'.$namefield};
            } else {
                $modifier->$namefield = null;
            }

        }

        return $this->col_fullname($modifier);
    }

    /**
     * Format the coursename cell. Generates a link to filter by course.
     *
     * @param   \stdClass $row
     * @return  string
     */
    public function col_coursename($row) {
        if ($this->download) {
            return $row->coursename;
        }
        return \html_writer::link(course_get_url($row->courseid), $row->coursename);
    }

    /**
     * @return array sql and parameters to add to where statement.
     */
    public function get_sql_where() {
        global $DB;

        $conditions = array();
        $params = array();

        if (isset($this->columns['fullname'])) {
            static $i = 0;
            $i++;

            if (!empty($this->get_initial_first())) {
                $conditions[] = $DB->sql_like('u.firstname', ':ifirstc'.$i, false, false);
                $params['ifirstc'.$i] = $this->get_initial_first().'%';
            }
            if (!empty($this->get_initial_last())) {
                $conditions[] = $DB->sql_like('u.lastname', ':ilastc'.$i, false, false);
                $params['ilastc'.$i] = $this->get_initial_last().'%';
            }
        }

        return array(implode(" AND ", $conditions), $params);
    }
}
