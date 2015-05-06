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

/** Configurable Reports«
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

	require_once("../../../../../config.php");
	require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");
	

	require_login();

	#error_reporting(0);
	#ini_set('display_erros',false);

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
	} else {
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

			if($g['id'] == $id) {
				include($CFG->dirroot."/blocks/configurable_reports/lib/pChart2/class/pDraw.class.php");
				include($CFG->dirroot."/blocks/configurable_reports/lib/pChart2/class/pData.class.php");
				include($CFG->dirroot."/blocks/configurable_reports/lib/pChart2/class/pImage.class.php");
						
				
				// Dataset definition
				$DataSet = new pData();
				$f = fopen("/tmp/bar.series-pre","w"); fwrite($f,print_r($series,true)); fclose($f);
				$labels = array_shift($series);
				
				// Invert/Reverse Hebrew labels so it can be rendered using PHP imagettftext()
				// Also find the longest value, to aid with sizing the image
				$longest_label = 0 ;
				foreach ($labels as $key => $value) {
					$label_len = strlen($value);
					$label_len > $longest_label && $longest_label = $label_len;
					$invertedlabels[$key] = strip_tags((preg_match("/[\xE0-\xFA]/", iconv("UTF-8", "ISO-8859-8", $value))) ? $reportclass->utf8_strrev($value) : $value);
				}
				$DataSet->addPoints($invertedlabels,"Labels");
				$DataSet->setAbscissa("Labels");
				$f = fopen("/tmp/bar.series","w"); fwrite($f,print_r($series,true)); fclose($f);
				$longest_legend = 0;
				foreach ($series as $name => $valueset) {
					$legend_len = strlen($name);
					$legend_len > $longest_legend && $longest_legend = $legend_len;
					$DataSet->addPoints($valueset,$name);
				}
				
				$width = property_exists($g['formdata'],"width") ? $g['formdata']->width : 900;
				$height = property_exists($g['formdata'],"height") ? $g['formdata']->height : 500;
				$color_r = property_exists($g['formdata'],"color_r") ? $g['formdata']->color_r : 170;
				$color_g = property_exists($g['formdata'],"color_g") ? $g['formdata']->color_g : 183;
				$color_b = property_exists($g['formdata'],"color_b") ? $g['formdata']->color_b : 87;
				$padding = 30;
				$font_size = 8;
				$font_path = $CFG->dirroot."/blocks/configurable_reports/lib/pChart2/Fonts";
                $label_offset = $longest_label * ($font_size/2);
                $min_label_offset = $padding + 100;
>--->--->--->---$max_label_offset = $height / 2 + $padding;
                if ($label_offset < $min_label_offset) {
                    $label_offset = $min_label_offset;
                } else if ($label_offset > $max_label_offset) {
>--->--->--->--->---$label_offset = $max_label_offset;>-
>--->--->--->---}
				$legend_offset = ($longest_legend * ($font_size/2));
				$max_legend_offset = $width / 3 + $padding;
				if ($legend_offset > $max_legend_offset) {
					$legend_offset = $max_legend_offset;	
				}	
				
				$myPicture = new pImage($width,$height,$DataSet);
				$myPicture->setFontProperties(array("FontName"=>"$font_path/calibri.ttf","FontSize"=>$font_size));
				list($legend_width,$legend_height) = array_values($myPicture->getLegendSize());
				$legend_x = $width - $legend_width - $padding;
				$legend_y = $padding;				
                $colnames = array_keys($series);
                $firstcol = $colnames[0];
				$graph_x = $padding + (strlen($firstcol) * ($font_size/2));
				$graph_y = $padding;
				$graph_width = $legend_x - $padding;
				$graph_height = $height - $label_offset;
				
				#$Settings = array("R"=>$color_r, "G"=>$color_g, "B"=>$color_b);
				$BGSettings = array(
					"R"=>225, 
					"G"=>225, 
					"B"=>225
				);
				$myPicture->drawFilledRectangle(0,0,$width+2,$height+2,$BGSettings);
				$myPicture->setGraphArea($graph_x,$graph_y,$graph_width,$graph_height);
				
				$ScaleSettings = array(
					"TickR"=>0, 
					"TickG"=>0, 
					"TickB"=>0,
					"LabelRotation"=>45,
					"DrawSubTicks"=>TRUE
				);
				$myPicture->drawScale($ScaleSettings);
				$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
				
				$ChartSettings  = array(
					"DisplayValues"=>TRUE,
					#"DisplayColor"=>DISPLAY_AUTO,
					"Rounded"=>TRUE,
					"Surrounding"=>60,
					"DisplayR"=>0,
					"DisplayG"=>0,
					"DisplayB"=>0,
					"DisplayOffset"=>5
				);
				$myPicture->drawBarChart($ChartSettings);
				$myPicture->setShadow(FALSE);
				$myPicture->drawLegend($legend_x,$legend_y);
				$myPicture->stroke();
								
                		ob_clean(); // Hack to clear output and send only IMAGE data to browser
				

            }
		}
	}
