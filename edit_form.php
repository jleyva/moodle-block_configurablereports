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

/** Configurable Reports
  * A Moodle block for creating Configurable Reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

class block_configurable_reports_edit_form extends block_edit_form {
    protected function specific_definition($mform) {

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('name'));
        $mform->setType('config_title', PARAM_MULTILANG);
        $mform->setDefault('config_title', get_string('pluginname', 'block_configurable_reports'));

        $mform->addElement('selectyesno', 'config_displayreportslist', get_string('displayreportslist', 'block_configurable_reports'));
        $mform->setDefault('config_displayreportslist', 1);

        $mform->addElement('selectyesno', 'config_displayglobalreports', get_string('displayglobalreports', 'block_configurable_reports'));
        $mform->setDefault('config_displayglobalreports', 1);

    }
}