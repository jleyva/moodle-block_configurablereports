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

        // Report display options.
        $reportdisplayoptions = [
                CR_BLOCK_DISPLAY_LIST   => get_string('displayreportsaslist', 'block_configurable_reports'),
                CR_BLOCK_DISPLAY_TILES  => get_string('displayreportsastiles', 'block_configurable_reports')
        ];
        $mform->addElement('select', 'config_displayreportsas', get_string('displayreportsas', 'block_configurable_reports'), $reportdisplayoptions);

        // Subtitle option for tile reports.
        $mform->addElement('text', 'config_subtitle', 'Subtitle');
        $mform->setType('config_subtitle', PARAM_TEXT);
        $mform->disabledIf('config_subtitle', 'config_displayreportsas', 'eq', CR_BLOCK_DISPLAY_LIST);

        // Option to display global reports. I think this was already here.
        $mform->addElement('selectyesno', 'config_displayglobalreports', get_string('displayglobalreports', 'block_configurable_reports'));
        $mform->setDefault('config_displayglobalreports', 1);
        $mform->disabledIf('config_displayglobalreports', 'config_displayreportsas', 'eq', CR_BLOCK_DISPLAY_TILES);

        // End of block instance editing.
    }
}