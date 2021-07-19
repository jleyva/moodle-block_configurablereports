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
				//ADD
				include($CFG->dirroot."/blocks/configurable_reports/lib/pChart2/class/pSurface.class.php");	
				
				// Dataset definition
				$DataSet = new pData();
				$f = fopen("/tmp/heatmap.series-pre","w"); fwrite($f,print_r($series,true)); fclose($f);
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
				$f = fopen("/tmp/heatmap.series","w"); fwrite($f,print_r($series,true)); fclose($f);
				
				$longest_legend = 0;
				$row_len	= 0;
				$i		= 0;
				foreach ($series as $name => $valueset) {
					$legend_len = strlen($name);
					$legend_len > $longest_legend && $longest_legend = $legend_len;
					$DataSet->addPoints($valueset,$name);
					$row_len=count($valueset)-1;
				}
				
				$width = property_exists($g['formdata'],"width") ? $g['formdata']->width : 900;
				$height = property_exists($g['formdata'],"height") ? $g['formdata']->height : 500;		
				$colnames = array_keys($series);
				$firstcol = $colnames[0];

/*< Create the surface object >*/
 $font_path = $CFG->dirroot."/blocks/configurable_reports/lib/pChart2/fonts";
 $myPicture = new pImage($width,$height,$DataSet);//$myPicture = new pImage(400,400);

 /* Create a solid background */
 $Settings = array("R"=>179, "G"=>217, "B"=>91, "Dash"=>1, "DashR"=>199, "DashG"=>237, "DashB"=>111);
 //out $myPicture->drawFilledRectangle(0,0,/*400,400*/$width,$height,$Settings);

 /* Do a gradient overlay */
 $Settings = array("StartR"=>194, "StartG"=>231, "StartB"=>44, "EndR"=>43, "EndG"=>107, "EndB"=>58, "Alpha"=>50);
 $myPicture->drawGradientArea(0,0,/*400,400*/$width,$height,DIRECTION_VERTICAL,$Settings);
 $myPicture->drawGradientArea(0,0,/*400*/$width,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>100));

 /* Add a border to the picture */
 $myPicture->drawRectangle(0,0,/*399,399*/$width-1,$height-1,array("R"=>0,"G"=>0,"B"=>0));
 
 /* Write the picture title */ 
 $myPicture->setFontProperties(array("FontName"=>"$font_path/Silkscreen.ttf","FontSize"=>6));
 $myPicture->drawText(10,13,"HEAT MAP :: 2D chart",array("R"=>255,"G"=>255,"B"=>255));

 /* Define the charting area */
 $myPicture->setGraphArea(20,40,$width-20,$height-20);
 $myPicture->drawFilledRectangle(20,40,/*380,380*/$width-20,$height-20,array("R"=>255,"G"=>255,"B"=>255,"Surrounding"=>-200,"Alpha"=>20));

 $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1));

 /* Create the surface object */
 $mySurface = new pSurface($myPicture);

 /* Set the grid size */
 //$mySurface->setGrid(20,20);
 $Xlen = count($colnames)-1;
 $Ylen = $row_len;
 $mySurface->setGrid($Xlen,$Ylen);

 /* Write the axis labels */
 $myPicture->setFontProperties(array("FontName"=>"$font_path/pf_arma_five.ttf","FontSize"=>6));
 $mySurface->writeXLabels( array("Labels"=>$colnames) );
 $mySurface->writeYLabels();

 /* Add random values */
 $j = 0;
 /*foreach($colnames as $col) 
 { 	
 	for($i=0; $i<$Ylen; $i++){
		$val =  $Dataset->getValueAt($col,$i);
 		$mySurface->addPoint($j,$i,$val); 
 	}
 	$j++;
 }*/
//IN 
$j = 0;
 foreach ($series as $name => $valueset) 
 { $i = 0;
	 foreach ($valueset as $val) 
	 { 
		$mySurface->addPoint($j,$i,$val);
		//$DataSet->addPoints($valueset,$name);
		//$row_len=count($valueset)-1;
		$i++;	
	}
  $j++;		
 } 


 /* Compute the missing points */
 //$mySurface->computeMissing();

 /* Draw the surface chart */
// $mySurface->drawSurface( array("Border"=>TRUE,"Surrounding"=>40 /*,"ShadeR1"=>0,"ShadeG1"=>0,"ShadeB1"=>0,"ShadeR2"=>255,"ShadeG2"=>255,"ShadeB2"=>255 */) );
//$mySurface->drawSurface( array("Border"=>TRUE,"Surrounding"=>40,"ShadeR1"=>255,"ShadeG1"=>255,"ShadeB1"=>255,"ShadeR2"=>0,"ShadeG2"=>0,"ShadeB2"=>0 ) );
//$mySurface->drawSurface( array("Border"=>TRUE,"Surrounding"=>40,"ShadeR1"=>255,"ShadeG1"=>0,"ShadeB1"=>0,"ShadeR2"=>0,"ShadeG2"=>255,"ShadeB2"=>0 ) );
//$mySurface->drawSurface( array("Border"=>TRUE,"Surrounding"=>40,"ShadeR1"=>77,"ShadeG1"=>205,"ShadeB1"=>21,"ShadeR2"=>227,"ShadeG2"=>135,"ShadeB2"=>61 ) );
//IN $mySurface->drawSurface( array("Border"=>TRUE,"Surrounding"=>40,"ShadeR1"=>110,"ShadeG1"=>110,"ShadeB1"=>110,"ShadeR2"=>125,"ShadeG2"=>125,"ShadeB2"=>125 ) );
//IN 
$mySurface->drawSurface( array("Border"=>TRUE,"Surrounding"=>40 /*,"ShadeR1"=>145,"ShadeG1"=>22,"ShadeB1"=>0,"ShadeR2"=>160,"ShadeG2"=>41,"ShadeB2"=>11 */) );
/*< Create the surface object /> */				
				
$myPicture->setShadow(FALSE);
ob_clean(); // Hack to clear output and send only IMAGE data to browser
$myPicture->stroke();	
            }
		}
	}
