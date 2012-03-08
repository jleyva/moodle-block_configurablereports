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

require_once($CFG->libdir.'/formslib.php');

abstract class calcs_plugin_form extends plugin_form {
    function get_column_options() {
        $mform = $this->_form;
        
        $components = cr_unserialize($components);
        
        $options = array();
        if ($this->report->type != 'sql') {
            if(!is_array($components) || empty($components['columns']['elements']))
                print_error('nocolumns');
            	
            $columns = $components['columns']['elements'];
            	
            $calcs = isset($components['calcs']['elements'])?  $components['calcs']['elements']: array();
            $columnsused = array();
            if($calcs){
                foreach($calcs as $c){
                    $columnsused[] = $c['formdata']->column;
                }
            }
        
            $i = 0;
            foreach($columns as $c){
                if(!in_array($i,$columnsused))
                    $options[$i] = $c['summary'];
                $i++;
            }
        } else {
            $reportclass = report_base::get($this->report);
            	
            $components = cr_unserialize($this->config->components);
            $config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;
            	
            if(isset($config->querysql)){
        
                $sql =$config->querysql;
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