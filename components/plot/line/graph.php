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

	require_once("../../../../../config.php");
	require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");

	require_login();

	error_reporting(0);
	ini_set('display_erros',false);

	$id = required_param('id', PARAM_ALPHANUM);
	$reportid = required_param('reportid', PARAM_INT);

	if(! $report = $DB->get_record('block_configurable_reports',array('id' => $reportid)))
		print_error('reportdoesnotexists');

	$courseid = $report->courseid;

	if (! $course = $DB->get_record("course",array( "id" =>  $courseid)) ) {
		print_error("No such course id");
	}

	// Force user login in course (SITE or Course)
	if ($course->id == SITEID){
        require_login();
        $context = context_system::instance();
    } else {
        require_login($course->id);
        $context = context_course::instance($course->id);
    }

	require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

	$reportclassname = 'report_'.$report->type;
	$reportclass = new $reportclassname($report);

	if (!$reportclass->check_permissions($USER->id, $context)){
		print_error("No permissions");
	}
	else{

		$components = cr_unserialize($report->components);
		$graphs = $components['plot']['elements'];

		if(!empty($graphs)){
			$series = array();
			foreach($graphs as $g){
				require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/'.$g['pluginname'].'/plugin.class.php');
				if($g['id'] == $id){
					$classname = 'plugin_'.$g['pluginname'];
					$class = new $classname($report);
					$series = $class->get_series($g['formdata']);
					break;
				}
			}

			if($g['id'] == $id){

				$min = optional_param('min',0,PARAM_INT);
				$max = optional_param('max',0,PARAM_INT);
				$abcise  = optional_param('abcise',-1,PARAM_INT);

				$abciselabel = array();
				if($abcise != -1){
					$abciselabel = $series[$abcise]['serie'];
					unset($series[$abcise]);
				}

				// Standard inclusions
				include($CFG->dirroot."/blocks/configurable_reports/lib/pChart/pData.class");
				include($CFG->dirroot."/blocks/configurable_reports/lib/pChart/pChart.class");

				// Dataset definition
				$DataSet = new pData;
				$lastid = 0;
				foreach($series as $key=>$val){
                    $DataSet->AddPoint($val['serie'] ,"Serie$key");
					$DataSet->AddAllSeries("Serie$key");
					$lastid = $key;
				}

				if(!empty($abciselabel)){
					$nk = $lastid + 1;
					$DataSet->AddPoint($abciselabel, "Serie$nk");
					$DataSet->SetAbsciseLabelSerie("Serie$nk");
				}
				else{
					$DataSet->SetAbsciseLabelSerie();
				}

				foreach($series as $key=>$val){
                    $value = $val['name'];
                    $isHebrew = preg_match("/[\xE0-\xFA]/", iconv("UTF-8", "ISO-8859-8", $value));
                    $fixedValue = ($isHebrew == 1) ? $reportclass->utf8_strrev($value) : $value;
					$DataSet->SetSerieName($fixedValue, "Serie$key");
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
                ob_clean(); // Hack to clear output and send only IMAGE data to browser
				$Test->Stroke();

			}
		}
	}
