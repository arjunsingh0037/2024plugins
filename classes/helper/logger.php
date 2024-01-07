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
 * @package   report_trainingenrolment
 * @category  Report plugin
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mailtotrainers\helper;

class logger
{
    /**
     * Adds a success message to the log
     *
     * @param                $message
     * @param \stdClass|null $otherdata
     * @return bool
     */
    public static function success($message, \stdClass $otherdata = null)
    {
        return self::add($message, 'SUCCESS', $otherdata);
    }

    /**
     * Adds a warning message to the log
     *
     * @param                $message
     * @param \stdClass|null $otherdata
     * @return bool
     */
    public static function warn($message, \stdClass $otherdata = null)
    {
        return self::add($message, 'WARNING', $otherdata);
    }

    /**
     * Adds a fatal message to the log
     *
     * @param                $message
     * @param \stdClass|null $otherdata
     * @return bool
     */
    public static function fatal($message, \stdClass $otherdata = null)
    {
        return self::add($message, 'FATAL', $otherdata);
    }

    /**
     * Adds an INFO message to the log
     *
     * @param                $message
     * @param \stdClass|null $otherdata
     * @return bool
     */
    public static function info($message, \stdClass $otherdata = null)
    {
        return self::add($message, 'INFO', $otherdata);
    }

    /**
     * Adds a message to the log
     *
     * @param                $message
     * @param string         $type
     * @param \stdClass|null $otherdata
     * @return bool
     */
    public static function add($message, $type = 'INFO', \stdClass $otherdata = null)
    {
        $message = "{$type}: {$message}";
        mtrace($message);

        return true;
    }
}
