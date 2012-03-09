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

class component_permissions extends component_base{
	
	function plugin_classes(){
	    return array(
	            'anyone'              => 'plugin_anyone',
	            'puserfield'          => 'plugin_puserfield',
	            'reportscapabilities' => 'plugin_reportscapabilities',
	            'roleincourse'        => 'plugin_roleincourse',
	            'usersincoursereport' => 'plugin_usersincoursereport',
	    );
	}
	
	function has_form(){
	    return true;
	}
	
	function add_missing_conditions($cond){	     
	    $instances = $this->get_all_instances();
	    $count = count($instances);
	    if(empty($instances) || $count == 1){
	        return '';
	    }
	     
	    for($i=$count; $i > 0; $i--){
	        if (strpos($cond,'c'.$i) !== false){
	            continue;
	        }
	        if ($count > 1 && $cond) {
	            $cond .= " and c$i";
	        } else {
	            $cond .= "c$i";
	        }
	    }
	    
	    // Deleting extra conditions
	    for($i = $count + 1; $i <= $count + 5; $i++){
	        $cond = preg_replace('/(\bc'.$i.'\b\s+\b(and|or|not)\b\s*)/i', '', $cond);
	        $cond = preg_replace('/(\s+\b(and|or|not)\b\s+\bc'.$i.'\b)/i', '', $cond);
	    }
	    
	    return $cond;
	}
}

?>