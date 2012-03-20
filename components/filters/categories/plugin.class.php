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

class plugin_categories extends filters_plugin{
	
	function execute($finalelements, $instance){
		if (! ($filter = optional_param('filter_categories', 0, PARAM_INT))) {
			return $finalelements;
		}
		
		// TODO: Check logic
		if ($this->report->type == 'sql' && $sqlelements = $this->sql_elements($finalelements, $filter)) {
		    return $sqlelements;
		} else {
		    return array($filter);
		}
			
		return $finalelements;
	}
	
	function print_filter(&$mform, $instance){
		global $DB;
		
		$filter_categories = optional_param('filter_categories', 0, PARAM_INT);

		// TODO: ???
		if($this->report->type != 'sql'){
		    $reportclass = report_base::get($this->report);
			$catids = $reportclass->get_elements_by_conditions();
		} else {
		    $catids = $DB->get_fieldset_select('course_categories', 'id', '');
		}
		
		if(empty($catids)){
		    return;
		}   
		
		$courseoptions = array(0 => get_string('choose'));
		$categories = $DB->get_records_list('course_categories', 'id', $catids);
		foreach($categories as $cat){
			$courseoptions[$cat->id] = format_string($cat->name);				
		}
		
		$mform->addElement('select', 'filter_categories', get_string('category'), $courseoptions);
		$mform->setType('filter_categories', PARAM_INT);
	}
	
}

?>