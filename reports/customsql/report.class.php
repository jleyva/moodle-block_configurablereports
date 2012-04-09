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
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

require_once("$CFG->dirroot/blocks/configurable_reports/reports/report_sql.class.php");

class report_customsql extends report_sql_base{
	const max_records = 5000;
    
    function component_classes(){
        return array(
            'customsql'   => 'component_customsql',
            'filters'     => 'component_filters_sql',
            'permissions' => 'component_permissions',
            'calcs'       => 'component_calcs',
            'plot'        => 'component_plot_sql',
            'template'    => 'component_template',
        );
    }
	
	function execute_query($sql, $limitnum = self::max_records) {
		global $DB, $CFG;
		
		$sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);
		return $DB->get_recordset_sql($sql, null, 0, $limitnum);
	}
	
	function get_column_options($ignore = array()){
	    $options = array();
	    
	    $compclass = $this->get_component('customsql');
	    if (isset($compclass) && isset($compclass->config->querysql)) {
	        $sql = $this->prepare_sql($config->querysql);
	        
	        $rs = $this->execute_query($sql);
            foreach ($rs as $row) {
                $i = 0;
                foreach($row as $colname => $value){
                    $options[$i] = str_replace('_', ' ', $colname);
                    $i++;
                }
                break;
            }
            $rs->close();
	    }
	    
	    return $options;
	}
	
	function prepare_sql($sql) {
	    global $USER;
	    
	    // APPLY FILTERS
	    if ($compclass = $this->get_component('filters')) {
    	    foreach($compclass->get_plugins() as $plugclass){
    	        foreach($plugclass->get_instances() as $filter){
    	            $sql = $plugclass->execute($sql, $filter);
    	        }
    	    }
	    }
	
	    $sql = str_replace('%%USERID%%', $USER->id, $sql);
	    // See http://en.wikipedia.org/wiki/Year_2038_problem
	    $sql = str_replace(array('%%STARTTIME%%','%%ENDTIME%%'), array('0','2145938400'), $sql);
	    $sql = preg_replace('/%{2}[^%]+%{2}/i','',$sql);
	    return $sql;
	}
	
}

?>