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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/filters/plugin.class.php');

class plugin_courses extends filters_plugin{
	
	function execute($finalelements, $data){
	    if (! ($filter = optional_param('filter_courses', 0, PARAM_INT))) {
	        return $finalelements;
	    }

	    return $this->filter_elements($finalelements, $filter);
	}
	
	function filter_elements($finalelements, $filter){
	    return array($filter);
	}
	
	function get_course_ids(){
	    return $this->report->get_elements_by_conditions();
	}
	
	function print_filter(&$mform, $instance){
		global $DB;
		
		$filter_courses = optional_param('filter_courses', 0, PARAM_INT);
		
	    $courseids = $this->get_course_ids();
		if(empty($courseids)){
		    return;
		}
				
		$courseoptions = array(0 => get_string('choose'));
		$courses = $DB->get_records_list('course', 'id', $courseids);
		foreach($courses as $course){
			$courseoptions[$course->id] = course_format_name($course);				
		}
		
		$mform->addElement('select', 'filter_courses', get_string('course'), $courseoptions);
		$mform->setType('filter_courses', PARAM_INT);
	}
	
}

?>