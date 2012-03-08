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
require_once("$CFG->dirroot/blocks/configurable_reports/locallib.php");
require_once($CFG->dirroot.'/blocks/configurable_reports/reports/report.class.php');

$id   = required_param('id', PARAM_INT);
$comp = required_param('comp', PARAM_ALPHA);

if (! ($report = $DB->get_record('block_configurable_reports_report', array('id' => $id)))) {
	print_error('reportdoesnotexists');
}
if (! ($course = $DB->get_record("course", array( "id" => $report->courseid)))) {
	print_error('invalidcourseid');
}	

// Force user login in course (SITE or Course)
if ($course->id == SITEID) {
	require_login();
	$context = context_system::instance();
} else {
	require_login($course->id);		
	$context = context_course::instance($course->id);
}
// Capability check
if($report->ownerid != $USER->id){
    require_capability('block/configurable_reports:managereports', $context);
}else{
    require_capability('block/configurable_reports:manageownreports', $context);
}

$params = array('id' => $id, 'comp' => $comp);
$baseurl = new moodle_url('/blocks/configurable_reports/editcomp.php');
$manageurl = new moodle_url('/blocks/configurable_reports/managereport.php', array('courseid' => $report->courseid));
$editurl = new moodle_url('/blocks/configurable_reports/editplugin.php', $params);
$PAGE->set_url($baseurl, $params);
$PAGE->set_context($context);	
$PAGE->set_pagelayout('incourse');
$PAGE->requires->js('/blocks/configurable_reports/js/configurable_reports.js');
	
$reportclass = report_base::get($report);
$compclass = $reportclass->get_component($comp);
if (!isset($compclass)) {
    print_error('badcomponent');
}
$compconfig = $reportclass->get_component_config($comp);

$title = $reportclass->get_name().' '.$compclass->get_name();
navigation_node::override_active_url($manageurl);
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// TODO: Fix form handling
if($compclass->has_form()){
    $editform = $compclass->get_form($PAGE->url);
	
	if ($editform->is_cancelled()) {
		redirect(new moodle_url('/blocks/configurable_reports/editreport.php', array('id' => $id)));
		
	} else if ($data = $editform->get_data()) {
		$compclass->form_process_data($editform);
		add_to_log($report->courseid, 'configurable_reports', 'edit', '', $report->name);
	}
	
	$compclass->form_set_data($editform);
}

if ($compconfig) {
    $table = new stdclass;
    $table->head = array(get_string('idnumber'), get_string('name'), get_string('summary'), get_string('edit'));
    
    $icons = array(
            'edit'      => new pix_icon('t/edit', get_string('edit')),
            'delete'    => new pix_icon('t/delete', get_string('delete')),
            'moveup'    => new pix_icon('t/up', get_string('hide')),
            'movedown'  => new pix_icon('t/down', get_string('show')),
    );
    $divider = '&nbsp;&nbsp;';
    $pixattr = array('class'=>'iconsmall');
    
    $i = 0;
    $numelements = count($compconfig);
    foreach($compconfig as $plugin => $config){
        //TODO: Figure out cid linkage
        $editurl->params(array('pname' => $plugin));
        $pluginclass = plugin_base::get($report, $comp, $plugin);
    
        $commands = array();
        if($pluginclass->form){
            $commands[] = $OUTPUT->action_icon($editurl, $icons['edit'], null, $pixattr);
        }
        $url = $editurl;
        $url->params(array('delete' => $config->id, 'sesskey' => $USER->sesskey));
        $commands[] = $OUTPUT->action_icon($url, $icons['delete'], null, $pixattr);
        
        if ($compclass->ordering && $i != 0 && $numelements > 1) {
            $url = $editurl;
            $url->params(array('moveup' => $config->id, 'sesskey' => $USER->sesskey));
            $commands[] = $OUTPUT->action_icon($url, $icons['moveup'], null, $pixattr);
        }
        if($compclass->ordering && $i != $numelements -1){
            $url = $editurl;
            $url->params(array('movedown' => $config->id, 'sesskey' => $USER->sesskey));
            $commands[] = $OUTPUT->action_icon($url, $icons['movedown'], null, $pixattr);
        }
        $editcell = implode($divider, $commands);
            
        $table->data[] = array('c'.($i+1), $config->name, $config->summary, $editcell);
        $i++;
    }
}

/* Display page */

echo $OUTPUT->header();

$currenttab = $comp;
include('tabs.php');

if ($helpicon = $compclass->get_help_icon()) {
    echo $OUTPUT->box(html_writer::tag('p', $helpicon, array('class' => 'centerpara')), 'boxaligncenter');
}

if($elements){
	cr_print_table($table);
} else if($compclass->plugins) {
	echo $OUTPUT->heading(get_string('no'.$comp.'yet', 'block_configurable_reports'));
}

if($pluginoptions = $compclass->get_plugin_options()){
    $plugurls = array();
    foreach($pluginoptions as $pname => $option){
        $plugurls[$editurl->out(true, array('pname' => $pname))] = $option;
    }
    $selector = get_string('add').' '.$OUTPUT->render(new url_select($plugurls));
    
    echo $OUTPUT->box(html_writer::tag('p', $selector, array('class' => 'centerpara')), 'boxaligncenter');
}

if($compclass->has_form()){
	$editform->display();
}

echo $OUTPUT->footer();

?>