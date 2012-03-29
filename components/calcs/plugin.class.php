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
 * @author: Nick Koeppen
 */

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin.class.php');

abstract class calcs_plugin extends plugin_base{
	
	function has_form(){
	    return true;
	}
	
	function summary($instance){
		if(! ($data = $instance->configdata)){
		    return '';
		}
		
		$options = $this->report->get_column_options();
		
		return $options[$data->column];
	}
	
	abstract function execute($rows);
}

?>