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

class component_calcs extends component_base{
	
	function plugin_classes(){
		return array(
		        'average' => 'plugin_average',
		        'max'     => 'plugin_max',
		        'min'     => 'plugin_min',
		        'sum'     => 'plugin_sum',
		);
	}
	
	function print_to_report($reportclass, $return = false){
	    $calcs = $this->get_all_instances();
	    if (empty($calcs)) {
	        return;
	    }
	    
	    /* Organize table data by column */
	    $rows = array();
	    foreach($reportclass->finaltable as $row){
	        foreach($row as $column => $cell){
	            $rows[$column][] = $cell;
	        }
	    }
	    
	    /* Perform calculations on rows */
	    $finalcalcs = array();
	    foreach($this->get_plugins() as $plugclass){
	        $plugname = $plugclass->get_name();
	        foreach($plugclass->get_instances() as $calc){
	            $column = $calc->configdata->column;
	            if (isset($rows[$column])) {
	                $finalcalcs[$column][$plugname] = $plugclass->execute($rows[$column]);
	            }
	        }
	    }
	    ksort($finalcalcs);
	    
	    $table = $reportclass->create_table(array_keys($finalcalcs));
	    $tabletitle = get_string("columncalculations", "block_configurable_reports");
	    $output = html_writer::tag('div', "<b>$tabletitle</b>", array('class' => 'centerpara'));
	    $output .= html_writer::table($table);
	    if ($return) {
	        return $output;
	    }
	    echo $output;
	}
}

?>