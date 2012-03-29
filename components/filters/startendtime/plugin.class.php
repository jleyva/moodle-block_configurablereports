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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/filters/plugin.class.php');

class plugin_startendtime extends filters_plugin{

	function execute($finalelements, $data){
	
		if ($this->report->config->type != 'sql') {
			return $finalelements;
	    }
		
		$filterstart = optional_param('filter_starttime',0,PARAM_RAW);
		$filterend = optional_param('filter_endtime',0,PARAM_RAW);
		if(!$filterstart || ! $filterend)
			return $finalelements;
			
		$filterstart = make_timestamp($filterstart['year'], $filterstart['month'], $filterstart['day']);
		$filterend = make_timestamp($filterend['year'], $filterend['month'], $filterend['day']);
				
		$operators = array('<','>','<=','>=');
		
		if(preg_match("/%%FILTER_STARTTIME:([^%]+)%%/i", $finalelements, $output)){
			list($field,$operator) = explode(':',$output[1]);		
			if(!in_array($operator,$operators))
				print_error('nosuchoperator');
			$replace = ' AND '.$field.' '.$operator.' '.$filterstart;			
			$finalelements = str_replace('%%FILTER_STARTTIME:'.$output[1].'%%',$replace,$finalelements);
		}			
		
		if(preg_match("/%%FILTER_ENDTIME:([^%]+)%%/i", $finalelements, $output)){
			list($field,$operator) = explode(':',$output[1]);
			if(!in_array($operator,$operators))
				print_error('nosuchoperator');
			$replace = ' AND '.$field.' '.$operator.' '.$filterend;			
			$finalelements = str_replace('%%FILTER_ENDTIME:'.$output[1].'%%',$replace,$finalelements);
		}
		
		$finalelements = str_replace('%STARTTIME%%',$filterstart,$finalelements);
		$finalelements = str_replace('%ENDTIME%%',$filterend,$finalelements);
		
		return $finalelements;
	}
	
	function print_filter(&$mform, $instance){
		global $DB, $CFG;
		
        $mform->addElement('date_selector', 'filter_starttime', get_string('starttime', 'block_configurable_reports'));
		$mform->setDefault('filter_starttime', time() - 3600 * 24);
        $mform->addElement('date_selector', 'filter_endtime', get_string('endtime', 'block_configurable_reports'));
		$mform->setDefault('filter_endtime', time() + 3600 * 24);
	}
	
}

?>