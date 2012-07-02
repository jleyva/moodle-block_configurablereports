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
	require_once 'import_form.php';
	
	$courseid = optional_param('courseid',SITEID,PARAM_INT);
	
	if (! $course = $DB->get_record("course",array( "id" =>  $courseid)) ) {
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
	
	$PAGE->set_url('/blocks/configurable_reports/managereport.php', array('courseid'=>$course->id));
	$PAGE->set_context($context);
	$PAGE->set_pagelayout('incourse');
		
	$mform = new import_form(null,$course->id);

	if ($data = $mform->get_data()) {
		if ($xml = $mform->get_file_content('userfile')) {
			require_once($CFG->dirroot.'/lib/xmlize.php');
			$data = xmlize($xml, 1, 'UTF-8');
			
			if(isset($data['report']['@']['version'])){
				$newreport = new stdclass;
				foreach($data['report']['#'] as $key=>$val){
					if($key == 'components')
						$val[0]['#'] = base64_decode(trim($val[0]['#']));
					$newreport->{$key} = trim($val[0]['#']);
				}
				$newreport->courseid = $course->id;
				$newreport->ownerid = $USER->id;
				if(!$DB->insert_record('block_configurable_reports',$newreport))
					print_error('errorimporting');
				header("Location: $CFG->wwwroot/blocks/configurable_reports/managereport.php?courseid={$course->id}");
				die;
			}
		}
	}	
	
	$reports = cr_get_my_reports($course->id, $USER->id);
	
	$title = get_string('reports','block_configurable_reports');
	$navlinks = array();
	$navlinks[] = array('name' => $title, 'link' => null, 'type' => 'title');
	$navigation = build_navigation($navlinks);

	$PAGE->set_title($title);
	$PAGE->set_heading( $title);
	$PAGE->set_cacheable( true);
	echo $OUTPUT->header();
			
	if($reports){
		$table = new stdclass;
		$table->head = array(get_string('name'),get_string('course'),get_string('type','block_configurable_reports'),get_string('username'),get_string('edit'),get_string('download','block_configurable_reports'));
		$table->align = array('left','left','left','left','center','center');
		$stredit = get_string('edit');
		$strdelete = get_string('delete');
		$strhide = get_string('hide');
		$strshow = get_string('show');
		$strcopy = get_string('duplicate');
		$strexport = get_string('exportreport','block_configurable_reports');
		
		foreach($reports as $r){
			
			if($r->courseid == 1)
				$coursename = '<a href="'.$CFG->wwwroot.'">'.get_string('site').'</a>';
			else if(! $coursename = $DB->get_field('course','fullname',array('id' => $r->courseid)))
				$coursename = get_string('deleted');
			else
				$coursename = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$r->courseid.'">'.$coursename.'</a>';
				
			if($owneruser = $DB->get_record('user',array('id' => $r->ownerid)))
				$owner = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$r->ownerid.'">'.fullname($owneruser).'</a>';
			else
				$owner = get_string('deleted');
			
			$editcell = '';
			$editcell .= '<a title="'.$stredit.'"  href="editreport.php?id='.$r->id.'"><img src="'.$OUTPUT->pix_url('/t/edit').'" class="iconsmall" alt="'.$stredit.'" /></a>&nbsp;&nbsp;';
			$editcell .= '<a title="'.$strdelete.'"  href="editreport.php?id='.$r->id.'&amp;delete=1&amp;sesskey='.$USER->sesskey.'"><img src="'.$OUTPUT->pix_url('/t/delete').'" class="iconsmall" alt="'.$strdelete.'" /></a>&nbsp;&nbsp;';
			
			
			if (!empty($r->visible)) {
				$editcell .= '<a title="'.$strhide.'" href="editreport.php?id='.$r->id.'&amp;hide=1&amp;sesskey='.$USER->sesskey.'">'.'<img src="'.$OUTPUT->pix_url('/t/hide').'" class="iconsmall" alt="'.$strhide.'" /></a> ';}
			else {
				$editcell .= '<a title="'.$strshow.'" href="editreport.php?id='.$r->id.'&amp;show=1&amp;sesskey='.$USER->sesskey.'">'.'<img src="'.$OUTPUT->pix_url('/t/show').'" class="iconsmall" alt="'.$strshow.'" /></a> ';
			}
			$editcell .= '<a title="'.$strcopy.'" href="editreport.php?id='.$r->id.'&amp;duplicate=1&amp;sesskey='.$USER->sesskey.'"><img src="'.$OUTPUT->pix_url('/t/copy').'" class="iconsmall" alt="'.$strcopy.'" /></a>&nbsp;&nbsp;';
			$editcell .= '<a title="'.$strexport.'" href="export.php?id='.$r->id.'&amp;sesskey='.$USER->sesskey.'"><img src="'.$OUTPUT->pix_url('/i/backup').'" class="iconsmall" alt="'.$strexport.'" /></a>&nbsp;&nbsp;';
			
			$download = '';
			$export = explode(',',$r->export);
			if(!empty($export)){				
				foreach($export as $e)
					if($e){
						$download .= '<a href="viewreport.php?id='.$r->id.'&amp;download=1&amp;format='.$e.'"><img src="'.$CFG->wwwroot.'/blocks/configurable_reports/export/'.$e.'/pix.gif" alt="'.$e.'">&nbsp;'.(strtoupper($e)).'</a>&nbsp;&nbsp;';
					}				
			}
			
			$table->data[] = array('<a href="viewreport.php?id='.$r->id.'">'.$r->name.'</a>',$coursename,get_string('report_'.$r->type,'block_configurable_reports'), $owner, $editcell, $download);
		}
		
		$table->id = 'reportslist';
		cr_add_jsordering("#reportslist");
		cr_print_table($table);
	}
	else{
		echo $OUTPUT->heading(get_string('noreportsavailable','block_configurable_reports'));
	}
	
	echo $OUTPUT->heading('<a href="'.$CFG->wwwroot.'/blocks/configurable_reports/editreport.php?courseid='.$course->id.'">'.(get_string('addreport','block_configurable_reports')).'</a>');
	
	$mform->display();
				
    echo $OUTPUT->footer();

?>