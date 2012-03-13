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
require_once($CFG->dirroot.'/blocks/configurable_reports/components/component.class.php');

abstract class report_base {
    var $config;        // Report configuration (DB record)
	var $components;    // Component objects (and therefore plugin objects)
	
	var $finalreport;
	var $totalrecords = 0;
	var $starttime = 0;
	var $endtime = 0;
	
	static function get($reportorid){
	    global $CFG, $DB;
	    
	    if (is_numeric($reportorid)) {
	        $report = $DB->get_record('block_configurable_reports_report', array('id' => $reportorid));
	    } else if($reportorid instanceof stdClass) {
	        $report = $reportorid;
	    } else {
	        throw new moodle_exception('invalidreport');    //TODO
	    }
	    
	    require_once("$CFG->dirroot/blocks/configurable_reports/reports/$report->type/report.class.php");
	    
	    $reportclassname = 'report_'.$report->type;
	    return new $reportclassname($report);
	}
	
	function __construct($report){
		$this->config = $report;
	}
	
	function get_name(){
	    return format_string($this->config->name);
	}
	
	function component_classes(){
	    return array(
		        'columns'     => 'component_columns',
		        'conditions'  => 'component_conditions',
		        'ordering'    => 'component_ordering',
		        'filters'     => 'component_filters',
		        'permissions' => 'component_permissions',
		        'calcs'       => 'component_calcs',
		        'plot'        => 'component_plot',
		        'template'    => 'component_template',
		);
	}
	
	function _load_components(){
	    $this->components = array();
	    foreach($this->component_classes() as $comp => $classname){
	        $this->components[$comp] = component_base::get($this->config, $comp, $classname);
	    }
	}
	
	function get_components(){
	    if (!isset($this->components)) {
	        $this->_load_components();
	    }
	    
	    return $this->components;
	}
	
	function has_component($compname){
	    return array_key_exists($compname, $this->component_classes());
	}
	
	/**
	 * Return the component class object for this report.
	 * @param string $compname    Component name
	 * @return component_base     Component class
	 */
	function get_component($compname){
	    if (!$this->has_component($compname)) {
	        return null;
	    }
	    $components = $this->get_components();
	    
	    return $components[$compname];
	}
	
	/* Core component integration */
	
	function get_column_options($ignore = array()){
	    $options = array();
	    if ($this->config->type != 'sql') {
	        $columnclass = $this->get_component('columns');
	        if(!isset($columnclass)){
	            return null;
	        }
	        $instances = $columnclass->get_all_instances();
	        if (empty($instances)) {
	            //print_error('nocolumns');
	        }
	
	        $i = 0;
	        foreach($instances as $instance){
	            if(!in_array($i, $ignore) && isset($instance->summary)){
	                $options[$i] = $instance->summary;
	            }
	            $i++;
	        }
	    } else {
	        $customsqlclass = $this->get_component('customsql');
	        if(!isset($customsqlclass)){
	            return null;
	        }
	        $config = $customsqlclass->config;
	
	        if(isset($config->querysql)){
	            $sql = $this->prepare_sql($config->querysql);
	            if($rs = $this->execute_query($sql)){
	                foreach ($rs as $row) {
	                    $i = 0;
	                    foreach($row as $colname=>$value){
	                        $options[$i] = str_replace('_', ' ', $colname);
	                        $i++;
	                    }
	                    break;
	                }
	                $rs->close();
	            }
	        }
	    }
	
	    return $options;
	}
	
	function check_permissions($context, $userid = null){
		global $USER;
		if (!isset($userid)) {
		    $userid = $USER->id;
		}
		
		// Management permissions
		if ($this->config->ownerid == $userid &&
		        has_capability('block/configurable_reports:manageownreports', $context, $userid)){
		    return true;
		}
		if (has_capability('block/configurable_reports:managereports', $context, $userid)) {
			return true;
		}
			
		// Visibility
		if (empty($this->config->visible)) {
			return false;
		}
		
		// Custom permissions
		$permclass = $this->get_component('permissions');
		$permissions = $permclass->get_all_instances();
		if (empty($permissions)) {
		    return has_capability('block/configurable_reports:viewreports', $context);
		}
		
		$i = 1;
		$cond = array();
		foreach($permissions as $permission){
		    $cond[$i] = $permclass->execute($userid, $context, $permission);
		    $i++;
		}
		
		if (count($cond) == 1) {
		    return $cond[1];
		} else if (isset($permclass->config) && isset($permclass->config->conditionexpr)) {
		    $logic = trim($permclass->config->conditionexpr);
		    
		    $m = new EvalMath;
		    $orig = $dest = array();
		    
		    // Security
		    // No more than conditions * 10 chars
		    $logic = substr($logic, 0, count($permissions['elements']) * 10);
		    $logic = str_replace(array('and','or'), array('&&','||'), strtolower($logic));
		    // Only allowed chars
		    $logic = preg_replace('/[^&c\d\s|()]/i', '', $logic);
		    //$logic = str_replace('c','$c',$logic);
		    $logic = str_replace(array('&&','||'), array('*','+'), $logic);
		    	
		    for($j = $i -1; $j > 0; $j--){
		        $orig[] = 'c'.$j;
		        $dest[] = ($cond[$j]) ? 1 : 0;
		    }
		    	
		    return $m->evaluate(str_replace($orig, $dest, $logic));
		}
		
		return false;
	} 
	
