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
    abstract function get_component_name();
    
    function set_data(){
        $data = $this->get_config_data();
        
        parent::set_data($data);
    }
    
    function get_config_data(){
        global $DB;
        
        $compclass = $this->_customdata['compclass'];
        $search = array('reportid' => $compclass->report->id, 'component' => $this->get_component_name());
        $configdata = $DB->get_field('block_configurable_reports_component', 'configdata', $search);
        
        return $configdata ? cr_unserialize($configdata) : new stdClass();
    }
    
    function save_data($data){
        global $DB;
        
        $configdata = cr_serialize($data);

        $compclass = $this->_customdata['compclass'];
        $search = array('reportid' => $compclass->report->id, 'component' => $this->get_component_name());
        if ($record = $DB->get_record('block_configurable_reports_component', $search)){
            $record->configdata = $configdata;
            $DB->update_record('block_configurable_reports_component', $record);
        } else {
            $record = (object)$search;
            $record->configdata = $configdata;
            $DB->insert_record('block_configurable_reports_component', $record);
        }
    }
}