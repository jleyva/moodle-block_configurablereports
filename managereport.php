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
require_once($CFG->dirroot."/blocks/configurable_reports/import_form.php");

$courseid = optional_param('courseid', null, PARAM_INT);

$params = array();
if (isset($courseid)) {
    if (! ($course = $DB->get_record("course", array( "id" =>  $courseid)))) {
    	print_error("nosuchcourseid", 'block_configurable_reports');
    }
    $params['courseid'] = $courseid;
    
    require_login($courseid);
    $context = context_course::instance($courseid);
} else {
    require_login();
    $context = context_system::instance();
}
// Capability check	
if(!has_capability('block/configurable_reports:managereports', $context) && 
        !has_capability('block/configurable_reports:manageownreports', $context)){
	print_error('badpermissions');
}

$baseurl = new moodle_url('/blocks/configurable_reports/managereport.php');
$addurl  = new moodle_url('/blocks/configurable_reports/addreport.php');
$editurl = new moodle_url('/blocks/configurable_reports/editreport.php');
$viewurl = new moodle_url('/blocks/configurable_reports/viewreport.php');
$exporturl = new moodle_url('blocks/configurable_reports/export.php');
$courseurl = new moodle_url('/course/view.php');
$userurl = new moodle_url('/user/view.php');
$PAGE->set_url($baseurl, $params);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
	
$importform = new import_form(null, $courseid);
if (($data = $importform->get_data()) && ($xml = $importform->get_file_content('userfile'))) {
	require_once($CFG->dirroot.'/lib/xmlize.php');
	$data = xmlize($xml, 1, 'UTF-8');
	
	if(isset($data['report']['@']['version'])){
		$newreport = new stdclass;
		foreach($data['report']['#'] as $key=>$val){
			if ($key == 'components') {
				$val[0]['#'] = base64_decode(trim($val[0]['#']));
			}
			$newreport->{$key} = trim($val[0]['#']);
		}
		$newreport->courseid = $course->id;
		$newreport->ownerid = $USER->id;
		$DB->insert_record('block_configurable_reports_report', $newreport);

		redirect($PAGE->url);
	}
}	

if($reports = cr_get_my_reports($USER->id, $context)){
    $sitestr = get_string('site');
    $delstr = get_string('deleted');
    
    $table = new html_table();
    $table->id = 'reportslist';
    $table->head = array(
            get_string('name'),
            get_string('course'),
            get_string('type', 'block_configurable_reports'),
            get_string('username'),
            get_string('edit'),
            get_string('download', 'block_configurable_reports')
    );
    $table->align = array('left','left','left','left','center','center');
 
    $icons = array(
        'edit'       => new pix_icon('t/edit', get_string('edit')),
        'delete'     => new pix_icon('t/delete', get_string('delete')),
        'hide'       => new pix_icon('t/hide', get_string('hide')),
        'show'       => new pix_icon('t/show', get_string('show')),
        'duplicate'  => new pix_icon('t/copy', get_string('duplicate')),
        'export'     => new pix_icon('i/backup', get_string('exportreport','block_configurable_reports'))
    );
    $divider = '&nbsp;&nbsp;';
    $pixattr = array('class'=>'iconsmall');
    
    foreach($reports as $r){
        $editurl->params(array('id' => $r->id, 'sesskey' => $USER->sesskey));
        $exporturl->param('id', $r->id);
        $viewurl->param('id', $r->id);
        
        $reportname = html_writer::tag('a', $r->name, array('href' => $viewurl));
        $reporttype = get_string('report_'.$r->type, 'block_configurable_reports');
        
        if(!isset($r->courseid)) {
            $coursename = html_writer::tag('a', $sitestr, array('href' => $CFG->wwwroot));
        } else if (! ($coursename = $DB->get_field('course', 'fullname', array('id' => $r->courseid)))) {
            $coursename = $delstr;
        } else {
            $url = $courseurl->out(true, array('courseid' => $r->courseid));
            $coursename = html_writer::tag('a', $coursename, array('href' => $url));
        }
        
        if($owneruser = $DB->get_record('user', array('id' => $r->ownerid))){
            $url = $userurl->out(true, array('id' => $r->ownerid));
            $owner = html_writer::tag('a', fullname($owneruser), array('href' => $url));
        } else {
            $owner = $delstr;
        }

        $commands = array();
        $commands[] = $OUTPUT->action_icon($editurl, $icons['edit'], null, $pixattr);
        $url = clone($editurl);
        $url->param('delete', 1);
        $commands[] = $OUTPUT->action_icon($url, $icons['delete'], null, $pixattr);
        if (!empty($r->visible)) {
            $url = clone($editurl);
            $url->param('hide', 1);
            $commands[] = $OUTPUT->action_icon($url, $icons['hide'], null, $pixattr);
        } else {
            $url = clone($editurl);
            $url->param('show', 1);
            $commands[] = $OUTPUT->action_icon($url, $icons['show'], null, $pixattr);
        }
        $url = clone($editurl);
        $url->param('duplicate', 1);
        $commands[] = $OUTPUT->action_icon($url, $icons['duplicate'], null, $pixattr);
        $commands[] = $OUTPUT->action_icon($exporturl, $icons['export'], null, $pixattr);
        $editcell = implode($divider, $commands);

        $download = '';
        if(!empty($r->export)){
            foreach (explode(',', $r->export) as $e) {
                $url = clone($viewurl);
                $url->params(array('download' => 1, 'format' => $e));
                $icon = '<img src="'.$CFG->wwwroot.'/blocks/configurable_reports/export/'.$e.'/pix.gif">';
                $download .= html_writer::tag('a', $icon.'&nbsp;'.strtoupper($e), array('href' => $url));
                $download .= $divider;
            }
        }

        $table->data[] = array($reportname, $coursename, $reporttype, $owner, $editcell, $download);
    }
}

$title = get_string('reports','block_configurable_reports');
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

/* Display page */

echo $OUTPUT->header();

if ($reports) {
    cr_add_jsordering("#reportslist");
    cr_print_table($table);
    //echo html_writer::table($table);
} else {
    echo $OUTPUT->heading(get_string('noreportsavailable', 'block_configurable_reports'));
}

$typeoptions = cr_get_report_plugins($courseid);
if (!has_capability('block/configurable_reports:managesqlreports', $context)) {
    unset($typeoptions['sql']);    //TODO: Make more general with specific capabilities (subplugins)
}
$typeurls = array();
foreach($typeoptions as $type => $typename){
    $typeurls[$addurl->out(false, array('type' => $type))] = $typename;
}
$selector = new url_select($typeurls);
$selector->class = 'boxaligncenter centerpara';
$selector->set_label(get_string('add'));
echo $OUTPUT->render($selector);

$importform->display();
			
echo $OUTPUT->footer();

?>