	//TODO: CHECK
	
	function print_export_options($return = false){
		global $DB, $CFG;
		
		$params = array('id' => $this->config->id);
		
		$request = array_merge($_POST,$_GET);
		if($request){
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
		
		$viewurl = new moodle_url('/blocks/configurable_reports/viewreport.php', $params);
		
		$output = '';
		if(!empty($this->config->export)){
		    $export = explode(',', $this->config->export);
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
	}
	
	function get_elements_by_conditions(){
		global $USER, $COURSE;
		
		$i = 1;
		$finalelements = array();
		$condcomp = $this->get_component('conditions');
		foreach($condcomp->get_plugins() as $plugclass){
		    foreach($plugclass->get_instances() as $condition){
    			$elements[$i] = $plugclass->execute($USER->id, $COURSE->id, $condition);
    			$i++;
		    }
		}
		
		if (empty($elements)) {
		    return $this->get_all_elements();
		} else if(count($elements) == 1){
			$finalelements = $elements[1];
		}else{
			$finalelements = $condcomp->evaluate_expression($elements);
		}
				
		return $finalelements;
	}
	
	// Returns a report object 	
	function create_report(){
		global $DB, $CFG;
		
		$finalelements = $this->get_elements_by_conditions();
				
		// FILTERS    execute(finalelements, $instance)
		$compclass = $this->get_component('filters');
		foreach($compclass->get_plugins() as $plugclass){
		    foreach($plugclass->get_instances() as $filter){
		        $finalelements = $plugclass->execute($finalelements, $filter);
		    }
		}
		
		// ORDERING - only execute if SQL = true?
		$sqlorder = '';
		$orderingdata = array();
		$compclass = $this->get_component('ordering');
		foreach($compclass->get_plugins() as $plugclass){
		    if (!$plugclass->sql) {
		        continue;
		    }
		    foreach($plugclass->get_instances as $order){
				$sqlorder = $compclass->execute($order);
			}
		}
				
		// COLUMNS - FIELDS
		$columns = $this->get_component('columns');
		
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
		
		// EXPAND ROWS
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
		
		$table = $this->create_table();
		$table->id = 'reporttable';
		$table->data = $finaltable;
		$this->finalreport->table = $table;
		
		return true;
	
	}
	
	function add_jsordering(){
		cr_add_jsordering('#reporttable');
	}
	
	/**
	 * Create report table.
	 * 
	 * @param array $columns    Optional array of column instance ids
	 * @return html_table
	 */
	function create_table(array $columns = null){
	    $colcomp = $this->get_component('columns');
	    $config = $colcomp->config;
	    
	    $table = new html_table();
	    $table->summary = $this->config->summary;
	    foreach($colcomp->get_plugins() as $plugclass){
	        foreach($plugclass->get_instances() as $pid => $column){
	            if (isset($columns) && in_array($pid, $columns)) {
	                continue;
	            }
	            $table->head[] = $plugclass->summary($column);
	            list($align, $size, $wrap) = $plugclass->colformat($column);
	            $table->align[] = $align;
	            $table->size[] = $size;
	            $table->wrap[] = $wrap;
	        }
	    }
	    if (isset($config)) {
	        $table->class = $config->class;
	        $table->width = $config->tablewidth;
	        $table->tablealign = $config->tablealign;
	        $table->cellpadding = $config->cellpadding;
	        $table->cellspacing = $config->cellspacing;
	    }
	    
	    return $table;
	}
	
	function print_using_template(){
	    $compclass = $this->get_component('template');
	    if (!isset($compclass->config) || !$compclass->config->enabled) {
	        return false;
	    }
	    
	    $compclass->print_report($this);
	    return true;
	}
	
	function print_report_page(){
	    global $OUTPUT;
	    
		cr_print_js_function();
		
		if ($this->print_using_template()) {
			return true;
		}
				
	    echo html_writer::tag('div', format_text($this->config->summary), array('class' => 'centerpara'));
		
	    $compclass = $this->get_component('filters');
	    $compclass->print_to_report();
		
		$finaltable = $this->finalreport->table;
		if ($finaltable && !empty($finaltable->data[0])) {
			echo html_writer::start_tag('div', array('id' => 'printablediv'));
			
			$compclass = $this->get_component('plot');
			$compclass->print_to_report();
			
			if($this->config->jsordering){
				$this->add_jsordering();				
			}
		
			if($this->config->pagination){
				$page = optional_param('page',0,PARAM_INT);
				$items = $page * $this->config->pagination;
				$this->totalrecords = count($finaltable->data);
				$finaltable->data = array_slice($finaltable->data, $items, $this->config->pagination);
			}
		
			cr_print_table($finaltable);

    	    if($reportclass->config->pagination){
    	        $params = array('id' => $this->config->id);
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
		
    	    $compclass = $this->get_component('calcs');
    	    $compclass->print_to_report($this);
    	    
			echo html_writer::end_tag('div');
			
			$this->print_export_options();
		} else {
		    $norecords = get_string('norecordsfound', 'block_configurable_reports');
		    echo html_writer::tag('div', $norecords, array('class' => 'centerpara'));
		}		
		
		cr_print_link($this->config->id);
    }
}

?>
