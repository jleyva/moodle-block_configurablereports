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

class component_template extends component_base{

	function has_form(){
	    return true;
	}
	
	function print_report($reportclass){
	    global $OUTPUT;
	    
	    $page = optional_param('page', 0, PARAM_INT);
	    
	    $page_contents = array();
	    $page_contents['header'] = (isset($config->header) && $config->header)? $config->header : '';
	    $page_contents['footer'] = (isset($config->footer) && $config->footer)? $config->footer : '';
	    
	    $report = $reportclass->config;
	    $recordtpl = (isset($config->record) && $config->record)? $config->record : '';;
	    
	    $calculations = '';
	    
	    if(!empty($reportclass->finalreport->calcs->data[0])){
	        $calculations = print_table($reportclass->finalreport->calcs, true);
	    }
	    	
	    $pagination = '';
	    if($reportclass->config->pagination){
	        $params = array('id' => $report->id);
	        $request = array_merge($_POST, $_GET);
            foreach($request as $key => $val){
	            if(strpos($key,'filter_') !== false){
    	            if(is_array($val)){
    	                foreach($val as $k => $v){
    	                    $params[$key][$k] = $v;    //TODO: moodle_url doesn't accept array params
    	                }
    	            } else {
    	                $params[$key] = $val;
    	            }
	            }
            }
	    
	        $reportclass->totalrecords = count($reportclass->finalreport->table->data);
	        $baseurl = new moodle_url('viewreport.php', $params);
	        $pagingbar = new paging_bar($reportclass->totalrecords, $page, $reportclass->config->pagination, $baseurl, 'page');
	        $pagination =  $OUTPUT->render($pagingbar);
	    }
	    
	    /* Add report elements */
	    $search = array(
	            '##reportname##',
	            '##reportsummary##',
	            '##graphs##',
	            '##exportoptions##',
	            '##calculationstable##',
	            '##pagination##'
	    );
	    $plotclass = $this->get_component('plot');
	    $exportclass = $this->get_component('export');
	    $replace = array(
	        format_string($reportclass->config->name),
	        format_text($reportclass->config->summary),
	        $plotclass->print_to_report(true),
	        $exportclass->print_to_report(true),
	        $calculations,
	        $pagination
        );
	    foreach($page_contents as $key=>$p){
	        if($p){
	            $page_contents[$key] = str_ireplace($search, $replace, $p);
	        }
	    }
	    
	    /* Print report */
	    $reportclass->print_filters();
	    
	    //TODO: Need to actually have a table somewhere
	    $PAGE->requires->js_init_call('M.block_configurable_reports.setupTable', array('reporttable'));
	    
	    echo html_writer::start_tag('div', array('id' => 'printablediv'));
	    echo format_text($page_contents['header'], FORMAT_HTML);
	    
	    if($recordtpl){
	        if($reportclass->config->pagination){
	            $rowsperpage = $reportclass->config->pagination;
	            $reportclass->totalrecords = count($reportclass->finalreport->table->data);
	            $reportclass->finalreport->table->data = array_slice($reportclass->finalreport->table->data, $page * $rowsperpage, $rowsperpage);
	        }
	        	
	        foreach($reportclass->finalreport->table->data as $r){
	            $recordtext = $recordtpl;
	            foreach($reportclass->finalreport->table->head as $key=>$c){
	                $recordtext = str_ireplace("[[$c]]",$r[$key],$recordtext);
	            }
	            echo format_text($recordtext, FORMAT_HTML);
	        }
	    }
	   
	    echo format_text($page_contents['footer'], FORMAT_HTML);
	    echo html_writer::end_tag('div');
	    
	    $this->report->print_report_link();
	}
}

?>