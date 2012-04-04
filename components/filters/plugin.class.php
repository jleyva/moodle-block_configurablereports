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

abstract class filters_plugin extends plugin_base{
    
    function instance_allow_multiple(){
        return false;
    }
    
    function get_fullname($instance){
        return get_string('filter'.$this->get_type(), 'block_configurable_reports');
    }
    
    function summary($instance){
        return get_string('filter'.$this->get_type().'_summary', 'block_configurable_reports');
    }
    
    function sql_elements($finalelements, $filter){
        $filtername = "FILTER_".strtoupper($this->get_type());
        if(preg_match("/%%$filtername:([^%]+)%%/i", $finalelements, $output)){
            $replace = ' AND '.$output[1].' = '.$filter;
            return str_replace("%%$filtername:$output[1]%%", $replace, $finalelements);
        }
    }
    
    abstract function execute($finalelements, $instance);
    
    abstract function print_filter(&$mform, $instance);
    
}