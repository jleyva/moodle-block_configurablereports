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

$id   = required_param('id', PARAM_INT);        // Report id
$comp = required_param('comp', PARAM_ALPHA);    // Component name

if (! ($report = $DB->get_record('block_configurable_reports_report', array('id' => $id)))) {
	print_error('reportdoesnotexists');
}
$courseid = $report->courseid;
if (isset($courseid)) {
    if (! ($course = $DB->get_record("course", array( "id" =>  $courseid)))) {
        print_error('invalidcourseid');
    }

    require_login($courseid);
    $context = context_course::instance($courseid);
} else {
    require_login();
    $context = context_system::instance();
}
// Capability check
if($report->ownerid != $USER->id){
    require_capability('block/configurable_reports:managereports', $context);
}else{
    require_capability('block/configurable_reports:manageownreports', $context);
}

$params = array('id' => $id, 'comp' => $comp);
$baseurl = new moodle_url('/blocks/configurable_reports/editcomp.php');
$manageurl = new moodle_url('/blocks/configurable_reports/managereport.php', array('courseid' => $courseid));
$addurl = new moodle_url('/blocks/configurable_reports/addplugin.php', $params);
$editurl = new moodle_url('/blocks/configurable_reports/editplugin.php', array('comp' => $comp));
$PAGE->set_url($baseurl, $params);
$PAGE->set_context($context);	
$PAGE->set_pagelayout('incourse');
	
$reportclass = report_base::get($report);
$compclass = $reportclass->get_component($comp);
if (!isset($compclass)) {
    print_error('badcomponent');
}

$title = format_string($reportclass->config->name).' '.$compclass->get_name();    //TODO: Display names
navigation_node::override_active_url($manageurl);
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

if($compclass->has_form()){
    $editform = $compclass->get_form($PAGE->url);
	
	if ($editform->is_cancelled()) {
		redirect(new moodle_url('/blocks/configurable_reports/editreport.php', array('id' => $id)));
		
	} else if ($data = $editform->get_data()) {
	    $editform->save_data($data);
	    $logcourse = isset($courseid) ? $courseid : $SITE->id; 
		add_to_log($logcourse, 'configurable_reports', 'edit', '', $report->name);
	}
	
	$editform->set_data();
}

$instances = $compclass->get_all_instances();

if (!empty($instances)) {
    $table = new html_table();
    $table->width = '80%';
    $table->tablealign = 'center';
    $table->head = array(
            get_string('idnumber'),
            get_string('name'), 
            get_string('summary'), 
            get_string('edit')
    );
    
    $icons = array(
            'edit'      => new pix_icon('t/edit', get_string('edit')),
            'delete'    => new pix_icon('t/delete', get_string('delete')),
            'moveup'    => new pix_icon('t/up', get_string('hide')),
            'movedown'  => new pix_icon('t/down', get_string('show')),
    );
    $divider = '&nbsp;&nbsp;';
    $pixattr = array('class'=>'iconsmall');
    
    $i = 0;
    $numinstances = count($instances);
    $plugins = $compclass->get_plugins();
    foreach($instances as $sortorder => $instance){
        if (!$compclass->has_plugin($instance->plugin)) {
            continue;    //Just in case dependency change TODO: throw Exception
        }
        $editurl->params(array('id' => $instance->id));
        $pluginclass = $plugins[$instance->plugin];
    
        $commands = array();
        if($pluginclass->has_form()){
            $commands[] = $OUTPUT->action_icon($editurl, $icons['edit'], null, $pixattr);
        }
        $url = clone($editurl);
        $url->params(array('delete' => 1, 'sesskey' => $USER->sesskey));
        $commands[] = $OUTPUT->action_icon($url, $icons['delete'], null, $pixattr);
        
        if ($compclass->has_ordering()) {
            if ($i != 0 && $numinstances > 1) {
                $url = clone($editurl);
                $url->params(array('moveup' => 1, 'sesskey' => $USER->sesskey));
                $commands[] = $OUTPUT->action_icon($url, $icons['moveup'], null, $pixattr);
            }
            if ($i != $numinstances -1) {
                $url = clone($editurl);
                $url->params(array('movedown' => 1, 'sesskey' => $USER->sesskey));
                $commands[] = $OUTPUT->action_icon($url, $icons['movedown'], null, $pixattr);
            }
        }
        $editcell = implode($divider, $commands);
        
        $table->data[] = array('c'.($i+1), $instance->name, $instance->summary, $editcell);
        $i++;
    }
}

/* Display page */

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('report_'.$report->type, 'block_configurable_reports'));

cr_print_tabs($reportclass, $comp);

if ($helpicon = $compclass->get_help_icon()) {
    echo $OUTPUT->box(html_writer::tag('p', $helpicon, array('class' => 'centerpara')), 'boxaligncenter');
}

if (!empty($instances)) {
	echo html_writer::table($table);
} else if ($compclass->plugins) {
	echo $OUTPUT->heading(get_string('no'.$comp.'yet', 'block_configurable_reports'));
}

if ($pluginoptions = $compclass->get_plugin_options()) {
    $plugurls = array();
    foreach($pluginoptions as $plugin => $option){
        $plugurls[$addurl->out(false, array('plug' => $plugin))] = $option;
    }
    $selector = new url_select($plugurls);
    $selector->class = 'boxaligncenter centerpara';
    $selector->set_label(get_string('add'));
    echo $OUTPUT->render($selector);
}

if ($compclass->has_form()) {
	$editform->display();
}

echo $OUTPUT->footer();

?>