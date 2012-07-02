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
  
class block_configurable_reports extends block_list {
    
    /**
     * Sets the block name and version number
     *
     * @return void
     **/
    function init() {
        $this->title = get_string('blockname', 'block_configurable_reports');
        $this->version = 2010060101;  // YYYYMMDDXX
    }
   
    /**
     * Where to add the block
     *
     * @return boolean
     **/
    function applicable_formats() {
        return array('site' => true, 'course' => true);
    }
    
    /**
     * Global Config?
     *
     * @return boolean
     **/
    function has_config() {
        return false;
    }
    
    /**
     * More than one instance per page?
     *
     * @return boolean
     **/
    function instance_allow_multiple() {
      return false;
    } 
	
    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with the contents
     **/
    function get_content() {
        global $DB, $USER, $CFG ,$COURSE;

        if($this->content !== NULL) {
            return $this->content;
        }

		$this->content = new stdClass;
		$this->content->footer = '';
		$this->content->icons = array();
		
		if (!isloggedin())
			return $this->content;
		
		require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");
		
		$course = $DB->get_record('course',array('id' => $COURSE->id));
		
		if(!$course)
			print_error('coursedoesnotexists');
		
		if ($course->id == SITEID)
			$context = get_context_instance(CONTEXT_SYSTEM);
		else
			$context = get_context_instance(CONTEXT_COURSE, $course->id);

		$reports = $DB->get_records('block_configurable_reports',array('courseid' => $course->id),'name ASC');
		
		if($reports){
			foreach($reports as $report){
				if($report->visible && cr_check_report_permissions($report,$USER->id,$context)){
					$rname = format_string($report->name);
					$this->content->items[] = '<a href= "'.$CFG->wwwroot.'/blocks/configurable_reports/viewreport.php?id='.$report->id.'&courseid='.$course->id.'" alt="'.$rname.'">'.$rname.'</a>';
				}
			}
		}
		
		if(has_capability('block/configurable_reports:managereports', $context) || has_capability('block/configurable_reports:manageownreports', $context)){
			$this->content->items[] = '<a href="'.$CFG->wwwroot.'/blocks/configurable_reports/managereport.php?courseid='.$course->id.'">'.(get_string('managereports','block_configurable_reports')).'</a>';
		}
				
        return $this->content;
    }
}
?>