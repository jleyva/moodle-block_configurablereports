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

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_roleincourse extends plugin_base{
	
	function init(){
		$this->form = true;
		$this->unique = false;
		$this->fullname = get_string('roleincourse','block_configurable_reports');
		$this->reporttypes = array('courses','sql','users','timeline','categories');
	}
	
	function summary($data){
		global $DB;
		
		$rolename = $DB->get_field('role','name',array('id' => $data->roleid));
		$coursename = $DB->get_field('course','fullname',array('id' => $this->report->courseid));
		return $rolename.' '.$coursename;
	}
	
	function execute($userid, $context, $data){
		global $DB, $CFG;
		
		$context = ($this->report->courseid == SITEID)? get_context_instance(CONTEXT_SYSTEM): get_context_instance(CONTEXT_COURSE,$this->report->courseid);
		$roles = get_user_roles($context,$userid);
		if(!empty($roles)){
			foreach($roles as $rol){
				if($rol->roleid == $data->roleid)
					return true;
			}
		}
		return false;
	}
	
}

?>