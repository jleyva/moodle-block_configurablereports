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

class component_customsql extends component_base{

	function init(){
		global $PAGE;

		$this->plugins = false;
		$this->ordering = false;
		$this->form = true;
		$this->help = true;

		if (get_config('block_configurable_reports', 'sqlsyntaxhighlight')) {
	        $PAGE->requires->js('/blocks/configurable_reports/js/codemirror/lib/codemirror.js');
	        $PAGE->requires->js('/blocks/configurable_reports/js/codemirror/mode/sql/sql.js');
	        $PAGE->requires->js('/blocks/configurable_reports/js/codemirror/addon/display/fullscreen.js');
	        $PAGE->requires->js('/blocks/configurable_reports/js/codemirror/addon/edit/matchbrackets.js');
	    }

	    $PAGE->requires->js_init_call('M.block_configurable_reports.init');
	}

	function form_process_data(&$cform){
		global $DB;
		if($this->form){
			$data = $cform->get_data();
			// cr_serialize() will add slashes
			$components = cr_unserialize($this->config->components);
			$components['customsql']['config'] = $data;
			$this->config->components = cr_serialize($components);
			$DB->update_record('block_configurable_reports',$this->config);
		}
	}

	function form_set_data(&$cform){
		if($this->form){
			$fdata = new stdclass;
			$components = cr_unserialize($this->config->components);
			//print_r($components);exit;
			$sqlconfig = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;
			$cform->set_data($sqlconfig);
		}
	}
}

