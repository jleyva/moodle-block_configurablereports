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

class template_form extends moodleform {
 function definition() {
        global $DB, $CFG;

        $mform =& $this->_form;

		$report = $this->_customdata['report'];
		
		$options = array();
		
		if($report->type != 'sql'){		
			$components = cr_unserialize($this->_customdata['report']->components);
			
			if(is_array($components) && !empty($components['columns']['elements'])){
				$columns = $components['columns']['elements'];
				foreach($columns as $c){
					$options[] = $c['summary'];
				}
			}
		}
		else{
			
			require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
			require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');
			
			$reportclassname = 'report_'.$report->type;	
			$reportclass = new $reportclassname($report);
			
			$components = cr_unserialize($report->components);
			$config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;	
			
			if(isset($config->querysql)){
				
				$sql = $config->querysql;
				$sql = $reportclass->prepare_sql($sql);
				if($rs = $reportclass->execute_query($sql)){
					foreach($rs as $row){
						$i = 0;
						foreach($row as $colname=>$value){
							$options[$i] = str_replace('_', ' ', $colname);
							$i++;
						}
					break;
					}
				}
			}			
		}		
		
		$optionsenabled = array(0=>get_string('disabled','block_configurable_reports'),1=>get_string('enabled','block_configurable_reports'));
		
		$mform->addElement('select','enabled',get_string('template','block_configurable_reports'),$optionsenabled);
		$mform->setDefault('enabled',0);
			
		$mform->addElement('htmleditor', 'header', get_string('header', 'block_configurable_reports'));
		$mform->disabledIf('header', 'enabled', 'eq', 0);
		$mform->addHelpButton('header','template_marks', 'block_configurable_reports');
		
		$availablemarksrec = '';
		if($options)
			foreach($options as $o)
				$availablemarksrec .= "[[$o]] => $o <br />";
		
		$mform->addElement('static','statictext',get_string('availablemarks','block_configurable_reports'),$availablemarksrec);
		$mform->addElement('htmleditor', 'record', get_string('templaterecord', 'block_configurable_reports'));
		$mform->disabledIf('record', 'enabled', 'eq', 0);
		
		$mform->addElement('htmleditor', 'footer', get_string('footer', 'block_configurable_reports'));
        $mform->disabledIf('footer', 'enabled', 'eq', 0);
		$mform->addHelpButton('footer','template_marks', 'block_configurable_reports');
		
		//$mform->addRule('record', get_string('required'), 'required', null, 'client');
        $mform->setType('header', PARAM_RAW);
		$mform->setType('record', PARAM_RAW);
		$mform->setType('footer', PARAM_RAW);

        
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $DB, $CFG, $db, $USER;

        $errors = parent::validation($data, $files);
		
		if($data['enabled']){
			if(! $data['record'])
				$errors['record'] = get_string('required');
		}
        		
        return $errors;
    }
    
}

