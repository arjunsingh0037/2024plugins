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
 * Scheduled tasks for report_trainingenrolment
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_trainingenrolment\task;

use coding_exception;
use core\task\scheduled_task;
use dml_exception;
use report_trainingenrolment\helper;

class process_mail extends scheduled_task
{
    public function get_name() {
        return get_string('task:process_mail', 'report_trainingenrolment');
    }

    /**
     * Execute the scheduled task.
     *
     * Add all the users that match any existing
     * or new rules and will add them to the respective cohorts.
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    public function execute() {
        helper::processemails();
    }
}
