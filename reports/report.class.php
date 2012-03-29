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
    public  $id;         // Report id
    public  $config;     // Report configuration (DB record)
	private $components; // Component objects (and therefore plugin objects)
	
	var $finalreport;
	var $totalrecords = 0;
	var $starttime = 0;
	var $endtime = 0;
	
	/**
	 * Retrieve a report class based on report record or report id.
	 * 
	 * @param mixed $reportorid    report DB record (stdClass) or id (int)
	 * @throws moodle_exception
	 * @return report_base         report class object
	 */
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
	
	function __construct(stdClass $report){
	    $this->id = $report->id;
		$this->config = $report;
	}
	
	function get_name(){
	    return format_string($this->config->name);
	}
	
	function form_components(){
	    return array(
	        'export'    => 'component_export',
	    );
	}
	
	abstract function component_classes();
	
	function all_components(){
	    return array_merge($this->component_classes(), $this->form_components());
	}
	
	function _load_components(){
	    $this->components = array();
	    
	    foreach($this->all_components() as $comp => $classname){
	        $this->components[$comp] = component_base::get($this, $comp, $classname);
	    }
	}
	
	function get_components($includeformcomps = false){
	    if (!isset($this->components)) {
	        $this->_load_components();
	    }
	    
	    return array_intersect_key($this->components, $this->component_classes());
	}
	
	function get_form_components(){
	    if (!isset($this->components)) {
	        $this->_load_components();
	    }
	     
	    return array_intersect_key($this->components, $this->form_components());
	}
	
	function has_component($compname){
	    return array_key_exists($compname, $this->all_components());
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
	    if (!isset($this->components)) {
	        $this->_load_components();
	    }
	    
	    return $this->components[$compname];
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
		if ($this->config->ownerid == $userid && has_capability('block/configurable_reports:manageownreports', $context, $userid)){
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
		foreach($permclass->get_plugins() as $plugclass){
		    foreach($plugclass->get_instances() as $permission){
    		    $cond[$i] = $plugclass->execute($userid, $context, $permission);
    		    $i++;
		    }
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
		global $USER, $COURSE;
		
		$finalelements = $this->get_elements_by_conditions();
				
		// FILTERS    execute(finalelements, $instance)
		$compclass = $this->get_component('filters');
		foreach($compclass->get_plugins() as $plugclass){
		    foreach($plugclass->get_instances() as $filter){
		        $finalelements = $plugclass->execute($finalelements, $filter);
		    }
		}
		
		// ORDERING
		$sqlorder = '';
		$orderingdata = array();
		$compclass = $this->get_component('ordering');
		foreach($compclass->get_plugins() as $plugclass){
		    foreach($plugclass->get_instances() as $order){
				$sqlorder = $plugclass->execute($order);
			}
		}
		
		// RETRIEVE DATA ROWS
		$rows = $this->get_rows($finalelements, $sqlorder);
	
		// COLUMNS - FIELDS
		$compclass = $this->get_component('columns');
		$reporttable = array();
		foreach($rows as $row){
			foreach($compclass->get_plugins() as $plugclass){
			    if(! ($columns = $plugclass->get_instances())){
			        continue;
			    }
			    $tempcols = array();
			    foreach($columns as $column){			         
				    $tempcols[] = $plugclass->execute($USER, $COURSE->id, $column, $row);
			    }
			    $reporttable[] = $tempcols;
			}
			
		}
		
// 		// EXPAND ROWS
// 		$finaltable = array();
// 		$newcols = array();
		
// 		foreach($reporttable as $row){
// 			$col = array();
// 			$multiple = false;
// 			$nrows = 0;
// 			$mrowsi = array();
						
// 			foreach($row as $key => $cell){
// 				if (!is_array($cell)) {
// 					$col[] = $cell;				
// 				} else {
// 					$multiple = true;
// 					$nrows = count($cell);
// 					$mrowsi[] = $key;
// 				}				
// 			}
// 			if ($multiple) {
// 				$newrows = array();
// 				for($i=0; $i<$nrows; $i++){
// 					$newrows[$i] = $row;
// 					foreach($mrowsi as $index){
// 						$newrows[$i][$index] = $row[$index][$i];
// 					}
// 				}
// 				foreach($newrows as $r)
// 					$finaltable[] = $r;
// 			} else {
// 				$finaltable[] = $col;
// 			}
// 		}
		
		$table = $this->create_table();
		$table->id = 'reporttable';
		$table->data = $reporttable;
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
	            $table->head[] = $plugclass->get_fullname($column);
	            list($align, $size, $wrap) = $plugclass->colformat($column);
	            $table->align[] = $align;
	            $table->size[] = $size;
	            $table->wrap[] = $wrap;
	        }
	    }
	    if (!empty($config)) {
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
	    if (empty($compclass->config) || !$compclass->config->enabled) {
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

    	    if($this->config->pagination){
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
    	    
    	        $this->totalrecords = count($this->finalreport->table->data);
    	        $baseurl = new moodle_url('viewreport.php', $params);
    	        $pagingbar = new paging_bar($this->totalrecords, $page, $this->config->pagination, $baseurl, 'page');
    	        $pagination =  $OUTPUT->render($pagingbar);
    	    }
		
    	    $compclass = $this->get_component('calcs');
    	    $compclass->print_to_report($this);
    	    
			echo html_writer::end_tag('div');
			
			$compclass = $this->get_component('export');
			$compclass->print_to_report();
		} else {
		    $norecords = get_string('norecordsfound', 'block_configurable_reports');
		    echo html_writer::tag('div', $norecords, array('class' => 'centerpara'));
		}		
		
		cr_print_link($this->config->id);
    }
}

?>
