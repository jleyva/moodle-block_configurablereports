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

use context;
use context_course;
use context_system;
use core\event\base;
use moodle_url;
use stdClass;

/**
 * Base class for the events about a configurable report.
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class report_event extends base {

    /**
     * Create the event for a report.
     *
     * @param stdClass $report The report record.
     * @param context|null $context The context the report is used in, the context of the report course by default.
     * @param array $other Extra data for the event.
     * @return static
     */
    public static function create_from_report(stdClass $report, ?context $context = null, array $other = []): self {
        if (!$context) {
            $context = ($report->courseid == SITEID) ? context_system::instance() :
                (context_course::instance($report->courseid, IGNORE_MISSING) ?: context_system::instance());
        }

        $event = static::create([
            'objectid' => $report->id,
            'context' => $context,
            'other' => $other + ['name' => $report->name, 'type' => $report->type],
        ]);
        $event->add_record_snapshot('block_configurable_reports', $report);

        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'block_configurable_reports';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns the URL of the report.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/blocks/configurable_reports/viewreport.php', ['id' => $this->objectid]);
    }

    /**
     * Validates the custom data.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['type'])) {
            throw new \coding_exception('The \'type\' value must be set in other.');
        }
    }

    /**
     * Reports are not restored, so the report id is not mapped.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'block_configurable_reports', 'restore' => base::NOT_MAPPED];
    }

    /**
     * The other data is not mapped.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
