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
 * @author: Nick Koeppen
 */

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plugin.class.php');

abstract class conditions_plugin extends plugin_base{
    
    function summary($instance){
        return get_string($this->get_type().'_summary', 'block_configurable_reports');
    }

    function get_operators(){
        return array(
            '=' => '=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
            '<>' => '<>',
            'LIKE'         => 'LIKE',
            'LIKE % %'     => 'LIKE % %',
            'NOT LIKE'     => 'NOT LIKE',
            'NOT LIKE % %' => 'NOT LIKE % %'
        );
    }
    
    function operator_sql($data, $params = array()){
        global $DB;
        
        switch($data->operator){
            case 'NOT LIKE % %':
                $data->value = "%$data->value%";
            case 'NOT LIKE':
                $params['value'] = $data->value;
                $sql = $DB->sql_like($data->field, ':value', true, true, true);
                break;
            case 'LIKE % %':
                $data->value = "%$data->value%";
            case 'LIKE':
                $params['value'] = $data->value;
                $sql = $DB->sql_like($data->field, ':value');
                break;
            default:
                $params['value'] = $data->value;
                $sql = "$data->field $data->operator :value";
                break;
        }
        
        return array($sql, $params);
    }
    
    abstract function execute($instance);
    
}