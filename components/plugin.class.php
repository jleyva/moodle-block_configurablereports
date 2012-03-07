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

class plugin_base{
    var $elements;    // Current plugin elements in report
    var $report;
    var $comp;
    var $config;
    
	var $fullname = '';
	var $type = '';
	var $form = false;
	var $cache = array();
	var $unique = false;

	static function get($report, $component, $plugin) {
	    $plugpath = self::get_path($report, $component, $plugin);
	    require_once("$plugpath/plugin.class.php");
	     
	    $pluginclassname = 'plugin_'.$plugin;
	    $pluginclass = new $pluginclassname($report);
	}
	
	static function get_path($report, $component, $plugin){
	    global $CFG;
	     
	    $basedir = "$CFG->dirroot/blocks/configurable_reports";
	    $custompath = "report/$report->type";
	    $filepath = "components/$component/$plugin";
	    $file = "plugin.class.php";
	     
	    if (file_exists("$basedir/$custompath/$filepath/$file")) {
	        return "$basedir/$custompath/$filepath";
	    }
	     
	    return "$basedir/$filepath";
	}
	
	function __construct($report){
	    global $DB, $CFG;
		
		if(is_numeric($report))
			$this->report = $DB->get_record('block_configurable_reports_report',array('id' => $report));
		else
			$this->report = $report;
		$this->init();
	}
	
	function get_elements(){
	    global $DB;
	     
	    if(!isset($this->config)){
	        $this->config = array();
	        $search = array('reportid' => $this->id);
	        $records = $DB->get_records('block_configurable_reports_plugin', );
	        foreach($records as $record){
	            $record->configdata = cr_unserialize($record->configdata);
	            $this->plugconfig[$record->component][$record->plugin][$record->id] = $record;
	        }
	    }
	     
	    return $this->plugconfig[$component];
	}
	
	function get_form($action = null, $customdata = null){
	    if(!$this->form){
	        return null;
	    }
	     
	    $plugpath = self::get_path($this->config, $component, $plugin);
	    require_once("$plugpath/form.php");
	     
	    $classname = $plugin.'_form';
	    return new $classname($action, $customdata);
	}
	
	function summary(){
		return '';
	}
	
	function get_name(){
	    return get_string($this->name, 'block_configurable_reports');
	}
}

?>