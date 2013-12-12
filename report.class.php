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
 require_once($CFG->dirroot.'/lib/evalmath/evalmath.class.php');

 class report_base {

	var $id = 0;
	var $components = array();
	var $finalreport;
	var $totalrecords = 0;
	var $currentuser = 0;
	var $currentcourse = 0;
	var $starttime = 0;
	var $endtime = 0;
    var $sql = '';

	function reports_base($report){
		global $DB, $CFG, $USER;

		if(is_numeric($report))
			$this->config = $DB->get_record('block_configurable_reports',array('id' => $report));
		else
			$this->config = $report;

		$this->currentuser = $USER;
		$this->currentcourseid = $this->config->courseid;
		$this->init();
	}

	function __construct($report) {
		$this->reports_base($report);
	}

	function check_permissions($userid, $context){
		global $DB, $CFG, $USER;

		if(has_capability('block/configurable_reports:manageownreports', $context, $userid) && $this->config->ownerid == $userid)
			return true;

		if(has_capability('block/configurable_reports:managereports', $context, $userid))
			return true;

		if(empty($this->config->visible))
			return false;

		$components = cr_unserialize($this->config->components);
		$permissions = (isset($components['permissions']))? $components['permissions'] : array();

		if(empty($permissions['elements'])){
			return has_capability('block/configurable_reports:viewreports', $context);
		} else {
			$i = 1;
			$cond = array();
			foreach($permissions['elements'] as $p){
				require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');
				require_once($CFG->dirroot.'/blocks/configurable_reports/components/permissions/'.$p['pluginname'].'/plugin.class.php');
				$classname = 'plugin_'.$p['pluginname'];
				$class = new $classname($this->config);
				$cond[$i] = $class->execute($userid, $context, $p['formdata']);
				$i++;
			}
			if (count($cond) == 1) {
                return $cond[1];
            } else {
				$m = new EvalMath;
				$orig = $dest = array();

				if(isset($permissions['config']) && isset($permissions['config']->conditionexpr)){
					$logic = trim($permissions['config']->conditionexpr);
					// Security
					// No more than: conditions * 10 chars
					$logic = substr($logic,0,count($permissions['elements']) * 10);
					$logic = str_replace(array('and','or'),array('&&','||'),strtolower($logic));
					// More Security Only allowed chars
					$logic = preg_replace('/[^&c\d\s|()]/i','',$logic);
					//$logic = str_replace('c','$c',$logic);
					$logic = str_replace(array('&&','||'),array('*','+'),$logic);

					for($j = $i -1; $j > 0; $j--){
						$orig[] = 'c'.$j;
						$dest[] = ($cond[$j])? 1 : 0;
					}

					return $m->evaluate(str_replace($orig,$dest,$logic));
				} else {
					return false;
				}
			}
		}
	}

	function add_filter_elements(&$mform){
		global $DB, $CFG;

		$components = cr_unserialize($this->config->components);
		$filters = (isset($components['filters']['elements']))? $components['filters']['elements']: array();

		require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');
		foreach($filters as $f){
			require_once($CFG->dirroot.'/blocks/configurable_reports/components/filters/'.$f['pluginname'].'/plugin.class.php');
			$classname = 'plugin_'.$f['pluginname'];
			$class = new $classname($this->config);

			$finalelements = $class->print_filter($mform, $f['formdata']);

		}
	}

	var $filterform = null;
	function check_filters_request(){
		global $DB, $CFG;

		$components = cr_unserialize($this->config->components);
		$filters = (isset($components['filters']['elements']))? $components['filters']['elements']: array();

		if(!empty($filters)){

			$formdata = new stdclass;
			$request = array_merge($_POST, $_GET);
			if($request)
				foreach($request as $key=>$val)
					if(strpos($key,'filter_') !== false)
						$formdata->{$key} = $val;

			require_once('filter_form.php');
			$filterform = new report_edit_form(null,$this);

			$filterform->set_data($formdata);

			if($filterform->is_cancelled()){
				redirect("$CFG->wwwroot/blocks/configurable_reports/viewreport.php?id=".$this->config->id."&courseid=".$this->config->courseid);
				die;
			}
			$this->filterform = $filterform;
		}
	}

	function print_filters(){
		if(!is_null($this->filterform)) {
			$this->filterform->display();
		}
	}

	function print_graphs($return = false){
		$output = '';
		$graphs = $this->get_graphs($this->finalreport->table->data);

		if($graphs){
			foreach($graphs as $g){
				$output .= '<div class="centerpara">';
				$output .= ' <img src="'.$g.'" alt="'.$this->config->name.'"><br />';
				$output .= '</div>';
			}
		}
		if($return){
			return $output;
		}

		echo $output;
		return true;
	}


	function print_export_options($return = false){
		global $CFG;

		$wwwpath = $CFG->wwwroot;
		$request = array_merge($_POST,$_GET);
		if($request){
			$wwwpath = 'viewreport.php?id='.$request['id'];
			unset($request['id']);
			foreach($request as $key=>$val){
				if(is_array($val)){
					foreach($val as $k=>$v)
						$wwwpath .= "&amp;{$key}[$k]=".$v;
				}
				else{
					$wwwpath .= "&amp;$key=".$val;
				}
			}
		}

		$output = '';
		$export = explode(',',$this->config->export);
		if(!empty($this->config->export)){
			$output .= '<br /><div class="centerpara">';
			$output .= get_string('downloadreport','block_configurable_reports').': ';
			foreach($export as $e)
				if($e){
					$output .= '<a href="'.$wwwpath.'&amp;download=1&amp;format='.$e.'"><img src="'.$CFG->wwwroot.'/blocks/configurable_reports/export/'.$e.'/pix.gif" alt="'.$e.'">&nbsp;'.(strtoupper($e)).'</a>&nbsp;';
				}
			$output .= '</div>';
		}

		if($return){
			return $output;
		}

		echo $output;
		return true;
	}

	function evaluate_conditions($data,$logic){
		global $DB, $CFG;

		require_once($CFG->dirroot.'/blocks/configurable_reports/reports/evalwise.class.php');

		$logic = trim(strtolower($logic));
		$logic = substr($logic,0,count($data) * 10);
		$logic = str_replace(array('or','and','not'),array('+','*','-'),$logic);
		$logic = preg_replace('/[^\*c\d\s\+\-()]/i','',$logic);

		$orig = $dest = array();
		for($j = count($data); $j > 0; $j--){
			$orig[] = 'c'.$j;
			$dest[] = $j;
		}
		$logic = str_replace($orig,$dest,$logic);

		$m = new EvalWise();

		$m->set_data($data);
		$result = $m->evaluate($logic);
		return $result;
	}

	function get_graphs($finalreport){
		global $DB, $CFG;

		$components = cr_unserialize($this->config->components);
		$graphs = (isset($components['plot']['elements']))? $components['plot']['elements'] : array();

		$reportgraphs = array();

		if(!empty($graphs)){
			$series = array();
			foreach($graphs as $g){
				require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/'.$g['pluginname'].'/plugin.class.php');
				$classname = 'plugin_'.$g['pluginname'];
				$class = new $classname($this->config);
				$reportgraphs[] = $class->execute($g['id'],$g['formdata'],$finalreport);
			}
		}
		return $reportgraphs;
	}

	function get_calcs($finaltable, $tablehead){
		global $DB, $CFG;

		$components = cr_unserialize($this->config->components);
		$calcs = (isset($components['calcs']['elements']))? $components['calcs']['elements'] : array();

		// Calcs doesn't work with multi-rows so far
		$columnscalcs = array();
		$finalcalcs = array();
		if(!empty($calcs)){
			foreach($calcs as $calc){
				$columnscalcs[$calc['formdata']->column] = array();
			}

			$columnstostore = array_keys($columnscalcs);

			foreach($finaltable as $r){
				foreach($columnstostore as $c){
					if(isset($r[$c]))
						$columnscalcs[$c][] = $r[$c];
				}
			}

			foreach($calcs as $calc){
				require_once($CFG->dirroot.'/blocks/configurable_reports/components/calcs/'.$calc['pluginname'].'/plugin.class.php');
				$classname = 'plugin_'.$calc['pluginname'];
				$class = new $classname($this->config);
				$result = $class->execute($columnscalcs[$calc['formdata']->column]);
				$finalcalcs[$calc['formdata']->column] = $result;
			}

			for($i=0;$i<count($tablehead);$i++){
				if(!isset($finalcalcs[$i]))
					$finalcalcs[$i] = '';
			}

			ksort($finalcalcs);

		}
		return $finalcalcs;
	}

	function elements_by_conditions($conditions){
		global $DB, $CFG;

		if(empty($conditions['elements'])){
			$finalelements = $this->get_all_elements();
			return $finalelements;
		}

		$finalelements = array();
		$i = 1;
		foreach($conditions['elements'] as $c){
			require_once($CFG->dirroot.'/blocks/configurable_reports/components/conditions/'.$c['pluginname'].'/plugin.class.php');
			$classname = 'plugin_'.$c['pluginname'];
			$class = new $classname($this->config);
			$elements[$i] = $class->execute($c['formdata'],$this->currentuser,$this->currentcourseid);
			$i++;
		}


		if(count($conditions['elements']) == 1){
			$finalelements = $elements[1];
		}else{
			$logic = $conditions['config']->conditionexpr;

			$finalelements = $this->evaluate_conditions($elements,$logic);

			if($finalelements === FALSE)
				return false;
		}

		return $finalelements;
	}

	// Returns a report object
	function create_report(){
		global $DB, $CFG;

		//
		// CONDITIONS
		//

		$components = cr_unserialize($this->config->components);

		$conditions = (isset($components['conditions']['elements']))? $components['conditions']['elements'] : array();
		$filters = (isset($components['filters']['elements']))? $components['filters']['elements'] : array();
		$columns = (isset($components['columns']['elements']))? $components['columns']['elements'] : array();
		$ordering = (isset($components['ordering']['elements']))? $components['ordering']['elements'] : array();

		$finalelements = array();

		if(!empty($conditions)){
			$finalelements = $this->elements_by_conditions($components['conditions']);
		}
		else{
			// All elements
			$finalelements = $this->get_all_elements();
		}



		//
		// FILTERS
		//

        if(!empty($filters)){
			foreach($filters as $f){
				require_once($CFG->dirroot.'/blocks/configurable_reports/components/filters/'.$f['pluginname'].'/plugin.class.php');
				$classname = 'plugin_'.$f['pluginname'];
				$class = new $classname($this->config);
				$finalelements = $class->execute($finalelements,$f['formdata']);
			}
		}

		//
		// ORDERING
		//

		$sqlorder = '';

		$orderingdata = array();
		if(!empty($ordering)){
			foreach($ordering as $o){
				require_once($CFG->dirroot.'/blocks/configurable_reports/components/ordering/'.$o['pluginname'].'/plugin.class.php');
				$classname = 'plugin_'.$o['pluginname'];
				$classorder = new $classname($this->config);
				$orderingdata = $o['formdata'];
				if($classorder->sql)
					$sqlorder = $classorder->execute($orderingdata);
			}
		}

		//
		// COLUMNS - FIELDS
		//

		$rows = $this->get_rows($finalelements,$sqlorder);

		if(!$sqlorder && isset($classorder)){
			$rows = $classorder->execute($rows,$orderingdata);
		}

		$reporttable = array();
		$tablehead = array();
		$tablealign =array();
		$tablesize = array();
		$tablewrap = array();
		$firstrow = true;

		$pluginscache = array();

		if($rows){
			foreach($rows as $r){
				$tempcols = array();
				foreach($columns as $c){
					require_once($CFG->dirroot.'/blocks/configurable_reports/components/columns/'.$c['pluginname'].'/plugin.class.php');
					$classname = 'plugin_'.$c['pluginname'];
					if(!isset($pluginscache[$classname])){
						$class = new $classname($this->config,$c);
						$pluginscache[$classname] = $class;
					}
					else{
						$class = $pluginscache[$classname];
					}

					$tempcols[] = $class->execute($c['formdata'],$r,$this->currentuser,$this->currentcourseid,$this->starttime, $this->endtime);
					if($firstrow){
						$tablehead[] = $class->summary($c['formdata']);
						list($align,$size,$wrap) = $class->colformat($c['formdata']);
						$tablealign[] = $align;
						$tablesize[] = $size;
						$tablewrap[] = $wrap;
					}

				}
				$firstrow = false;
				$reporttable[] = $tempcols;
			}
		}

		//
		// EXPAND ROWS
		//
		$finaltable = array();
		$newcols = array();

		foreach($reporttable as $row){
			$col = array();
			$multiple = false;
			$nrows = 0;
			$mrowsi = array();

			foreach($row as $key=>$cell){
				if(!is_array($cell)){
					$col[] = $cell;
				}
				else{
					$multiple = true;
					$nrows = count($cell);
					$mrowsi[] = $key;
				}
			}
			if($multiple){
				$newrows = array();
				for($i=0;$i<$nrows;$i++){
					$newrows[$i] = $row;
					foreach($mrowsi as $index){
						$newrows[$i][$index] = $row[$index][$i];
					}
				}
				foreach($newrows as $r)
					$finaltable[] = $r;
			}
			else{
				$finaltable[] = $col;
			}
		}

		//
		// CALCS
		//

		$finalcalcs = $this->get_calcs($finaltable,$tablehead);


		// Make the table, head, columns, etc...

		$table = new stdClass;
		$table->id = 'reporttable';
		$table->data = $finaltable;
		$table->head = $tablehead;
		$table->size = $tablesize;
		$table->align = $tablealign;
		$table->wrap = $tablewrap;
		$table->width = (isset($components['columns']['config']))? $components['columns']['config']->tablewidth : '';
		$table->summary = $this->config->summary;
		$table->tablealign = (isset($components['columns']['config']))? $components['columns']['config']->tablealign : 'center';
		$table->cellpadding = (isset($components['columns']['config']))? $components['columns']['config']->cellpadding : '5';
		$table->cellspacing = (isset($components['columns']['config']))? $components['columns']['config']->cellspacing : '1';
		$table->class = (isset($components['columns']['config']))? $components['columns']['config']->class : 'generaltable';

		$calcs = new html_table();
		$calcs->data = array($finalcalcs);
		$calcs->head = $tablehead;
		$calcs->size = $tablesize;
		$calcs->align = $tablealign;
		$calcs->wrap = $tablewrap;
        $calcs->summary = $this->config->summary;
        // depricated and should be handled in CSS, since Moodle 2.0
		//$calcs->width = (isset($components['columns']['config']))? $components['columns']['config']->tablewidth : '';
		//$calcs->tablealign = (isset($components['columns']['config']))? $components['columns']['config']->tablealign : 'center';
		//$calcs->cellpadding = (isset($components['columns']['config']))? $components['columns']['config']->cellpadding : '5';
		//$calcs->cellspacing = (isset($components['columns']['config']))? $components['columns']['config']->cellspacing : '1';
		//$calcs->class = (isset($components['columns']['config']))? $components['columns']['config']->class : 'generaltable';
        $calcs->attributes['class'] = (isset($components['columns']['config']))? $components['columns']['config']->class : 'generaltable';

		if(!$this->finalreport) {
			$this->finalreport = new stdClass;
		}
		$this->finalreport->table = $table;
		$this->finalreport->calcs = $calcs;

		return true;

	}

	function add_jsordering(){
        switch (get_config('block_configurable_reports', 'reporttableui')) {
            case 'datatables':
                cr_add_jsdatatables('#reporttable');
                break;
            case 'jquery':
                cr_add_jsordering('#reporttable');
                echo html_writer::tag('style',
                    '#page-blocks-configurable_reports-viewreport .generaltable {
                    overflow: auto;
                    width: 100%;
                    display: block;}');
                break;
            case 'html':
                echo html_writer::tag('style',
                    '#page-blocks-configurable_reports-viewreport .generaltable {
                    overflow: auto;
                    width: 100%;
                    display: block;}');
                break;
            default: break;
        }

	}

	function print_template($config){
		global $DB, $CFG, $OUTPUT;

		$page_contents = array();
		$page_contents['header'] = (isset($config->header) && $config->header)? $config->header : '';
		$page_contents['footer'] = (isset($config->footer) && $config->footer)? $config->footer : '';

		$recordtpl = (isset($config->record) && $config->record)? $config->record : '';;

		$calculations = '';

		if(!empty($this->finalreport->calcs->data[0])){
			$calculations = html_writer::table($this->finalreport->calcs);
		}

		$pagination = '';
		if($this->config->pagination){
			$page = optional_param('page',0,PARAM_INT);
			$postfiltervars = '';
			$request = array_merge($_POST,$_GET);
			if($request)
				foreach($request as $key=>$val)
					if(strpos($key,'filter_') !== false){
						if(is_array($val)){
							foreach($val as $k=>$v)
								$postfiltervars .= "&amp;{$key}[$k]=".$v;
						}
						else{
							$postfiltervars .= "&amp;$key=".$val;
						}
					}

			$this->totalrecords = count($this->finalreport->table->data);
			//$pagination = print_paging_bar($this->totalrecords,$page,$this->config->pagination,"viewreport.php?id=".$this->config->id."$postfiltervars&amp;",'page',false,true);
			$pagingbar = new paging_bar($this->totalrecords, $page, $this->config->pagination, "viewreport.php?id=".$this->config->id."&courseid=".$this->config->courseid."$postfiltervars&amp;");
			$pagingbar->pagevar = 'page';
			$pagination =  $OUTPUT->render($pagingbar);
		}

		$search = array('##reportname##','##reportsummary##','##graphs##','##exportoptions##','##calculationstable##','##pagination##');
		$replace = array(format_string($this->config->name),format_text($this->config->summary),$this->print_graphs(true),$this->print_export_options(true),$calculations,$pagination);

		foreach($page_contents as $key=>$p){
			if($p){
				$page_contents[$key] = str_ireplace($search,$replace,$p);
			}
		}

		if($this->config->jsordering){
			$this->add_jsordering();
		}
		$this->print_filters();

		echo "<div id=\"printablediv\">\n";
		echo format_text($page_contents['header'], FORMAT_HTML);

        $a = new stdClass();
        $a->totalrecords = $this->totalrecords;
        echo html_writer::tag('div',get_string('totalrecords','block_configurable_reports',$a),array('id'=>'totalrecords'));

        if($recordtpl){
			if($this->config->pagination){
				$page = optional_param('page',0,PARAM_INT);
				$this->totalrecords = count($this->finalreport->table->data);
				$this->finalreport->table->data = array_slice($this->finalreport->table->data,$page * $this->config->pagination, $this->config->pagination);
			}

			foreach($this->finalreport->table->data as $r){
				$recordtext = $recordtpl;
				foreach($this->finalreport->table->head as $key=>$c){
					$recordtext = str_ireplace("[[$c]]",$r[$key],$recordtext);
				}
				echo format_text($recordtext, FORMAT_HTML);
			}
		}

		echo format_text($page_contents['footer'], FORMAT_HTML);
		echo "</div>\n";
		echo '<div class="centerpara"><br />';
		echo $OUTPUT->pix_icon('print', get_string('printreport', 'block_configurable_reports'), 'block_configurable_reports');
		echo "&nbsp;<a href=\"javascript: printDiv('printablediv')\">".get_string('printreport','block_configurable_reports')."</a>";
		echo "</div>\n";
	}

	function print_report_page(){
		global $DB, $CFG, $OUTPUT, $USER;

		cr_print_js_function();
		$components = cr_unserialize($this->config->components);

		$template = (isset($components['template']['config']) && $components['template']['config']->enabled && $components['template']['config']->record)? $components['template']['config']: false;

		if($template){
			$this->print_template($template);
			return true;
		}

        // Debug
        $debug = optional_param('debug', false, PARAM_BOOL);
        if ($debug OR $CFG->debugdisplay OR $this->config->debug) {
            echo html_writer::empty_tag('hr');
            echo html_writer::tag('div', $this->sql, array('id'=>'debug', 'style'=>'direction:ltr;text-align:left;'));
            echo html_writer::empty_tag('hr');
        }

        echo '<div class="centerpara">';
		echo format_text($this->config->summary);
		echo '</div>';

		$this->print_filters();
		if($this->finalreport->table && !empty($this->finalreport->table->data[0])){


			echo "<div id=\"printablediv\">\n";
			$this->print_graphs();

			if($this->config->jsordering){
				$this->add_jsordering();
			}

			$this->totalrecords = count($this->finalreport->table->data);
			if($this->config->pagination){
				$page = optional_param('page',0,PARAM_INT);
				$this->totalrecords = count($this->finalreport->table->data);
				$this->finalreport->table->data = array_slice($this->finalreport->table->data,$page * $this->config->pagination, $this->config->pagination);
			}

			cr_print_table($this->finalreport->table);

			if($this->config->pagination){
				$postfiltervars = '';
				$request = array_merge($_POST,$_GET);
				if($request)
					foreach($request as $key=>$val)
						if(strpos($key,'filter_') !== false){
							if(is_array($val)){
								foreach($val as $k=>$v)
									$postfiltervars .= "&amp;{$key}[$k]=".$v;
							}
							else{
								$postfiltervars .= "&amp;$key=".$val;
							}
						}

				//print_paging_bar($this->totalrecords,$page,$this->config->pagination,"viewreport.php?id=".$this->config->id."$postfiltervars&amp;");
				$pagingbar = new paging_bar($this->totalrecords, $page, $this->config->pagination, "viewreport.php?id=".$this->config->id."&courseid=".$this->config->courseid."$postfiltervars&amp;");
				$pagingbar->pagevar = 'page';
				echo $OUTPUT->render($pagingbar);
			}

            // Report statistics
            $a = new stdClass();
            $a->totalrecords = $this->totalrecords;
            echo html_writer::tag('div',get_string('totalrecords','block_configurable_reports',$a),array('id'=>'totalrecords'));

            echo html_writer::tag('div',get_string('lastexecutiontime','block_configurable_reports',$this->config->lastexecutiontime/1000),array('id'=>'lastexecutiontime'));

            if(!empty($this->finalreport->calcs->data[0])){
				echo '<br /><br /><br /><div class="centerpara"><b>'.get_string("columncalculations","block_configurable_reports").'</b></div><br />';
				echo html_writer::table($this->finalreport->calcs);
			}
			echo "</div>";

			$this->print_export_options();
		}
		else{
			echo '<div class="centerpara">'.get_string('norecordsfound','block_configurable_reports').'</div>';
		}

		echo '<div class="centerpara"><br />';
		echo $OUTPUT->pix_icon('print', get_string('printreport', 'block_configurable_reports'), 'block_configurable_reports');
		echo "&nbsp;<a href=\"javascript: printDiv('printablediv')\">".get_string('printreport','block_configurable_reports')."</a>";
		echo "</div>\n";
	}

    public function utf8_strrev($str){
        preg_match_all('/./us', $str, $ar);
        return join('',array_reverse($ar[0]));
    }

 }


