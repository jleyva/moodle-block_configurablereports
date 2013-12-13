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
	require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/component.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

	$id = required_param('id', PARAM_INT);
	$comp = required_param('comp', PARAM_ALPHA);
    $courseid = optional_param('courseid', null, PARAM_INT);

	if(! $report = $DB->get_record('block_configurable_reports',array('id' => $id)))
		print_error('reportdoesnotexists');

    // Ignore report's courseid, If we are running this report on a specific courseid
    // (For permission checks)
    if (empty($courseid))
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

	$PAGE->set_url('/blocks/configurable_reports/editreport.php', array('id'=>$id,'comp'=>$comp));
	$PAGE->set_context($context);
	$PAGE->set_pagelayout('incourse');

    $PAGE->requires->js('/blocks/configurable_reports/js/configurable_reports.js');

if(! has_capability('block/configurable_reports:managereports', $context) && ! has_capability('block/configurable_reports:manageownreports', $context))
		print_error('badpermissions');


	if(! has_capability('block/configurable_reports:managereports', $context) && $report->ownerid != $USER->id)
		print_error('badpermissions');

	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

	$reportclassname = 'report_'.$report->type;
	$reportclass = new $reportclassname($report->id);

	if(!in_array($comp,$reportclass->components))
		print_error('badcomponent');

	$elements = cr_unserialize($report->components);
	$elements = isset($elements[$comp]['elements'])? $elements[$comp]['elements'] : array();

	require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/component.class.php');
	$componentclassname = 'component_'.$comp;
	$compclass = new $componentclassname($report->id);

	if($compclass->form){
		require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/form.php');
		$classname = $comp.'_form';
		$editform = new $classname('editcomp.php?id='.$id.'&comp='.$comp,compact('compclass','comp','id','report','reportclass','elements'));

		if($editform->is_cancelled()){
			redirect($CFG->wwwroot.'/blocks/configurable_reports/editcomp.php?id='.$id.'&amp;comp='.$comp);
		}
		else if ($data = $editform->get_data()) {
			$compclass->form_process_data($editform);
			add_to_log($courseid, 'configurable_reports', 'edit', '', $report->name);
		}

		$compclass->form_set_data($editform);

	}

	if($compclass->plugins){
		$currentplugins = array();
		if($elements){
			foreach($elements as $e){
				$currentplugins[] = $e['pluginname'];
			}
		}
		$plugins = get_list_of_plugins('blocks/configurable_reports/components/'.$comp);
		$optionsplugins = array();
		foreach($plugins as $p){
			require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/'.$p.'/plugin.class.php');
			$pluginclassname = 'plugin_'.$p;
			$pluginclass = new $pluginclassname($report);
			if(in_array($report->type,$pluginclass->reporttypes)){
				if($pluginclass->unique && in_array($p,$currentplugins))
					continue;
				$optionsplugins[$p] = get_string($p,'block_configurable_reports');
			}
		}
		asort($optionsplugins);
	}


    //$courseurl =  new moodle_url($CFG->wwwroot.'/course/view.php',array('id'=>$report->courseid));
    //$PAGE->navbar->add($COURSE->shortname, $courseurl);

    $managereporturl =  new moodle_url($CFG->wwwroot.'/blocks/configurable_reports/managereport.php',array('courseid'=>$courseid));
    $PAGE->navbar->add(get_string('managereports','block_configurable_reports'), $managereporturl);

    $PAGE->navbar->add($report->name);

    $title = format_string($report->name);//.' '.get_string($comp,'block_configurable_reports');
    $PAGE->set_title($title);
	$PAGE->set_heading($title);
	$PAGE->set_cacheable(true);

	echo $OUTPUT->header();

	$currenttab = $comp;
	include('tabs.php');

	if($elements){
		$table = new stdclass;
		$table->head = array(get_string('idnumber'),get_string('name'),get_string('summary'),get_string('edit'));
		$i = 0;

		foreach($elements as $e){
			require_once($CFG->dirroot.'/blocks/configurable_reports/components/'.$comp.'/'.$e['pluginname'].'/plugin.class.php');
			$pluginclassname = 'plugin_'.$e['pluginname'];
			$pluginclass = new $pluginclassname($report);

			$editcell = '';

			if($pluginclass->form){
				$editcell .= '<a href="editplugin.php?id='.$id.'&comp='.$comp.'&pname='.$e['pluginname'].'&cid='.$e['id'].'"><img src="'.$OUTPUT->pix_url('/t/edit').'" class="iconsmall"></a>';
			}

			$editcell .= '<a href="editplugin.php?id='.$id.'&comp='.$comp.'&pname='.$e['pluginname'].'&cid='.$e['id'].'&delete=1&amp;sesskey='.sesskey().'"><img src="'.$OUTPUT->pix_url('/t/delete').'" class="iconsmall"></a>';

			if($compclass->ordering && $i != 0 && count($elements) > 1)
				$editcell .= '<a href="editplugin.php?id='.$id.'&comp='.$comp.'&pname='.$e['pluginname'].'&cid='.$e['id'].'&moveup=1&amp;sesskey='.sesskey().'"><img src="'.$OUTPUT->pix_url('/t/up').'" class="iconsmall"></a>';
			if($compclass->ordering && $i != count($elements) -1)
				$editcell .= '<a href="editplugin.php?id='.$id.'&comp='.$comp.'&pname='.$e['pluginname'].'&cid='.$e['id'].'&movedown=1&amp;sesskey='.sesskey().'"><img src="'.$OUTPUT->pix_url('/t/down').'" class="iconsmall"></a>';

			$table->data[] = array('c'.($i+1),$e['pluginfullname'],$e['summary'],$editcell);
			$i++;
		}
		cr_print_table($table);
	} else {
		if($compclass->plugins)
			echo $OUTPUT->heading(get_string('no'.$comp.'yet','block_configurable_reports'));
	}

	if($compclass->plugins) {
		echo '<div class="boxaligncenter">';
		echo '<p class="centerpara">';
		print_string('add');
		echo ': &nbsp;';
		//choose_from_menu($optionsplugins,'plugin','',get_string('choose'),"location.href = 'editplugin.php?id=".$id."&comp=".$comp."&pname='+document.getElementById('menuplugin').value");
		$attributes = array('id'=>'menuplugin');

		echo html_writer::select($optionsplugins,'plugin','', array(''=>get_string('choose')), $attributes);
		$OUTPUT->add_action_handler(new component_action('change', 'menuplugin',array('url'=>"editplugin.php?id=".$id."&comp=".$comp."&pname=")),'menuplugin');
		echo '</p>';
		echo '</div>';
	}

	if($compclass->form){
		$editform->display();
	}

	if($compclass->help){
		echo '<div class="boxaligncenter">';
		echo '<p class="centerpara">';
		echo $OUTPUT->help_icon('comp_'.$comp,'block_configurable_reports',get_string('comp_'.$comp,'block_configurable_reports'));
		//helpbutton('comp_'.$comp, get_string('componenthelp','block_configurable_reports'),'block_configurable_reports', true, true);
		echo '</p>';
		echo '</div>';
	}

	echo $OUTPUT->footer();

