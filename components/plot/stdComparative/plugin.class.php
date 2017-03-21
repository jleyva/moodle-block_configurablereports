<?php

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_stdComparative extends plugin_base{
	
	function init(){
		$title = get_string('stdComparative','block_configurable_reports');
		
		if ($title && trim($title) && strpos($title,'[')!==false )
			$this->fullname = $title; //get_string('line','block_configurable_reports');		
		else
			$this->fullname = 'stdComparative';
		
		$this->form = true;
		$this->ordering = true;
		$this->reporttypes = array('timeline', 'sql','timeline');		
	}
	
	function summary($data){
		return get_string('linesummary','block_configurable_reports');
	}
	
	// data -> Plugin configuration data
	function execute($id, $data, $finalreport){
		global $DB, $CFG;

		$series = array();
		$data->xaxis--;
		$data->yaxis--;
		$data->serieid--;
		$minvalue = 0;
		$maxvalue = 0;
		
		if($finalreport){
			foreach($finalreport as $r){
				$hash = md5(strtolower($r[$data->serieid]));					
				$sname[$hash] = $r[$data->serieid];
				$val = (isset($r[$data->yaxis]) && is_numeric($r[$data->yaxis]))? $r[$data->yaxis] : 0;
				$series[$hash][] = $val;
				$minvalue = ($val < $minvalue)? $val : $minvalue;
				$maxvalue = ($val > $maxvalue)? $val : $maxvalue;
			}			
		}
		
		$params = '';
				
		$i = 0;
		foreach($series as $h=>$s){
			$params .= "&amp;serie$i=".base64_encode($sname[$h].'||'.implode(',',$s));		
			$i++;
		}
		
		return $CFG->wwwroot.'/blocks/configurable_reports/components/plot/stdComparative/graph.php?reportid='.$this->report->id.'&id='.$id.$params.'&amp;min='.$minvalue.'&amp;max='.$maxvalue;
	}
	
	function get_series($data){
		$series = array();
		foreach($_GET as $key=>$val){
			if(strpos($key,'serie') !== false){
				$id = (int) str_replace('serie','',$key);
				list($name, $values) = explode('||',base64_decode($val));
				$series[$id] = array('serie'=> explode(',',$values), 'name'=> $name);
			}
		}
		return $series;
	}
	
}
