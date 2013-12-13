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

class component_conditions extends component_base{

	function init(){
		$this->plugins = true;
		$this->ordering = false;
		$this->form = true;
		$this->help = true;
	}

	function form_process_data(&$cform){
		global $DB;

		if($this->form){
			$data = $cform->get_data();
			// cr_serialize() will add slashes

			$components = cr_unserialize($this->config->components);
			$components['conditions']['config'] = $data;
			if(isset($components['conditions']['config']->conditionexpr)){
				$components['conditions']['config']->conditionexpr = $this->add_missing_conditions($components['conditions']['config']->conditionexpr);
			}
			$this->config->components = cr_serialize($components);
			$DB->update_record('block_configurable_reports',$this->config);
		}
	}

	function add_missing_conditions($cond){
		global $DB;

		$components = cr_unserialize($this->config->components);

		if(isset($components['conditions']['elements'])){

			$elements = $components['conditions']['elements'];
			$count = count($elements);
			if($count == 0 || $count == 1)
				return '';
			for($i=$count; $i > 0; $i--){
				if(strpos($cond,'c'.$i) === false){
					if($count > 1 && $cond)
						$cond .= " and c$i";
					else
						$cond .= "c$i";
				}
			}

			// Deleting extra conditions

			for($i = $count + 1; $i <= $count + 5; $i++){
				$cond = preg_replace('/(\bc'.$i.'\b\s+\b(and|or|not)\b\s*)/i','',$cond);
				$cond = preg_replace('/(\s+\b(and|or|not)\b\s+\bc'.$i.'\b)/i','',$cond);
			}
		}

		return $cond;
	}

	function form_set_data(&$cform){
		global $DB;
		if($this->form){
			$fdata = new stdclass;
			$components = cr_unserialize($this->config->components);
			//print_r($components);exit;
			$conditionsconfig = (isset($components['conditions']['config']))? $components['conditions']['config'] : new stdclass;

			if(!isset($conditionsconfig->conditionexpr)){
				$conditionsconfig->conditionexpr = '';
				$conditionsconfig->conditionexpr = '';
			}
			$conditionsconfig->conditionexpr = $this->add_missing_conditions($conditionsconfig->conditionexpr);
			$fdata->conditionexpr = $conditionsconfig->conditionexpr;

			if (empty($components['conditions'])) {
				$components['conditions'] = array();
			}

			$components['conditions']['config']->conditionexpr = $fdata->conditionexpr;
			$this->config->components = cr_serialize($components);
			$DB->update_record('block_configurable_reports',$this->config);

			$cform->set_data($fdata);
		}
	}

}

