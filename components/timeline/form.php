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

// Based on Custom SQL Reports Plugin
// See http://moodle.org/mod/data/view.php?d=13&rid=2884

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class timeline_form extends moodleform {
 function definition() {
        global $DB, $CFG;

        $mform =& $this->_form;

		$options = array('previous'=>get_string('previousdays', 'block_configurable_reports'), 'fixeddate'=>get_string('fixeddate', 'block_configurable_reports'));
		$mform->addElement('select', 'timemode', get_string('timemode', 'block_configurable_reports'),$options);
		$mform->setDefault('timemode','previous');
		
		$mform->addElement('text', 'previousstart', get_string('previousstart', 'block_configurable_reports'));
		$mform->setDefault('previousstart', 1);
		$mform->setType('previousstart', PARAM_INT);
		$mform->addRule('previousstart', null, 'numeric', null, 'client');
		$mform->disabledIf('previousstart','timemode','eq','fixeddate');		
		
		$mform->addElement('text', 'previousend', get_string('previousend', 'block_configurable_reports'));
		$mform->setDefault('previousend', 0);
		$mform->setType('previousend', PARAM_INT);
		$mform->addRule('previousend', null, 'numeric', null, 'client');
		$mform->disabledIf('previousend','timemode','eq','fixeddate');
		
		$mform->addElement('checkbox', 'forcemidnight', get_string('forcemidnight', 'block_configurable_reports'));
		$mform->disabledIf('forcemidnight','timemode','eq','fixeddate');
		
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'block_configurable_reports'));
		$mform->setDefault('starttime', time() - 3600 * 48);
        $mform->disabledIf('starttime','timemode','eq','previous');
		
		$mform->addElement('date_time_selector', 'endtime', get_string('endtime', 'block_configurable_reports'));
		$mform->setDefault('endtime', time() + 3600 * 24);
		$mform->disabledIf('endtime','timemode','eq','previous');
		
		$mform->addElement('text', 'interval', get_string('timeinterval', 'block_configurable_reports'));
		$mform->setDefault('interval', 1);
		$mform->setType('interval', PARAM_INT);
		$mform->addRule('interval', null, 'numeric', null, 'client');
		$mform->addRule('interval', null, 'nonzero', null, 'client');
				
		$mform->addElement('select', 'ordering', get_string('ordering', 'block_configurable_reports'),array('asc'=>'ASC','desc'=>'DESC'));
				
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $DB, $CFG, $db, $USER;

        $errors = parent::validation($data, $files);		

        return $errors;
    }
    
}

