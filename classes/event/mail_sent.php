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
 * Event class for report_trainingenrolment
 *
 * @package   report_trainingenrolment
 * @category  Report Plugins
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_trainingenrolment\event;

defined("MOODLE_INTERNAL") || die();

class mail_sent extends \core\event\base {
    /**
     * Init method.
     */
    protected function init() {
        $this->data["crud"]        = "r";
        $this->data["edulevel"]    = self::LEVEL_PARTICIPATING;
    }

    /**
     * Return localised event name.
     */
    public static function get_name() {
        return get_string('mailsent', 'report_trainingenrolment');
    }

    /**
     * Return localised event description.
     */
    public function get_description() {
        return "The trainingenrolment report mail has been sent to emailid  {$this->data['other']['emailid']}";
    }

    /**
     * Get URL related to the action.
     */
    public function get_url() {
        return new \moodle_url("/report/trainingenrolment/index.php");
    }
}