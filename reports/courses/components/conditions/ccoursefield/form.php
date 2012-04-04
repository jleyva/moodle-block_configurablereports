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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin_form.class.php');

class ccoursefield_form extends plugin_form {
    private $columns;

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'plughead', get_string('coursefield','block_configurable_reports'), '');

        $mform->addElement('select', 'field', get_string('column','block_configurable_reports'), $this->get_columns());
		
        $plugclass = $this->_customdata['plugclass'];
		$mform->addElement('select', 'operator', get_string('operator','block_configurable_reports'), $plugclass->get_operators());
		
		$mform->addElement('text','value', get_string('value','block_configurable_reports'));		

        $this->add_action_buttons();
    }
	
	function validation($data,$files){
		$errors = parent::validation($data, $files);
		
		if (!in_array($data['field'], $this->get_columns())) {
		    $errors['field'] = get_string('error_field','block_configurable_reports');
		}
		
		$plugclass = $this->_customdata['plugclass'];
		if (!in_array($data['operator'], $plugclass->get_operators())) {
			$errors['operator'] = get_string('error_operator','block_configurable_reports');
		}
		
		if (!is_numeric($data['value']) && preg_match('/^(<|>)[^(<|>)]/i', $data['operator'])) {
			$errors['value'] = get_string('error_value_expected_integer','block_configurable_reports');
		}
		
		return $errors;
	}
	
	function get_columns(){
	    global $DB;

	    if (!isset($this->columns)) {
    	    $this->columns = array();
    	    foreach($DB->get_columns('course') as $c){
    	        $this->columns[$c->name] = $c->name;
    	    }
	    }
	     
	    return $this->columns;
	}

}

?>