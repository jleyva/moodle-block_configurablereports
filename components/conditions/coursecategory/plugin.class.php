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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/conditions/plugin.class.php');

class plugin_coursecategory extends plugin_base{
	
	function summary($instance){
	    if(! ($data = $instance->configdata)){
	        return '';
	    }
		global $DB;
		
		$catname = $DB->get_field('course_categories', 'name', array('id' => $data->categoryid));
		if ($catname) {
			return get_string('category').' '.$catname;
		} else {
			return get_string('category').' '.get_string('top');
		}
	}

	function execute($userid, $courseid, $instance){
	    if(! ($data = $instance->configdata)){
	        return '';
	    }
		global $DB;

		return $DB->get_fieldset_select('course', 'id', 'category = ?', array('category' => $data->categoryid));
	}
	
}

?>