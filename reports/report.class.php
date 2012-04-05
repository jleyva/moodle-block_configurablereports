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

require_once($CFG->dirroot.'/blocks/configurable_reports/components/component.class.php');

abstract class report_base {
    public  $id;         // Report id
    public  $config;     // Report configuration (DB record)
	private $components; // Component objects (and therefore plugin objects)
	
	var $finalreport;
	var $totalrecords = 0;
	
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
	
	/**
	 * Retrieve a component class object.
	 * @param string          $component    Component type
	 * @param string          $classname    Component classname
	 * @return component_base               Component class object
	 */
	private function _create_component($component, $classname){
	    global $CFG;
	
	    $file = 'component.class.php';
	    $comppath = $this->get_component_path($component, $file);
	    require_once("$comppath/$file");
	
	    return new $classname($this);
	}
	
	/**
	 * Load components objects for this report.
	 */
	private function _load_components(){
	    $this->components = array();
	     
	    foreach($this->all_components() as $comp => $classname){
	        $this->components[$comp] = $this->_create_component($comp, $classname);
	    }
	}
	
	function form_components(){
	    return array(
	        'export' => 'component_export',
	    );
	}
	
	function check_permissions($context, $userid = null){
	    global $CFG, $USER;
	    
	    require_once($CFG->dirroot.'/lib/evalmath/evalmath.class.php');
	    
	    if (!isset($userid)) {
	        $userid = $USER->id;
	    }
	
	    // Management permissions
	    if ($this->config->ownerid == $userid && has_capability('block/configurable_reports:manageownreports', $context, $userid)) {
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
	
	abstract function component_classes();
	
	abstract function create_report();
	
	/**
	 * Create report table using the columns component.
	 *
	 * @param array $columns    Optional array of column instance ids
	 * @return html_table
	 */
	function create_table(array $columns = null){
	    $table = new html_table();
	    $table->summary = $this->config->summary;
	
	    $colcomp = $this->get_component('columns');
	    foreach($colcomp->get_plugins() as $plugclass){
	        foreach($plugclass->get_instances() as $pid => $column){
	            if (isset($columns) && !in_array($pid, $columns)) {
	                continue;
	            }
	            $table->head[] = $plugclass->get_fullname($column);
	            list($align, $size, $wrap) = $plugclass->colformat($column);
	            $table->align[] = $align;
	            $table->size[] = $size;
	            $table->wrap[] = $wrap;
	        }
	    }
	
	    $config = $colcomp->config;
	    if (!empty($config)) {
	        $table->class = $config->class;
	        $table->width = $config->tablewidth;
	        $table->tablealign = $config->tablealign;
	        $table->cellpadding = $config->cellpadding;
	        $table->cellspacing = $config->cellspacing;
	    } else {
	        $table->width = '80%';
	        $table->tablealign = 'center';
	    }
	
	    return $table;
	}
	
	function all_components(){
	    return array_merge($this->component_classes(), $this->form_components());
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
	
	function get_components(){
	    if (!isset($this->components)) {
	        $this->_load_components();
	    }
	    
	    return array_intersect_key($this->components, $this->component_classes());
	}
	
	/**
	 * Get path of component file.
	 * @param string $component    Component type
	 * @param string $file         File name
	 * @param string $reportclass  Optional report class name
	 * @return string              Full path to file directory (i.e. PATH/$file is the absolute dir)
	 */
	public function get_component_path($component, $file, $reportclass = null){
	    global $CFG;
	
	    if (!isset($reportclass)) {
	        $reportclass = get_class($this);
	    }
	     
	    $basedir = "$CFG->dirroot/blocks/configurable_reports";
	    $filepath = "components/$component";
	    if (! ($parentclass = get_parent_class($reportclass))) {
	        if (file_exists("$basedir/$filepath/$file")) {
	            return "$basedir/$filepath";
	        } else {
	            throw new Exception(get_string('nosuchcomponent', 'block_configurable_reports'));
	        }
	    }
	
	    $custompath = "reports/".$this->get_type($reportclass);
	    if (file_exists("$basedir/$custompath/$filepath/$file")) {
	        return "$basedir/$custompath/$filepath";
	    } else {
	        return $this->get_component_path($component, $file, $parentclass);
	    }
    }
	
	function get_form_components(){
	    if (!isset($this->components)) {
	        $this->_load_components();
	    }
	     
	    return array_intersect_key($this->components, $this->form_components());
	}
	
	function get_column_options($ignore = array()){
	    $options = array();
	    
	    $columnclass = $this->get_component('columns');
	    if(!isset($columnclass)){
	        return $options;
	    }
	    $instances = $columnclass->get_all_instances();
	    if (empty($instances)) {
	        return $options;
	    }
	    
	    $i = 0;
	    foreach($instances as $instance){
	        if(!in_array($i, $ignore) && isset($instance->summary)){
	            $options[$i] = $instance->summary;
	        }
	        $i++;
	    }
	
	    return $options;
	}
	
	/**
	 * Retrieve the report type for this class definition.
	 * FORMAT REQUIREMENT: report_XXX_YYY where XXX is the report type
	 * @param string  Optional report class name
	 * @return string Report type
	 */
	function get_type($reportclass = null){
	    if (!isset($reportclass)) {
	        $reportclass = get_class($this);
	    }
	    $pieces = explode('_', $reportclass);
	    return $pieces[1];
	}
	
	function get_typename(){
	    return get_string('report_'.$this->get_type(), 'block_configurable_reports');
	}
	
	function has_component($compname){
	    return array_key_exists($compname, $this->all_components());
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
	    global $PAGE, $OUTPUT;
		
		if ($this->print_using_template()) {
			return true;
		}
				
	    echo html_writer::tag('div', format_text($this->config->summary), array('class' => 'centerpara'));
		
	    $compclass = $this->get_component('filters');
	    $compclass->print_to_report();
			    
		$finaltable = $this->finalreport->table;
		if ($finaltable && !empty($finaltable->data[0])) {
		    $PAGE->requires->js_init_call('M.block_configurable_reports.setup_data_table', array('reporttable'));
			echo html_writer::start_tag('div', array('id' => 'printablediv'));
			
			$compclass = $this->get_component('plot');
			$compclass->print_to_report();
		
			if($this->config->pagination){
				$page = optional_param('page',0,PARAM_INT);
				$items = $page * $this->config->pagination;
				$this->totalrecords = count($finaltable->data);
				$finaltable->data = array_slice($finaltable->data, $items, $this->config->pagination);
			}
		
			echo html_writer::table($finaltable);

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
		
		$this->print_report_link();
    }
    
    function print_report_link($printablediv = 'printablediv'){
        global $PAGE, $OUTPUT;
    
        $PAGE->requires->js_init_call('M.block_configurable_reports.printDiv', array('printreport', $printablediv));
        
        echo html_writer::start_tag('div', array('id' => 'printreport', 'class' => 'centerpara'));
        $url = new moodle_url('/blocks/configurable_reports/printreport.php', array('id' => $this->config->id));
        $printstr = get_string('printreport', 'block_configurable_reports');
        $icon = $OUTPUT->pix_icon('print', $printstr, 'block_configurable_reports');
        echo html_writer::tag('a', "$icon $printstr $icon", array('href' => $url));
        echo html_writer::end_tag('div');
    }
}

?>
