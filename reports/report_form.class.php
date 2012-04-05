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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class report_edit_form extends moodleform {
    
    function definition() {
        $mform =& $this->_form;
        
        $reportclass = $this->_customdata['reportclass'];
        if (isset($reportclass)) {
            $type = $reportclass->config->type;
            $courseid = $reportclass->config->courseid;
        } else {
            $type = $this->_customdata['type'];
            $courseid = $this->_customdata['courseid'];
        }

        $this->general_options();
        
        $this->component_options();

		$mform->addElement('hidden', 'type', $type);
		if (isset($courseid)) {
		    $mform->addElement('hidden', 'courseid', $courseid);
		}
		
		if (isset($reportclass)) {
			$mform->addElement('hidden', 'id', $reportclass->id);
			$this->add_action_buttons();
		} else {
		    $this->add_action_buttons(true, get_string('add'));
		}
    }
    
    function general_options(){
        global $CFG;
        $mform =& $this->_form;
        
        $mform->addElement('header', 'reportgeneral', get_string('general', 'form'));
        
        $mform->addElement('text', 'name', get_string('name'));
        $mform->addRule('name', null, 'required', null, 'client');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        
        $mform->addElement('htmleditor', 'summary', get_string('summary'), array('rows' => 15));
        $mform->setType('summary', PARAM_RAW);
        
        for ($i=0; $i<=100; $i++) {
            $pagoptions[$i] = $i;
        }
        $mform->addElement('select', 'pagination', get_string("pagination", 'block_configurable_reports'), $pagoptions);
        $mform->addHelpButton('pagination','pagination', 'block_configurable_reports');
        $mform->setDefault('pagination', 0);
    }
    
    function component_options(){
        $mform =& $this->_form;
        
        $reportclass = $this->_customdata['reportclass'];
        foreach($reportclass->get_form_components() as $comp => $compclass){
            $compclass->report_form_elements($mform);
        }
    }
}

?>