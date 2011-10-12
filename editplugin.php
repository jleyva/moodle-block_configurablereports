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
  * A Moodle block for creating Configurable Reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

    require_once("../../config.php");
	
	require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");
	

	$id = required_param('id', PARAM_INT);
	$comp = required_param('comp', PARAM_ALPHA);
	$cid = optional_param('cid', '', PARAM_ALPHANUM);
	$pname = optional_param('pname', '', PARAM_ALPHA);
	
	$moveup = optional_param('moveup', 0, PARAM_INT);
	$movedown = optional_param('movedown', 0, PARAM_INT);
	$delete = optional_param('delete', 0, PARAM_INT);
	
	if(!$pname){
		header("Location: $CFG->wwwroot/blocks/configurable_reports/editcomp.php?id=$id&comp=$comp");
		die;
	}
	
	if(! $report = get_record('block_configurable_reports_report','id',$id))
		print_error('reportdoesnotexists');

	if (! $course = get_record("course", "id", $report->courseid) ) {
		print_error("No such course id");
	}	
	
	// Force user login in course (SITE or Course)
    if ($course->id == SITEID){
		require_login();
		$context = get_context_instance(CONTEXT_SYSTEM);
	}	
	else{
		require_login($course->id);		
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
	}

	if(! has_capability('block/configurable_reports:managereports', $context) && ! has_capability('block/configurable_reports:manageownreports', $context))
		print_error('badpermissions');
				
	if(! has_capability('block/configurable_reports:managereports', $context) && $report->ownerid != $USER->id)
		print_error('badpermissions');
		
	require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');
	
	$reportclassname = 'report_'.$report->type;	
	$reportclass = new $reportclassname($report->id);
	
	if(!in_array($comp,$reportclass->components))
		print_error('badcomponent');
		
	$cdata = null;
	$plugin = '';
	if(!$cid){
		if(filetype($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/'.$pname) == 'dir'){
			$plugin = $pname;
		}
	}
	else {
		$components = cr_unserialize($report->components);
		$elements = isset($components[$comp]['elements'])? $components[$comp]['elements'] : array();

		if($elements)
			foreach($elements as $e){
				if ($e['id'] == $cid){
					$cdata = $e;
					$plugin = $e['pluginname'];
					break;
				}
			}
			
		if(($moveup || $movedown || $delete) && confirm_sesskey()){
			foreach($elements as $index=>$e){
				if ($e['id'] == $cid){
					if($delete){
						unset($elements[$index]);
						break;
					}
					$newindex = ($moveup)? $index - 1 : $index +1;
					$tmp = $elements[$newindex];
					$elements[$newindex] = $e;
					$elements[$index] = $tmp;
					break;
				}
			}
			$components[$comp]['elements'] = $elements;
			$report->components = cr_serialize($components);
			update_record('block_configurable_reports_report',$report);
			header("Location: $CFG->wwwroot/blocks/configurable_reports/editcomp.php?id=$id&comp=$comp");			
			die;
		}
	}

	if(!$plugin || $plugin != $pname)
		print_error('nosuchplugin');
	
	require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/'.$pname.'/plugin.class.php');
	$pluginclassname = 'plugin_'.$pname;
	$pluginclass = new $pluginclassname($report);	
	
	if(isset($pluginclass->form) && $pluginclass->form){
		require_once($CFG->dirroot.'/blocks/configurable_reports/component.class.php');	
		require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/component.class.php');	
		$componentclassname = 'component_'.$comp;
		$compclass = new $componentclassname($report->id);
		
		require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/'.$pname.'/form.php');
		$classname = $pname.'_form';
		
		$formurl = "editplugin.php?id=$id&comp=$comp&pname=$pname";
		if($cid)
			$formurl .= "&cid=$cid";
		$editform = new $classname($formurl,compact('comp','cid','id','pluginclass','compclass','report','reportclass'));
			
		if(!empty($cdata)){		
			$editform->set_data($cdata['formdata']);
		}
			
		if($editform->is_cancelled()){
			if(!empty($report))
				redirect($CFG->wwwroot.'/blocks/configurable_reports/editreport.php?id='.$report->id);
			else
				redirect($CFG->wwwroot.'/blocks/configurable_reports/editreport.php');
		}
		else if ($data = $editform->get_data()) {	
			add_to_log($report->courseid, 'configurable_reports', 'edit', '', $report->name);
			if(!empty($cdata)){
				// cr_serialize() will add slashes
				$data = stripslashes_recursive($data);
				$cdata['formdata'] = $data;
				$cdata['summary'] = $pluginclass->summary($data);
				$elements = cr_unserialize($report->components);
				$elements = isset($elements[$comp]['elements'])? $elements[$comp]['elements'] : array();

				
				if($elements)
					foreach($elements as $key=>$e){
						if ($e['id'] == $cid){
							$elements[$key] = $cdata;
							break;
						}
					}		
				
				$allelements = cr_unserialize($report->components);
				$allelements[$comp]['elements'] = $elements;
				
				$report->components = cr_serialize($allelements);
				if(!update_record('block_configurable_reports_report',$report)){
					print_error('errorsaving');
				}
				else{
					header("Location: editcomp.php?id=$id&comp=$comp");
					die;
				}
					
			}
			else{
				
				$allelements = cr_unserialize($report->components);
				
				$uniqueid = random_string(15);
				while(strpos($report->components,$uniqueid) !== false){
					$uniqueid = random_string(15);
				}
				
				$cdata = array('id' => $uniqueid, 'formdata' => $data, 'pluginname' => $pname, 'pluginfullname' => $pluginclass->fullname, 'summary' => $pluginclass->summary($data));
				
				$allelements[$comp]['elements'][] = $cdata;
				$report->components = cr_serialize($allelements, false);
				if(!update_record('block_configurable_reports_report',$report)){
					print_error('errorsaving');
				}
				else{
					header("Location: editcomp.php?id=$id&comp=$comp");
					die;
				}
			}
		}
	}
	else{
		$allelements = cr_unserialize($report->components);
				
		$uniqueid = random_string(15);
		while(strpos($report->components,$uniqueid) !== false){
			$uniqueid = random_string(15);
		}
		
		$cdata = array('id' => $uniqueid, 'formdata' => new stdclass, 'pluginname' => $pname, 'pluginfullname' => $pluginclass->fullname, 'summary' => $pluginclass->summary(new stdclass));
		
		$allelements[$comp]['elements'][] = $cdata;
		$report->components = cr_serialize($allelements);
		if(!update_record('block_configurable_reports_report',$report)){
			print_error('errorsaving');
		}
		else{
			header("Location: editcomp.php?id=$id&comp=$comp");
			die;
		}
	}
	
	$title = format_string($report->name).' '.get_string($comp,'block_configurable_reports');	

	$navlinks = array();
	$navlinks[] = array('name' => get_string('managereports','block_configurable_reports'), 'link' => $CFG->wwwroot.'/blocks/configurable_reports/managereport.php?courseid='.$report->courseid, 'type' => 'title');
	$navlinks[] = array('name' => $title, 'link' => $CFG->wwwroot.'/blocks/configurable_reports/editcomp.php?id='.$id.'&amp;comp='.$comp, 'type' => 'title');
	$navlinks[] = array('name' => get_string($pname,'block_configurable_reports'), 'link' => null, 'type' => 'title');
	$navigation = build_navigation($navlinks);
	
	print_header($title, $title, $navigation, "", "", true);

	$currenttab = $comp;
	include('tabs.php');

	if($pluginclass->form)
		$editform->display();
	
	print_footer();

?>