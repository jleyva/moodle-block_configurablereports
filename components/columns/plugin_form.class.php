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
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin_form.class.php');

abstract class columns_plugin_form extends plugin_form {
    protected function common_column_options(){
        global $CFG;
        
        $mform = $this->_form;
        
        $mform->addElement('header', '', get_string('columnandcellproperties','block_configurable_reports'), '');
        
        $mform->addElement('text', 'columname', get_string('name'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('columname', PARAM_TEXT);
        } else {
            $mform->setType('columname', PARAM_CLEAN);
        }
        
        $alignoptions = array('center'=>'center','left'=>'left','right'=>'right');
        $mform->addElement('select', 'align', get_string('cellalign','block_configurable_reports'), $alignoptions);
        $mform->setAdvanced('align');
        
        $mform->addElement('text', 'size', get_string('cellsize','block_configurable_reports'));
        $mform->setType('size', PARAM_CLEAN);
        $mform->setAdvanced('size');
        
        $wrapoptions = array(''=>'Wrap','nowrap'=>'No Wrap');
        $mform->addElement('select', 'wrap', get_string('cellwrap','block_configurable_reports'), $wrapoptions);
        $mform->setAdvanced('wrap');
        
        $mform->addRule('columname',get_string('required'),'required');
    }
    
	function validation($data, $files){
		$errors = parent::validation($data, $files);

		if (!empty($data['size']) && !preg_match("/^\d+(%|px)$/i", trim($data['size']))) {
		    $errors['size'] = get_string('badsize','block_configurable_reports');
		}
		
		return $errors;
	}
	
	function set_data($default_values){
	    //TODO: Get component config (DB)
	    
	    parent::set_data($default_values);
	}
}