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

class puserfield_form extends plugin_form {

    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', '', get_string('coursefield','block_configurable_reports'), '');
		
		$usercolumns = $this->get_user_columns();	
        $mform->addElement('select', 'field', get_string('column','block_configurable_reports'), $usercolumns);
		
		$mform->addElement('text', 'value', get_string('value','block_configurable_reports'));
		$mform->addRule('value', get_string('required'), 'required');

        $this->add_action_buttons();
    }
	
	function validation($data,$files){
		global $DB, $db, $CFG;
		
		$errors = parent::validation($data, $files);
			
		if(!in_array($data['field'], $this->get_user_columns())){
			$errors['field'] = get_string('error_field','block_configurable_reports');
		}
		
		return $errors;
	}

	function get_user_columns(){
	    global $DB;
	    
	    $usercolumns = array();
	    
	    foreach($DB->get_columns('user') as $c){
	        $usercolumns[$c->name] = $c->name;
	    }
	    
	    if ($profile = $DB->get_records('user_info_field')) {
	        foreach($profile as $p){
	            $usercolumns['profile_'.$p->shortname] = 'profile_'.$p->shortname;
	        }
	    }
	    
	    unset($usercolumns['password']);
	    unset($usercolumns['secret']);
	    
	    return $usercolumns;
	}
}

?>