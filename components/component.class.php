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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin.class.php');
require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");    //cr_unserialize
 
abstract class component_base {
    var $report;     // Report class
    var $config;     // Component configuration (DB record if exists)
	var $plugins;    // Plugin objects
	
	function __construct(report_base $report) {
		global $DB, $CFG;
		
		$this->report = $report;
		$search = array('reportid' => $report->config->id, 'component' => $this->get_type());
		$configdata = $DB->get_field('block_configurable_reports_component', 'configdata', $search);
		$this->config = cr_unserialize($configdata);
	}
	
	function __toString(){
	    return $this->get_typename();
	}
	
	/**
	 * Retrieve a plugin class object.
	 * @param string          $plugin
	 * @param string          $classname
	 * @return plugin_base    Plugin class object
	 */
	private function _create_plugin($plugin, $classname){
	    global $CFG;
	    
	    $file = 'plugin.class.php';
	    $plugpath = $this->get_plugin_path($plugin, $file);
	    require_once("$plugpath/$file");
	    
	    return new $classname($this->report, $this);
	}
    
    /**
     * Load plugin objects for this component.
     */
    private function _load_plugins(){
        $this->plugins = array();
        foreach($this->plugin_classes() as $plug => $classname){
            $this->plugins[$plug] = $this->_create_plugin($plug, $classname);
        }
    }
    
    function add_instance($configdata = null){
        global $DB;
    
        $instance = new stdClass();
        $instance->reportid = $this->report->id;
        $instance->component = $this->get_type();
        $instance->configdata = $configdata;
        $instance->configdata = cr_serialize($instance->configdata);
    
        $DB->insert_record('block_configurable_reports_component', $instance);
    }
    
    function delete_instance($instanceid){
        global $DB;
    
        $DB->delete_records('block_configurable_reports_component', array('id' => $instanceid));
        unset($this->instances[$instanceid]);
    }
	
	function get_all_instances(){
	    global $DB;
	    
	    $instances = array();
	    $search = array('reportid' => $this->report->id, 'component' => $this->get_type());
	    $records = $DB->get_records('block_configurable_reports_plugin', $search, 'sortorder');
	    foreach($records as $record){
	        if ($this->has_ordering()) {
	            $instances[$record->sortorder] = $record;
	        } else {
	            $instances[$record->id] = $record;
	        }
	    }
	    
	    return $instances;
	}
	
	function get_help_icon(){
	    global $OUTPUT;
	     
	    return $OUTPUT->help_icon('comp_'.$this->get_type(), 'block_configurable_reports', true);
	}
	
	function get_form($action = null, $customdata = array()){
	    if (!$this->has_form()) {
	        return null;
	    }
	    global $CFG;    //Needed for requiring depends using CFG->dirroot
	     
	    $component = $this->get_type();
	    $comppath = $this->report->get_component_path($component, "form.php");
	    require_once("$comppath/form.php");
	     
	    $formclassname = $component.'_form';
	    $customdata['compclass'] = $this;
	    return new $formclassname($action, $customdata);
	}
	
	/**
	 * Retrieve the plugin object given a plugin name.
	 * @param string $plugname         Plugin name
	 * @return NULL|plugin_base        Plugin object
	 */
	function get_plugin($plugname){
	    if (!$this->has_plugin($plugname)) {
	        return null;
	    }
	    $plugins = $this->get_plugins();
	     
	    return $plugins[$plugname];
	}
	
	/**
	 * Return all plugin class objects for this component.
	 * @return plugin_base    Plugins for this component
	 */
	function get_plugins(){
	    if (!isset($this->plugins)) {
	        $this->_load_plugins();
	    }
	     
	    return $this->plugins;
	}
    
	function get_plugin_options(){
	    $plugins = $this->get_plugins();
	     
	    $pluginoptions = array();
	    foreach($plugins as $plugin => $pluginclass){
	        if ($pluginclass->can_create_instance()) {
	            $pluginoptions[$plugin] = get_string($pluginclass->get_type(), 'block_configurable_reports');
	        }
	    }
	    asort($pluginoptions);
	     
	    return $pluginoptions;
	}
	
	/**
	 * Get path of plugin file.
	 * @param string $plugin     Plugin type
	 * @param string $file       File name
	 * @return string            Full path to file directory (i.e. PATH/$file is the absolute dir)
	 */
	public function get_plugin_path($plugin, $file){
	    global $CFG;
	
	    $comppath = $this->report->get_component_path($this->get_type(), "$plugin/$file");
	    return "$comppath/$plugin";
	}
	
	/**
	 * Retrieve the component type for this class definition.
	 * FORMAT REQUIREMENT: component_XXX_YYY where XXX is the component type
	 *
	 * @return string Component type
	 */
	function get_type(){
	    $pieces = explode('_', get_class($this));
	    return $pieces[1];
	}
	
	function get_typename(){
	    return get_string($this->get_type(), 'block_configurable_reports');
	}
	
	function has_ordering(){
	    return false;
	}
	
	function has_plugin($plugname){
	    return array_key_exists($plugname, $this->plugin_classes());
	}
	
	function has_form(){
	    return false;
	}
	
	function plugin_classes(){
	    return array();
	}
	
	function update_instance($instance){
	    global $DB;
	    
	    $instance->configdata = cr_serialize($instance->configdata);
	    $DB->update_record('block_configurable_reports_component', $instance);
	}

}

?>