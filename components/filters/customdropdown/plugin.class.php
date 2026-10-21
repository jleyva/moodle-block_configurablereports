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
 * @copyright  2025 think-modular
 * @package    block_configurable_reports
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');

/**
 * Class plugin_customdropdown
 *
 * @package   block_configurable_reports
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 */
class plugin_customdropdown extends plugin_base {

    /**
     * Init
     *
     * @return void
     */
    public function init(): void {
        $this->form = true;
        $this->unique = true;
        $this->fullname = get_string('customdropdown', 'block_configurable_reports');
        $this->reporttypes = ['sql'];
    }

    /**
     * Summary
     *
     * @param object $data
     * @return string
     */
    public function summary(object $data): string {
        return get_string('customdropdown_summary', 'block_configurable_reports');
    }

    /**
     * Execute
     *
     * @param string $finalelements
     * @return array|string|string[]
     */
    public function execute($finalelements) {

        $filterdropdown = optional_param('filter_customdropdown', 0, PARAM_RAW);

        if (!$filterdropdown || $filterdropdown == 0 || $this->report->type !== 'sql') {
            return $finalelements;
        }

        return str_replace('%%FILTER_DROPDOWN%%', "AND $filterdropdown", $finalelements);
    }

    /**
     * Print filter
     *
     * @param MoodleQuickForm $mform
     * @param bool|object $formdata
     * @return void
     */
    public function print_filter(MoodleQuickForm $mform, $formdata = false): void {
        global $remotedb;

        $reportclassname = 'report_' . $this->report->type;
        $reportclass = new $reportclassname($this->report);

        if ($this->report->type !== 'sql') {
            return;
        }

        // Prepare options for the custom dropdown.
        $options = [0 => get_string('filter_all', 'block_configurable_reports')];
        $customdropdown_options = $formdata->customdropdown_options;
        $optionlines = explode("\n", $customdropdown_options);
        foreach ($optionlines as $optionline) {
            $optionparts = explode('|', $optionline);
            if (count($optionparts) < 2) {
                continue; // Skip invalid lines.
            }
            $label = trim($optionparts[0]);
            $sql = trim($optionparts[1]);
            $options[$sql] = $label;
        }

        $name = $formdata->customdropdown_name ?? get_string('customdropdown', 'block_configurable_reports');

        $mform->addElement('select', 'filter_customdropdown', $name, $options);
        $mform->setType('filter_customdropdown', PARAM_RAW);



        // $sortedcourseoptions = [];
        // $courseoptions = [];
        // $sortedcourseoptions[0] = get_string('filter_all', 'block_configurable_reports');

        // if (!empty($courselist)) {
        //     [$usql, $params] = $remotedb->get_in_or_equal($courselist);
        //     $courses = $remotedb->get_records_select('course', "id $usql", $params);

        //     foreach ($courses as $c) {
        //         $courseoptions[$c->id] = format_string($c->fullname);
        //     }

        //     asort($courseoptions);
        // }

        // $sortedcourseoptions += $courseoptions;

        // $mform->addElement('select', 'filter_courses', get_string('course'), $sortedcourseoptions);
        // $mform->setType('filter_courses', PARAM_INT);
    }

}
