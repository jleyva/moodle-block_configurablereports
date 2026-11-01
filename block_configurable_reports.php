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
 * Configurable Reports a Moodle block for creating customizable reports
 *
 * @copyright  2020 Juan Leyva <juan@moodle.com>
 * @package    block_configurable_reports
 * @author     Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class block_configurable_reports
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 */
class block_configurable_reports extends block_list {

    /**
     * Sets the block name and version number
     *
     * @return void
     **/
    public function init(): void {
        $this->title = get_string('pluginname', 'block_configurable_reports');
    }

    /**
     * Act on instance data.
     */
    public function specialization(): void {

        if ($this->config) {
            // Check if the title property exists before accessing it
            if (property_exists($this->config, 'title')) {
                $this->title = $this->config->title ? $this->config->title : get_string('pluginname', 'block_configurable_reports');
            } else {
                $this->title = get_string('pluginname', 'block_configurable_reports');
            }
        } else {
            $this->title = get_string('pluginname', 'block_configurable_reports');
            $this->config = new stdClass();
            $this->config->displayglobalreports = true;
        }
    }

    /**
     * instance_allow_config
     *
     * @return bool
     */
    public function instance_allow_config(): bool {
        return true;
    }

    /**
     * Where to add the block
     *
     * @return array
     **/
    public function applicable_formats(): array {
        return [
            'site' => true,
            'course' => true,
            'my' => true,
        ];
    }

    /**
     * Global Config?
     *
     * @return boolean
     **/
    public function has_config(): bool {
        return true;
    }

    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with the contents
     **/
    public function get_content() {
        global $DB, $USER, $CFG, $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->icons = [];

        if (!isloggedin()) {
            return $this->content;
        }

        require_once($CFG->dirroot . "/blocks/configurable_reports/locallib.php");
        $course = $DB->get_record('course', ['id' => $COURSE->id]);

        if (!$course) {
            throw new moodle_exception('coursedoesnotexists');
        }

        if ($course->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($course->id);
        }

        // Site (Shared) reports.
        if (!empty($this->config->displayglobalreports)) {
            $reports = $DB->get_records('block_configurable_reports', ['global' => 1], 'name ASC');

            if ($reports) {
                foreach ($reports as $report) {
                    if ($report->visible && cr_check_report_permissions($report, $USER->id, $context)) {
                        $rname = format_string($report->name);
                        $params = ['id' => $report->id, 'courseid' => $course->id];
                        $url = new moodle_url('/blocks/configurable_reports/viewreport.php', $params);
                        $attrs = ['alt' => $rname];
                        $this->content->items[] = html_writer::link($url, $rname, $attrs);
                    }
                }
                if (!empty($this->content->items)) {
                    $this->content->items[] = '========';
                }
            }
        }

        // Course reports.
        if (!property_exists($this, 'config')
            || !isset($this->config->displayreportslist)
            || $this->config->displayreportslist) {
            $reports = $DB->get_records('block_configurable_reports', ['courseid' => $course->id], 'name ASC');

            if ($reports) {
                foreach ($reports as $report) {
                    if (!$report->global && $report->visible && cr_check_report_permissions($report, $USER->id, $context)) {
                        $rname = format_string($report->name);
                        $params = ['id' => $report->id, 'courseid' => $course->id];
                        $url = new moodle_url('/blocks/configurable_reports/viewreport.php', $params);
                        $attrs = ['alt' => $rname];
                        $this->content->items[] = html_writer::link($url, $rname, $attrs);
                    }
                }
                if (!empty($this->content->items)) {
                    $this->content->items[] = '========';
                }
            }
        }

        if (has_capability('block/configurable_reports:managereports', $context)
            || has_capability('block/configurable_reports:manageownreports', $context)) {
            $url = new moodle_url('/blocks/configurable_reports/managereport.php', ['courseid' => $course->id]);
            $linktext = get_string('managereports', 'block_configurable_reports');
            $this->content->items[] = html_writer::link($url, $linktext);
        }

        return $this->content;
    }

}
