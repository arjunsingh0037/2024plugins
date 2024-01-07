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
 * Manages the data for the training enrolment report table.
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_trainingenrolment;

defined('MOODLE_INTERNAL') || die;

use context;

/**
 * Class that manages selected values as well as generates SQL for
 * the training enrol report.
 *
 * @package   report_trainingenrolment
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class trainingenrolment {

    /** @var context context of the report */
    protected $context;

    /** @var \moodle_url baseurl of the report */
    protected $baseurl;

    /** @var array parameters for filtering the report */
    protected $params;

    /** @var output\filters filter object for the report */
    protected $filters;

    /**
     * Set up the trainingenrolment class.
     *
     * @param context $context context the report is running in.
     * @param output\filters $filters filter object for the report.
     * @param \moodle_url $baseurl base url for the report.
     */
    public function __construct($context, $baseurl, $filters) {

    }

    /**
     * Gets the fields to SELECT for the SQL query.
     *
     * @return string
     */
    public function get_fields_sql() {

    }

    /**
     * Fetches the FROM SQL for the query.
     *
     * @return string
     */
    public function get_from_sql() {

    }

    /**
     * Get the params based on any filters that have been set.
     * Should only be called after get_where_sql.
     *
     * @return array
     */
    public function get_params() {
        $params = [];
        return $params;
    }

    /**
     * Gets the WHERE clause and sets up report parameters.
     *
     * @return string
     */
    public function get_where_sql() {

    }

    /**
     * Getter for baseurl.
     *
     * @return \moodle_url
     */
    public function get_baseurl() {

    }

    /**
     * Getter for context.
     *
     * @return context
     */
    public function get_context() {

    }

}
