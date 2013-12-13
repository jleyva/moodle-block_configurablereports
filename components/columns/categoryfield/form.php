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
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class categoryfield_form extends moodleform {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;

        $mform->addElement('header',  'crformheader' ,get_string('categoryfield','block_configurable_reports'), '');

		$columns = $DB->get_columns('course_categories');

		$coursecolumns = array();
		foreach($columns as $c)
			$coursecolumns[$c->name] = $c->name;

        $mform->addElement('select', 'column', get_string('column','block_configurable_reports'), $coursecolumns);

		$this->_customdata['compclass']->add_form_elements($mform,$this);

        // buttons
        $this->add_action_buttons(true, get_string('add'));

    }

	function validation($data, $files){
		$errors = parent::validation($data, $files);

		$errors = $this->_customdata['compclass']->validate_form_elements($data,$errors);

		return $errors;
	}

}

