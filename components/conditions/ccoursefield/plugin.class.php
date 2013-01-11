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

class plugin_ccoursefield extends plugin_base{
	
	function init(){
		$this->fullname = get_string('ccoursefield','block_configurable_reports');
		$this->reporttypes = array('courses');
		$this->form = true;
	}
		
	function summary($data){		
		return get_string($data->field).' '.$data->operator.' '.$data->value;
		
	}
	
	// data -> Plugin configuration data
	function execute($data,$user,$courseid){
		global $DB;
		
		$data->value = $data->value;
		$ilike = " LIKE "; // TODO - Use $DB->sql_like()
	
		switch($data->operator){
			case 'LIKE % %': 	$sql = "$data->field $ilike ?";
								$params = array("%$data->value%");
								break;						
			default:	$sql = "$data->field $data->operator ?";
						$params = array($data->value);
		}
		
		$courses = $DB->get_records_select('course',$sql, $params);

		if($courses)
			return array_keys($courses);

		return array();
	}
	
}

