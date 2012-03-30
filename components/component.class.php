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
	
	/**
	 * Retrieve a component class object.
	 * @param report_base     $report
	 * @param string          $component
	 * @param string          $classname
	 * @return component_base Component class object
	 */
	static function get(report_base $report, $component, $classname){
	    global $CFG;
	    
	    $file = 'component.class.php';
	    $comppath = self::get_path($report->config->type, $component, $file);
	    require_once("$comppath/$file");
	    
	    return new $classname($report);
	}
	
	/**
	 * Get path of component file.
	 * @param string $reporttype    Report type
	 * @param string $component     Component name
	 * @param string $file          File name
	 * @return string               Full path to file directory (i.e. PATH/$file is the absolute dir)
	 */
	static function get_path($reporttype, $component, $file){
	    global $CFG;
	    
	    $basedir = "$CFG->dirroot/blocks/configurable_reports";
	    $custompath = "reports/$reporttype";
	    $filepath = "components/$component";
	    
	    if (file_exists("$basedir/$custompath/$filepath/$file")) {
	        return "$basedir/$custompath/$filepath";
	    }
	    
	    return "$basedir/$filepath";
	}
	
	function __construct(report_base $report) {
		global $DB, $CFG;
		
		$this->report = $report;
		$search = array('reportid' => $report->config->id, 'component' => $this->get_name());
		$configdata = $DB->get_field('block_configurable_reports_component', 'configdata', $search);
		$this->config = cr_unserialize($configdata);
	}
	
	function __toString(){
	    return get_string($this->get_name(), 'block_configurable_reports');
	}
	
	/**
	 * Retrieve the component name for this class definition.
	 * FORMAT REQUIREMENT: component_XXX_YYY where XXX is the component name
	 * 
	 * @return string    Component name
	 */
	function get_name(){
	    $pieces = explode('_', get_class($this));
	    return $pieces[1];
	}
	
	function plugin_classes(){
	    return array();
	}
	
	function _load_plugins(){
	    $this->plugins = array();
	    foreach($this->plugin_classes() as $plug => $classname){
	        $this->plugins[$plug] = plugin_base::get($this->report, $this, $plug, $classname);
	    }
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
	
	function get_all_instances(){
	    global $DB;
	    
	    $instances = array();
	    $search = array('reportid' => $this->report->id, 'component' => $this->get_name());
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
	
	function has_plugin($plugname){
	    return array_key_exists($plugname, $this->plugin_classes());
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
	
	function get_plugin_options(){
	    $plugins = $this->get_plugins();
	     
	    $pluginoptions = array();
	    foreach($plugins as $plugin => $pluginclass){
	        if ($pluginclass->can_create_instance()) {
	            $pluginoptions[$plugin] = get_string($pluginclass->get_name(), 'block_configurable_reports');
	        }
	    }
	    asort($pluginoptions);
	     
	    return $pluginoptions;
	}
	
	function has_ordering(){
	    return false;
	}
	
	function get_help_icon(){
	    global $OUTPUT;
	    
	    return $OUTPUT->help_icon('comp_'.$this->get_name(), 'block_configurable_reports', true);
	}
	
	function has_form(){
	    return false;
	}
	
	function get_form($action = null, $customdata = array()){
	    if (!$this->has_form()) {
	        return null;
	    }
	    
	    global $CFG;
	    $component = $this->get_name();
	    $comppath = self::get_path($this->report->config->type, $component, "form.php");
	    require_once("$comppath/form.php");
	    
	    $formclassname = $component.'_form';
	    $customdata['compclass'] = $this;
	    return new $formclassname($action, $customdata);
	}
	
	function add_instance($configdata = null){
	    global $DB;

	    $instance = new stdClass();
	    $instance->reportid = $this->report->id;
	    $instance->component = $this->component->get_name();
	    $instance->configdata = $configdata;
	    $instance->configdata = cr_serialize($instance->configdata);
	
	    $DB->insert_record('block_configurable_reports_component', $instance);
	}
	
	function update_instance($instance){
	    global $DB;
	    
	    $instance->configdata = cr_serialize($instance->configdata);
	    $DB->update_record('block_configurable_reports_component', $instance);
	}
	
	function delete_instance($instanceid){
	    global $DB;
	     
	    $DB->delete_records('block_configurable_reports_component', array('id' => $instanceid));
	    unset($this->instances[$instanceid]);
	}
}

?>