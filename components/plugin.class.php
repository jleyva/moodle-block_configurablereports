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

abstract class plugin_base{
    var $report;
    var $component;
    var $instances;    // Current plugin instances in report
	
	/**
	 * Construct a new plugin type instance.
	 * @param report_base    $report
	 * @param component_base $component
	 */
	function __construct(report_base $report, component_base $component){
	    global $DB, $CFG;
		
		$this->report = $report;
		$this->component = $component;
	}
	
	private function _load_instances(){
	    global $DB;
	     
	    $search = array('reportid' => $this->report->id, 'plugin' => $this->get_type());
	    $this->instances = $DB->get_records('block_configurable_reports_plugin', $search);
	     
	    foreach($this->instances as $id => $instance){
	        $this->instances[$id]->configdata = cr_unserialize($instance->configdata);
	    }
	}
	
	function add_instance($configdata = null){
	    global $DB;
	     
	    $search = array(
            'reportid'  => $this->report->id,
            'component' => $this->component->get_type(),
	    );
	    $instance = new stdClass();
	    $instance->configdata = $configdata;
	    $instance->name = $this->get_fullname($instance);
	    $instance->summary = $this->summary($instance);
	    $instance->reportid = $search['reportid'];
	    $instance->component = $search['component'];
	    $instance->plugin = $this->get_type();
	    $last = $DB->get_field('block_configurable_reports_plugin', 'COALESCE(MAX(sortorder), -1)', $search);
	    $instance->sortorder = $last + 1;
	    $instance->configdata = cr_serialize($instance->configdata);
	
	    $DB->insert_record('block_configurable_reports_plugin', $instance);
	}
	
	/**
	 * Whether a new instance of this plugin can be created.
	 * @return boolean
	 */
	function can_create_instance(){
	    return count($this->instances) > 1 ? $this->instance_allow_multiple() : true;
	}
	
	function delete_instance($instanceid){
	    global $DB;
	
	    $DB->delete_records('block_configurable_reports_plugin', array('id' => $instanceid));
	    unset($this->instances[$instanceid]);
	}
	
	function get_instance($id){
	    $instances = $this->get_instances();
	    if(!array_key_exists($id, $instances)){
	        return false;
	    }
	    
	    return $instances[$id];
	}
	
	function get_instances(){
	    if (!isset($this->instances)) {
	        $this->_load_instances();
	    }
	    
	    return $this->instances;
	}
	
	function get_form($action = null, $customdata = array()){
	    global $CFG;
	    if (!$this->has_form()) {
	        return null;
	    }
	     
	    $plugin = $this->get_type();
	    $file = 'form.php';
	    $plugpath = $this->component->get_plugin_path($plugin, $file);
	    require_once("$plugpath/$file");
	
	    $classname = $plugin.'_form';
	    $customdata['plugclass'] = $this;
	    return new $classname($action, $customdata);
	}
	
	function get_fullname($instance){
	    if (isset($instance->configdata->name)) {
	        return $instance->configdata->name;
	    } else {
	        $strman = get_string_manager();
	        $identifier = $this->get_type();
	        $component = 'block_configurable_reports';
	        if ($strman->string_exists($identifier, $component)) {
	            return get_string($identifier, $component);
	        }
	    }
	    
	    return '';
	}
	
	/**
	 * Retrieve the plugin type for this class definition.
	 * FORMAT REQUIREMENT: plugin_XXX_YYY where XXX is the plugin type
	 *
	 * @return string Plugin type
	 */
	function get_type(){
	    $pieces = explode('_', get_class($this));
	    return $pieces[1];
	}
	
	function has_form(){
	    return false;
	}
	
	/**
	 * Whether this plugin allows multiple instances.
	 * @return boolean
	 */
	function instance_allow_multiple(){
	    return true;
	}
	
	function move_instance($instanceid, $shift){
	    global $DB;
	     
	    if (! ($instance = $this->get_instance($instanceid))) {
	        return false;
	    }
	    $oldorder = $instance->sortorder;
	    $neworder = $oldorder + $shift;
	    $instances =& $this->component->get_all_instances();
	    if (! ($swapped = $instances[$neworder])) {
	        return false;
	    }
	     
	    // Move this instance
	    $DB->set_field('block_configurable_reports_plugin', 'sortorder', $neworder, array('id' => $instanceid));
	    // Swap with another instance
	    $DB->set_field('block_configurable_reports_plugin', 'sortorder', $oldorder, array('id' => $swapped->id));
	}
	
	abstract function summary($instance);
	
	function update_instance($instance){
	    global $DB;
	    
	    $instance->name = $this->get_fullname($instance);
	    $instance->summary = $this->summary($instance);
	    $instance->configdata = cr_serialize($instance->configdata);
	    $DB->update_record('block_configurable_reports_plugin', $instance);
	}
	
}

?>