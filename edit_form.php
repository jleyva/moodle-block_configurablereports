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
    protected function specific_definition(\MoodleQuickForm $mform) {
        global $USER, $CFG, $COURSE;

        // Tap into some cr functions.
        require_once("{$CFG->dirroot}/blocks/configurable_reports/locallib.php");

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('name'));
        $mform->setType('config_title', PARAM_MULTILANG);
        $mform->setDefault('config_title', get_string('pluginname', 'block_configurable_reports'));

        // Get a bunch of cr reports.
        $reports = cr_get_my_reports($COURSE->id, $USER);

        if (empty($reports)) {
            // There were no cr reports for this user in this course.
            $mform->addElement('hidden', 'config_displaytiles', 0);
            $mform->setType('config_displaytiles', PARAM_INT);
        } else {
            // There were some reports for this user in this course. Provide an option to display as tiles. Todo: Use lang strings.
            $mform->addElement('selectyesno', 'config_displaytiles', 'Display tiles');
            $mform->setDefault('config_displaytiles', 0);

            // Subtitle option for tile reports. Todo: Use lang strings.
            $mform->addElement('text', 'config_subtitle', 'Subtitle');
            $mform->disabledIf('config_subtitle', 'config_displaytiles', 'eq', 0);
        }


        $mform->addElement('selectyesno', 'config_displayreportslist', get_string('displayreportslist', 'block_configurable_reports'));
        $mform->setDefault('config_displayreportslist', 1);
        $mform->disabledIf('config_displayreportslist', 'config_displaytiles', 'eq', 1);

        $mform->addElement('selectyesno', 'config_displayglobalreports', get_string('displayglobalreports', 'block_configurable_reports'));
        $mform->setDefault('config_displayglobalreports', 1);
        $mform->disabledIf('config_displayglobalreports', 'config_displaytiles', 'eq', 1);

        // Show a separate group with a list of reports that can be displayed as tiles.
        if (!empty($reports)) {
            $mform->addElement('header', 'heading_cr_list', 'List of reports');
            $mform->setExpanded('heading_cr_list');

            // Select the reports.
            foreach ($reports as $reportid => $report) {
                // Reports that aren't visible shouldn't be selected.
                if (!$report->visible) {
                    continue;
                }

                $mform->addElement('selectyesno', "config_cr_tile_reportid_{$reportid}", $report->name);
            }
        }

        // End of block instance editing.
    }
}