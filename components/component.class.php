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

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');
 
class component_base {
	var $plugins;    // Plugin objects
	
	var $ordering = false;
	var $form = false;
	var $help = '';
	
	static function get($report, $component) {
	    $comppath = self::get_path($report, $component);
	    require_once("$comppath/$component/component.class.php");
	    
	    $componentclassname = 'component_'.$component;
	    return new $componentclassname($report);
	}
	
	static function get_path($report, $component){
	    global $CFG;
	    
	    $basedir = "$CFG->dirroot/blocks/configurable_reports";
	    $custompath = "report/$report->type";
	    $filepath = "components/$component";
        $file = "component.class.php";
	    
	    if (file_exists("$basedir/$custompath/$filepath/$file")) {
	        return "$basedir/$custompath/$filepath";
	    }
	    
	    return "$basedir/$filepath";
	}
	
	function __construct($report) {
		global $DB, $CFG;
		
		$this->init();
	}
	
	function __toString(){
	    return get_string($this->name, 'block_configurable_reports');
	}
	
	function _load_plugins(){
	    $this->plugins = array();
	    foreach($this->get_plugin_list() as $plug){
	        $this->components[$plug] = plugin_base::get($this->report->config, $this->get_name(), $plug);
	    }
	}
	
	function get_name(){
	    $pieces = explode('component_', get_class($this));
	    
	    return $pieces[1];
	}
	
	function get_plugin_list(){
	    return get_list_of_plugins('blocks/configurable_reports/components/'.$this->get_name());
	}
	
	function has_plugin($plugname){
	    if (!isset($this->plugins)) {
	        $this->_load_plugins();
	    }
	    
	    return array_key_exists($plugname, $this->plugins);
	}
	
	function get_plugin($plugname){
	    if (!$this->has_plugin($plugname)) {
	        return null;
	    }
	     
	    return $this->plugins[$plugname];
	}
	
	function get_plugin_options(){
	    if (!isset($this->plugins)) {
	        $this->_load_plugins();
	    }
	    if (empty($this->plugins)) {
	        return array();
	    }
	    
	    $currentplugins = array_keys($this->elements);
	     
	    $pluginoptions = array();
	    foreach($this->plugins as $plugin => $pluginclass){
	        if($pluginclass->unique && in_array($plugin, $currentplugins)){
	            continue;
	        }
	        $pluginoptions[$plugin] = $pluginclass->get_name();
	    }
	    asort($pluginoptions);
	     
	    return $pluginoptions;
	}
	
	function get_form($action = null, $customdata = null){
	    if(!$this->form){
	        return null;
	    }
	    
	    $comppath = self::get_path($this->config, $component);
	    require_once("$comppath/form.php");
	    
	    $classname = $component.'_form';
	    return new $classname($action, $customdata);
	}
	
	function add_form_elements(&$mform,$fullform) {
		return false;
	}
}

?>