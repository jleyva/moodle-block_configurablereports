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
 * Configurable Reports
 * A Moodle block for creating Configurable Reports
 * @package blocks
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 */

class block_configurable_reports_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $USER, $CFG, $COURSE;

        // Tap into some cr functions.
        require_once("{$CFG->dirroot}/blocks/configurable_reports/locallib.php");

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('name'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_title', get_string('pluginname', 'block_configurable_reports'));

        // Get a bunch of cr reports.
        $reports = cr_get_my_reports($COURSE->id, $USER);

        if (empty($reports)) {
            // There were no cr reports for this user in this course.
            $mform->addElement('hidden', 'config_displaytiles', 0);
            $mform->setType('config_displaytiles', PARAM_INT);
        } else {
            // There were some reports for this user in this course. Provide an option to display as tiles.
            debugging('Make a select option for display reports as => none, a list of reports (cr default) and as tiles');
            $mform->addElement('selectyesno', 'config_displaytiles', get_string('displayastiles', 'block_configurable_reports'));
            $mform->setDefault('config_displaytiles', 0);

            // Subtitle option for tile reports.
            $mform->addElement('text', 'config_subtitle', 'Subtitle');
            $mform->setType('config_subtitle', PARAM_TEXT);
            $mform->disabledIf('config_subtitle', 'config_displaytiles', 'eq', 0);
        }

        // Option to display global reports. I think this was already here.
        $mform->addElement('selectyesno', 'config_displayglobalreports', get_string('displayglobalreports', 'block_configurable_reports'));
        $mform->setDefault('config_displayglobalreports', 1);
        $mform->disabledIf('config_displayglobalreports', 'config_displaytiles', 'eq', 1);

        // Todo: Add this later. Specific block instance controlling the reports to show.
        // Show a separate group with a list of reports that can be displayed as tiles.
        if (!empty($reports) && false) {
            $mform->addElement('header', 'heading_cr_list', get_string('tileablereports', 'block_configurable_reports'));
            $mform->setExpanded('heading_cr_list');

            // Select the reports.
            foreach ($reports as $reportid => $report) {
                // Reports that aren't visible shouldn't be selected.
                if (!$report->visible) {
                    continue;
                }

                // Get the report components.
                $reportconfig = cr_get_tilereport_config($report);

                // No config means no display.
                if (is_null($reportconfig)) {
                    continue;
                }

                // Check report tileability.
                if (!$reportconfig->tileable) {
                    continue;
                }

                // This report is tileability.
                $mform->addElement('selectyesno', "config_cr_tile_reportid_{$reportid}", $report->name);
            }
        }

        // End of block instance editing.
    }
}