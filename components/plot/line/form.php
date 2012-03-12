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
 * @author Nick Koeppen
 */ 

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/plugin_form.class.php');

class line_form extends plot_plugin_form {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;
		
        $plugclass = $this->_customdata['plugclass'];
        $reportclass = report_base::get($plugclass->report);
        $options = $reportclass->get_column_options();
        
		$mform->addElement('header', '', get_string('line','block_configurable_reports'), '');

		$mform->addElement('select', 'xaxis', get_string('xaxis','block_configurable_reports'), $options);
		$mform->addRule('xaxis', null, 'required', null, 'client');
		
		$mform->addElement('select', 'serieid', get_string('serieid','block_configurable_reports'), $options);
		$mform->addRule('serieid', null, 'required', null, 'client');
		
		$mform->addElement('select', 'yaxis', get_string('yaxis','block_configurable_reports'), $options);
		$mform->addRule('yaxis', null, 'required', null, 'client');
		
		$mform->addElement('checkbox', 'group', get_string('groupseries','block_configurable_reports'));
				
        $this->add_action_buttons();
    }
	
	function validation($data, $files){
		$errors = parent::validation($data, $files);
	
		if($data['xaxis'] == $data['yaxis'])
			$errors['yaxis'] = get_string('xandynotequal','block_configurable_reports');
	
		return $errors;
	}

}

?>