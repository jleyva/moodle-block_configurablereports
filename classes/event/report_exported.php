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

namespace block_configurable_reports\event;

/**
 * Event triggered when a configurable report is exported.
 *
 * @property-read array $other {
 *      - string name: the report name.
 *      - string type: the report type.
 *      - string format: the export format, such as csv or xml for the report definition.
 * }
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_exported extends report_event {

    /**
     * Init method.
     */
    protected function init() {
        parent::init();
        $this->data['crud'] = 'r';
    }

    /**
     * Validates the custom data.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['format'])) {
            throw new \coding_exception('The \'format\' value must be set in other.');
        }
    }

    /**
     * Returns the localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventreportexported', 'block_configurable_reports');
    }

    /**
     * Returns the description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' exported the configurable report with id '$this->objectid' " .
            "in the format '{$this->other['format']}'.";
    }
}
