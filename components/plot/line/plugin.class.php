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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/plugin.class.php');

class plugin_line extends plot_plugin{
	
	function init(){
		$this->fullname = get_string('line','block_configurable_reports');
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
		$min = 0;
		$max = 0;
		
		if($finalreport){
			foreach($finalreport as $r){
				$hash = md5(strtolower($r[$data->serieid]));					
				$sname[$hash] = $r[$data->serieid];
				$val = (isset($r[$data->yaxis]) && is_numeric($r[$data->yaxis]))? $r[$data->yaxis] : 0;
				$series[$hash][] = $val;
				$min = ($val < $min)? $val : $min;
				$max = ($val > $max)? $val : $max;
			}			
		}

		$i = 0;
		$params = compact('id', 'min', 'max');
		foreach($series as $h=>$s){
		    $params['serie'.$i] = $sname[$h].'||'.implode(',',$s);
			$i++;
		}
		
		return $this->get_graphurl($params);
	}
	
	function get_series($instanceid){
	    $instance = $this->get_instance($instanceid);
	    
		$series = array();
		foreach($instance->configdata as $series => $values){
			if(strpos($key,'serie') !== false){
				$id = (int) str_replace('serie','',$key);
				list($name, $values) = explode('||',base64_decode($val));
				$series[$id] = array('serie'=> explode(',',$values), 'name'=> $name);
			}
		}
		
		return $series;
	}
	
	function graph($series){
	    $min = optional_param('min',0,PARAM_INT);
	    $max = optional_param('max',0,PARAM_INT);
	    $abcise  = optional_param('abcise',-1,PARAM_INT);
	    
	    $abciselabel = array();
	    if ($abcise != -1) {
	        $abciselabel = $series[$abcise]['serie'];
	        unset($series[$abcise]);
	    }
	    
	    // Dataset definition
	    $DataSet = new pData;
	    $lastid = 0;
	    foreach($series as $key=>$val){
	        $DataSet->AddPoint($val['serie'],"Serie$key");
	        $DataSet->AddAllSeries("Serie$key");
	        $lastid = $key;
	    }
	    
	    if (!empty($abciselabel)) {
	        $nk = $lastid + 1;
	        $DataSet->AddPoint($abciselabel,"Serie$nk");
	        $DataSet->SetAbsciseLabelSerie("Serie$nk");
	    } else {
	        $DataSet->SetAbsciseLabelSerie();
	    }
	     
	    foreach($series as $key=>$val){
	        $DataSet->SetSerieName($val['name'],"Serie$key");
	    }
	    	
	    // Initialise the graph
	    $Test = new pChart(700,230);
	    $Test->setFixedScale($min, $max);
	    
	    $Test->setFontProperties($CFG->dirroot."/blocks/configurable_reports/lib/Fonts/tahoma.ttf",8);
	    $Test->setGraphArea(70,30,680,200);
	    $Test->drawFilledRoundedRectangle(7,7,693,223,5,240,240,240);
	    $Test->drawRoundedRectangle(5,5,695,225,5,230,230,230);
	    $Test->drawGraphArea(255,255,255,TRUE);
	    $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2);
	    
	    $Test->drawGrid(4,TRUE,230,230,230,50);
	    
	    // Draw the 0 line
	    $Test->setFontProperties($CFG->dirroot."/blocks/configurable_reports/lib/Fonts/tahoma.ttf",10);
	    $Test->drawTreshold(0,143,55,72,TRUE,TRUE);
	    
	    // Draw the line graph
	    $Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());
	    $Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);
	    
	    // Finish the graph
	    $Test->setFontProperties($CFG->dirroot."/blocks/configurable_reports/lib/Fonts/tahoma.ttf",8);
	    $Test->drawLegend(75,35,$DataSet->GetDataDescription(),255,255,255);
	    $Test->Stroke();
	}
}

?>