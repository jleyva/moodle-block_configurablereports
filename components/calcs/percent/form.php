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
 * A Moodle block for creating customizable reports
 * @package block_configurable_reports
 * @author David Pesce <davidpesce@gmail.com>
 * @date 2019
 */

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class percent_form extends moodleform {

    public function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;
		    $this->_customdata['compclass']->add_form_elements($mform, $this->_customdata['report']->components);

        // Buttons.
        $this->add_action_buttons(true, get_string('add'));
    }
}