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

class plugin_courseparent extends plugin_base{
	
	function init(){
		$this->fullname = get_string('courseparent','block_configurable_reports');
		$this->form = true;
		$this->reporttypes = array('courses');
	}
	
	function summary($data){
		global $DB;
		
		$course = $DB->get_record('course',array('id' => $data->courseid));
		if($course)
			return get_string('courseparent','block_configurable_reports').' '.(format_string($course->fullname));
		return '';
	}
	
	// data -> Plugin configuration data
	function execute($data,$user,$courseid){
		global $DB;
		
		$finalcourses = array();
		if($courses = $DB->get_records('course_meta',array('parent_course' => $data->courseid))){
			foreach($courses as $c)
				$finalcourses[] = $c->child_course;
		}
		return $finalcourses;
	}
	
}

