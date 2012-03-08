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
 * @author Nick Koeppen
 */ 

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin_form.class.php');

abstract class plot_plugin_form extends plugin_form {
    function get_column_options() {
        $options = array();
        
        if($this->report->type != 'sql'){
            $components = cr_unserialize($this->_customdata['report']->components);
            	
            if(!is_array($components) || empty($components['columns']['elements']))
                print_error('nocolumns');
        
            $columns = $components['columns']['elements'];
            foreach($columns as $c){
                $options[] = $c['summary'];
            }
        } else {
            $reportclass = report_base::get($this->report);
            	
            $components = cr_unserialize($report->components);
            $config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;
            	
            if(isset($config->querysql)){
                $sql = $config->querysql;
                $sql = $reportclass->prepare_sql($sql);
                if($rs = $reportclass->execute_query($sql)){
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
}
