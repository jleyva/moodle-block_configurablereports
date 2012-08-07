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

abstract class component_form extends moodleform {
    
    function set_data(){
        $data = $this->get_config_data();
        
        parent::set_data($data);
    }
    
    function get_config_data(){
        global $DB;
        
        $compclass = $this->_customdata['compclass'];
        $search = array('reportid' => $compclass->report->id, 'component' => $compclass->get_type());
        $configdata = $DB->get_field('block_cr_component', 'configdata', $search);
        
        return $configdata ? cr_unserialize($configdata) : new stdClass();
    }
    
//     function set_data($instance){
//         $data = cr_unserialize($instance->configdata);
    
//         parent::set_data($data);
//     }
    
//     function save_data($data, $instanceid = null){
//         global $DB;
    
//         $plugclass = $this->_customdata['plugclass'];
//         if (isset($instanceid) && ($instance = $plugclass->get_instance($instanceid))) {
//             $instance->configdata = $data;
//             $plugclass->update_instance($instance);
//         } else {
//             $plugclass->add_instance($data);
//         }
//     }
    
    function save_data($data){
        global $DB;
        
        $configdata = cr_serialize($data);

        $compclass = $this->_customdata['compclass'];
        $search = array('reportid' => $compclass->report->id, 'component' => $compclass->get_type());
        if ($record = $DB->get_record('block_cr_component', $search)){
            $record->configdata = $configdata;
            $DB->update_record('block_cr_component', $record);
        } else {
            $record = (object)$search;
            $record->configdata = $configdata;
            $DB->insert_record('block_cr_component', $record);
        }
    }
}