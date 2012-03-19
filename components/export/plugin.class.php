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

abstract class export_plugin extends plugin_base{
    
    function summary($instance){
        return get_string('');
    }
    
    function get_fullname($instance){
        return strtoupper($this->get_name());
    }
    
    function get_icon(){
        global $OUTPUT;
        
        $name = $this->get_name();
        
        $url = new moodle_url("/blocks/configurable_reports/components/export/$name/pix/icon.gif");
        $attributes = array('src' => $url, 'class' => 'smallicon');
        return html_writer::empty_tag('img', $attributes);
        
        // TODO: Simplify with subplugin API
        //return new pix_icon('icon', get_string('pluginname', 'enrol_'.$name), 'enrol_'.$name);
    }

    abstract function execute(report_base $reportclass);
    
}