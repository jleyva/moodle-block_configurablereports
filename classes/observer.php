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
 * Configurable Reports - A Moodle block for creating customizable reports
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use core\event\course_deleted;

/**
 * Event observers for this block.
 *
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Delete the reports that belonged to a course that has just been deleted.
     *
     * Nothing else removes them, so they stayed in the report list of every other course, showing their own
     * course as deleted, and every link on them led to an exception.
     *
     * @param course_deleted $event
     * @return void
     */
    public static function course_deleted(course_deleted $event): void {
        global $DB;

        $DB->delete_records('block_configurable_reports', ['courseid' => $event->objectid]);
    }

}
