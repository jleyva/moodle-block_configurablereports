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

abstract class component_contexts extends component_base{
	
	function has_form(){
	    return true;
	}
	
	abstract function get_allowed_context_types();
	
	function get_contexts(){
	    global $DB;
	    $course = $this->report->config->courseid;
	    //context::instance_by_id($this->report->contextid)
	    $reportcontext = isset($course) ? context_course::instance($course) : context_system::instance();
	    
	    $contexts = array();
	    $contexttypes = $this->get_context_types();
	    if (!empty($contexttypes)){
    	    if ($reportcontext instanceof context_system) {
    	        // Need a custom protocol since context_system::get_child_contexts returns all contexts
    	        list($csql, $params) = $DB->get_in_or_equal($contexttypes);
    	        $ids = $DB->get_fieldset_select('context', 'id', "contextlevel $csql", $params);
    	        foreach ($ids as $id) {
    	            $contexts[$id] = context::instance_by_id($id);
    	        }
    	    } else {
    	        $contexts = $reportcontext->get_child_contexts();
    	        foreach($contexts as $id => $context){
    	            if (!in_array(get_class($context), $contexttypes)) {
    	                unset($contexts[$id]);
    	            }
    	        }
    	    }
	    }
	    
	    return $contexts;
	}
	
	/**
	 * Get context types 
	 * @return array
	 */
	function get_context_types(){
	    return array_keys($this->config->contexts);
	}
	
	function get_help_icon(){
	    return '';
	}
	
	function get_typename(){
	    return 'Contexts';
	}
	
	function print_to_report($return = false){
	    global $PAGE, $OUTPUT;
	    
	    $curr_context = optional_param('context', null, PARAM_INT);
	    
	    $options = array();
	    foreach($this->get_contexts() as $id => $context){
	        $options[$PAGE->url->out(false, array('context' => $id))] = $context->get_context_name();
	    }
	    $selected = $PAGE->url->out(false, array('context' => $curr_context));
	    $urlselect = new url_select($options, $selected);
	    $urlselect->class = 'boxaligncenter centerpara';
	    $urlselect->set_label(get_string('context', 'role').': ');
	    
	    $output = $OUTPUT->render($urlselect);
	    if ($return) {
	        return $output;
	    }
	    echo $output;
	}
	
}

?>