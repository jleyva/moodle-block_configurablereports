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
  * @derived from: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2019
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_heatmap extends plugin_base{

	function init(){
		$name = get_string('heatmap','block_configurable_reports');
		if (!$name || is_null($name)){ $name = "heatmap"; }
		$this->fullname = $name;
		$this->form = true;
		$this->ordering = true;
		$this->reporttypes = array('courses','sql','users','timeline', 'categories');
	}

	function summary($data){
		return $this->fullname." summary";
	}

	// data -> Plugin configuration data
	function execute($id, $data, $finalreport){
		global $DB, $CFG;
		$series = array();
		if($finalreport){
			list($label_idx,$label_name) = explode(",",$data->label_field);
			$series[$label_name] = array();
			if( ! is_array($data->value_fields) ){
				$data->value_fields = array($data->value_fields);
			}
			foreach($finalreport as $r){
				$series[$label_name][] = $r[$label_idx];
				foreach ( $data->value_fields as $value_fields ) {
					list($idx,$name) = explode(",",$value_fields);
					$value = $r[$idx];
					
					if ( $idx == $label_idx ) {
						error_log("moodle:configurable_reports:heatmap:  refusing to chart label field");
						continue;	
					}
					
					if ( ! is_numeric($value) ) {
						# Can't just skip. That would throw off the indexes if a 
						# column has bad values in some but not all rows.
						error_log("moodle:configurable_reports:heatmap:  substituting 0 for non-numeric value '$value'");
						$value = 0;
					}
					
					if ( ! array_key_exists($name,$series) ) {
						$series[$name] = array();	
					}
					$series[$name][] = $value;
				}
			}
		}

		$graphdata = urlencode(json_encode($series));
/*MGL */ mtrace("\nConfigurable report (block):: execute -> Plugin configuration data");
		return $CFG->wwwroot.'/blocks/configurable_reports/components/plot/heatmap/graph.php?reportid='.$this->report->id.'&id='.$id.'&graphdata='.$graphdata;
	}

	function get_series($data){/*MGL */ mtrace("\nConfigurable report (block):: get_series -> Plugin configuration data");
		$graphdata_raw = required_param('graphdata',PARAM_RAW);
		$graphdata =  json_decode(urldecode($graphdata_raw));
/*MGL */ mtrace("\nConfigurable report (block):: get_series -> Plugin configuration data");		
		return (array) $graphdata;
	}

}

