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

class component_export extends component_base{
    
    function plugin_classes(){
        return array(
                'ods'    => 'plugin_export_ods',
                'xls'    => 'plugin_export_xls',
        );
    }
	
    function report_form_elements(MoodleQuickForm &$mform){
        $mform->addElement('header', 'exportoptions', get_string('exportoptions', 'block_configurable_reports'));

        $hassettings = is_array($this->config);
        foreach($this->get_plugins() as $type => $plugclass){
            $elementname = 'export_'.$type;
            $label = get_string($elementname, 'block_configurable_reports');
            $mform->addElement('checkbox', $elementname, null, $label);
            if ($hassettings && in_array($type, $this->config)) {
                $mform->setDefault($elementname, 1);
            }
        }
    }
    
    function save_report_formdata(stdClass $formdata){
        global $DB;
        
        $exports = array();
        foreach($formdata as $elname => $value){
            if(strpos($elname, 'export_') !== false){
                $exports[] = str_replace('export_', '', $elname);
            }
        }
        $configdata = cr_serialize($exports);
        
        //TODO: Should have some save_instance functions for general config handling
        $search = array(
	        'reportid'  => $this->report->id, 
	        'component' => $this->get_type(),
	    );
        if ($record = $DB->get_record('block_cr_component', $search)) {
            $method = 'update_record';
        } else {
            $record = (object) $search;
            $method = 'insert_record';
        }
        
        $record->configdata = $configdata;
        $DB->$method('block_cr_component', $record);
    }
    
    function print_to_report($return = false){
        $label = get_string('downloadreport', 'block_configurable_reports');
        $options = implode(' ', $this->get_export_options());
        $output = html_writer::tag('div', $label.': '.$options, array('class' => 'centerpara'));
        if($return){
            return $output;
        }
        echo $output;
    }
    
    function get_export_options(){
        $params = array('id' => $this->report->id, 'download' => 1);
        
        // TODO: REVIEW $params func parameter?
        $wwwpath = '';
        $request = array_merge($_POST,$_GET);
        if($request){
            foreach($request as $key=>$val){
                if(is_array($val)){
                    foreach($val as $k=>$v)
                        $wwwpath .= "&amp;{$key}[$k]=".$v;
                }
                else{
                    $wwwpath .= "&amp;$key=".$val;
                }
            }
        }
        
        $exports = cr_unserialize($this->config);
        
        $viewurl = new moodle_url('/blocks/configurable_reports/viewreport.php', $params);
        
        $options = array();
        foreach($exports as $export){
            $exportclass = $this->get_plugin($export);
            $icon = $exportclass->get_icon();
            $fullname = $exportclass->get_fullname(null);
            $attr = array('href' => $viewurl->out(false, array('format' => $exportclass->get_type())));
            $options[] = html_writer::tag('a', "$icon $fullname", $attr);
        }
        
        return $options;
    }
}

?>