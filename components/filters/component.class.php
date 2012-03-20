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

class component_filters extends component_base{

	function plugin_classes(){
	    return array(
	        'startendtime' => 'plugin_startendtime',
	    );
	}
	
	function has_ordering(){
	    return true;
	}
	
	function add_form_elements(&$mform){
	    foreach($this->get_plugins() as $plugin){
	        foreach($plugin->get_instances() as $filter){
	            $finalelements = $plugin->print_filter($mform, $filter);
	        }
	    }
	}
	
	function print_to_report($return = false){
	    global $CFG;
	    $filters = $this->get_all_instances();
	    if(empty($filters)){
	        return;
	    }
	    require_once('filter_form.php');
	    
        $formdata = new stdclass;
        $request = array_merge($_POST, $_GET);
        foreach($request as $key => $val){
            if (strpos($key,'filter_') !== false) {
                $formdata->{$key} = $val;
            }
        }
        	
        $filterform = new filter_form(null, $this);
        $filterform->set_data($formdata);
    
        if($filterform->is_cancelled()){
            $params = array('id' => $this->report->id);
            redirect(new moodle_url('blocks/configurable_reports/viewreport.php', $params));
        }
        
        ob_start();
        $filterform->display();
        $output = ob_get_clean();
        
        if ($return) {
            return $output;
        }
        echo $output;
	}
}

?>