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

require_once("$CFG->dirroot/blocks/configurable_reports/reports/report_dataset.class.php");

class report_timeline extends report_dataset_base{
	public $timeline;
	
    function component_classes(){
        return array(
            'timeline'    => 'component_timeline',
            'columns'     => 'component_columns_timeline',
            'filters'     => 'component_filters',
            'permissions' => 'component_permissions',
            'calcs'       => 'component_calcs',
            'plot'        => 'component_plot_timeline',
            'template'    => 'component_template',
        );
    }

	function get_all_elements(){
		$elements = array();
				
		$compclass = $this->get_component('timeline');
		$config = $compclass->config;
		if(isset($config->timemode)){
			$daysecs = 60 * 60 * 24;

			$filter_starttime = optional_param_array('filter_starttime', 0, PARAM_RAW);
			$filter_endtime = optional_param_array('filter_endtime', 0, PARAM_RAW);
	
			if($filter_starttime and $filter_endtime){
				$filter_starttime = make_timestamp($filter_starttime['year'],$filter_starttime['month'],$filter_starttime['day']);
				$filter_endtime = make_timestamp($filter_endtime['year'],$filter_endtime['month'],$filter_endtime['day']);
				
				$config->starttime = usergetmidnight($filter_starttime);
				$config->endtime = usergetmidnight($filter_endtime) + 24*60*60;
			} else if($config->timemode == 'previous'){
		        $config->starttime = gmmktime() - $config->previousstart * $daysecs;
		        $config->endtime = gmmktime() - $config->previousend * $daysecs;
		        
		        if(isset($config->forcemidnight)){
		            $config->starttime = usergetmidnight($config->starttime);
		            $config->endtime = usergetmidnight($config->endtime) + ($daysecs - 1);
		        }
		    }
			
			for($i=$config->starttime; $i<$config->endtime; $i += $config->interval * $daysecs){
				$row = new stdclass();
				$row->id = $i;
				$row->starttime = $i;
				$row->endtime = $row->starttime + ($config->interval * $daysecs -1);
				if ($row->endtime > $config->endtime) {
					$row->endtime = $config->endtime;
				}
				$this->timeline[$row->starttime] = $row;
				$elements[] = $row->starttime;
			}
			
			if ($config->ordering == 'desc') {
				rsort($elements);
			}
		}
				
		return $elements;
	}
	
	function get_rows(array $elements, $sqlorder = ''){
	    $finaltimeline = array();
		foreach($elements as $e){
			$finaltimeline[] = $this->timeline[$e];
		}
		
		return $finaltimeline;
	}
	
}

?>