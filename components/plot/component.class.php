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

class component_plot extends component_base{

	function plugin_classes(){
	    return array(
	            'line' => 'plugin_line',
	            'pie'  => 'plugin_pie',
	    );
	}
	
	function has_ordering(){
	    return true;
	}
	
	function print_to_report($return = false){
	    $graphs = array();
	    
	    $reportdata = $this->report->finalreport->table->data;
	    foreach($this->get_plugins() as $plotclass){
	        foreach($plotclass->get_instances() as $plot){
	            $imgsrc = $plotclass->execute($plot, $reportdata);
	            $img = html_writer::empty_tag('img', array('src' => $imgsrc, 'alt' => $plot->name));
	            $graphs[] = html_writer::tag('div', $img, array('class' => 'centerpara'));
	        }
	    }
	    
	    $output = implode('<br>', $graphs);
	    if ($return) {
	        return $output;
	    }
	    echo $output;
	}
}

?>