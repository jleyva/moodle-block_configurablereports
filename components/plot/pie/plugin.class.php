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

class plugin_pie extends plot_plugin{
	
	function init(){
		$this->fullname = get_string('pie','block_configurable_reports');
	}
	
	function summary($instance){
		return get_string('piesummary','block_configurable_reports');
	}
	
	// data -> Plugin configuration data
	function execute($instance, $finalreport){
		if(! ($data = $instance->configdata)){
		    return '';
		}

		$series = array();
		if($finalreport){
			foreach($finalreport as $r){
				if($data->areaname == $data->areavalue){
					$hash = md5(strtolower($r[$data->areaname]));
					if(isset($series[0][$hash])){
						$series[1][$hash] += 1;
					}
					else{
						$series[0][$hash] = str_replace(',','',$r[$data->areaname]);
						$series[1][$hash] = 1;
					}
				
				}else if(!isset($data->group) || ! $data->group){
					$series[0][] = str_replace(',','',$r[$data->areaname]);
					$series[1][] = (isset($r[$data->areavalue]) && is_numeric($r[$data->areavalue]))? $r[$data->areavalue] : 0;
				}else{
					$hash = md5(strtolower($r[$data->areaname]));
					if(isset($series[0][$hash])){
						$series[1][$hash] += (isset($r[$data->areavalue]) && is_numeric($r[$data->areavalue]))? $r[$data->areavalue] : 0;
					}
					else{
						$series[0][$hash] = str_replace(',','',$r[$data->areaname]);
						$series[1][$hash] = (isset($r[$data->areavalue]) && is_numeric($r[$data->areavalue]))? $r[$data->areavalue] : 0;
					}					
				}
			}
		}
		
		$id = $instance->id;
		$serie0 = base64_encode(implode(',', $series[0]));
		$serie1 = base64_encode(implode(',', $series[1]));
		
		return $this->get_graphurl(compact('id', 'serie0', 'serie1'));
	}
	
	function get_series($instanceid){
		$serie0 = required_param('serie0',PARAM_RAW);
		$serie1 = required_param('serie1',PARAM_BASE64);
						
		return array(explode(',',base64_decode($serie0)),explode(',',base64_decode($serie1)));
	}
	
	function graph($series){
	    // Dataset definition
	    $DataSet = new pData;
	    
	    $DataSet->AddPoint($series[1],"Serie1");
	    $DataSet->AddPoint($series[0],"Serie2");
	    $DataSet->AddAllSeries();
	    $DataSet->SetAbsciseLabelSerie("Serie2");
	    
	    // Initialise the graph
	    $Test = new pChart(450,200 + (count($series[0]) * 10));
	    $Test->drawFilledRoundedRectangle(7,7,293,193,5,240,240,240);
	    $Test->drawRoundedRectangle(5,5,295,195,5,230,230,230);
	    
	    // Draw the pie chart
	    $Test->setFontProperties($CFG->dirroot."/blocks/configurable_reports/lib/Fonts/tahoma.ttf",8);
	    //$Test->drawFlatPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),120,100,60,TRUE,10);
	    //$Test->drawBasicPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),120,100,70,PIE_PERCENTAGE,255,255,218);
	    $Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),150,90,110,PIE_PERCENTAGE,TRUE,50,20,5);
	    $Test->drawPieLegend(300,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250);
	    
	    $Test->Stroke();
	}
}

?>