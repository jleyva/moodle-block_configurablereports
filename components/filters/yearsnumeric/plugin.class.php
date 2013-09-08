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

class plugin_yearsnumeric extends plugin_base{

	function init(){
		$this->form = false;
		$this->unique = true;
		$this->fullname = get_string('filteryearsnumeric','block_configurable_reports');
		$this->reporttypes = array('categories','sql');
	}

	function summary($data){
		return get_string('filteryearsnumeric_summary','block_configurable_reports');
	}

	function execute($finalelements, $data){

		$filter_yearsnumeric = optional_param('filter_yearsnumeric',0,PARAM_INT);
		if(!$filter_yearsnumeric)
			return $finalelements;

		if ($this->report->type != 'sql') {
            return array($filter_yearsnumeric);
		} else {
			if (preg_match("/%%FILTER_YEARNUMERIC:([^%]+)%%/i",$finalelements, $output)) {
				$replace = ' AND '.$output[1].' LIKE \'%'.$filter_yearsnumeric.'%\'';
				return str_replace('%%FILTER_YEARNUMERIC:'.$output[1].'%%', $replace, $finalelements);
			}
		}
		return $finalelements;
	}

	function print_filter(&$mform){
		global $remoteDB, $CFG;

		$filter_yearsnumeric = optional_param('filter_yearsnumeric',0,PARAM_INT);

		$reportclassname = 'report_'.$this->report->type;
		$reportclass = new $reportclassname($this->report);
        $yearsnumeric = array('2010'=>'2010','2011'=>'2011','2012'=>'2012','2013'=>'2013','2014'=>'2014','2015'=>'2015');

		if($this->report->type != 'sql'){
			$components = cr_unserialize($this->report->components);
			$conditions = $components['conditions'];

			$yearsnumericlist = $reportclass->elements_by_conditions($conditions);
		} else {
			$yearsnumericlist = array_keys($yearsnumeric);
		}

		$courseoptions = array();
		$courseoptions[0] = get_string('filter_all', 'block_configurable_reports');

		if(!empty($yearsnumericlist)){
            // todo: check that keys of yearsnumeric array items are available
			foreach($yearsnumeric as $c){
				$yearsnumericoptions[$c->id] = format_string($c->name);
			}
		}

		$mform->addElement('select', 'filter_yearsnumeric', get_string('filter_yearsnumeric','block_configurable_reports'), $yearsnumericoptions);
		$mform->setType('filter_yearsnumeric', PARAM_INT);

	}

}

