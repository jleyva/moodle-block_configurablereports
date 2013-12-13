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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class reportcolumn_form extends moodleform {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;
        $mform->addElement('header',  'crformheader' ,get_string('reportcolumn','block_configurable_reports'), '');

		$reportid = optional_param('reportid',0,PARAM_INT);
		if($actualrid = $this->_customdata['pluginclass']->get_current_report($this->_customdata['report']))
			$reportid = $actualrid;

		$reports = $this->_customdata['pluginclass']->get_user_reports();
        $reportoptions = array(0=>get_string('choose'));

		if($reports)
			foreach($reports as $r)
				$reportoptions[$r->id] = format_string($r->name);

		$furl = "$CFG->wwwroot/blocks/configurable_reports/editplugin.php?id=".$this->_customdata['report']->id."&comp=columns&pname=reportcolumn";
		$options = array('onchange'=>'location.href="'.$furl.'&reportid="+document.getElementById("id_reportid").value');
		if($actualrid)
			$options['disabled'] = 'disabled';

		$mform->addElement('select', 'reportid', get_string('report','block_configurable_reports'), $reportoptions, $options);
		$mform->setDefault('reportid',$reportid);

		$columnsoptions = $this->_customdata['pluginclass']->get_report_columns($reportid);
		$mform->addElement('select', 'column', get_string('column','block_configurable_reports'), $columnsoptions);

		$this->_customdata['compclass']->add_form_elements($mform,$this);

        // buttons
        $this->add_action_buttons(true, get_string('add'));

    }

	function validation($data, $files){
		$errors = parent::validation($data, $files);

		$errors = $this->_customdata['compclass']->validate_form_elements($data,$errors);

		if(!$data['reportid'])
			$errors['reportid'] = get_string('missingcolumn','block_configurable_reports');

		if(!isset($data['column']))
			$errors['column'] = get_string('missingcolumn','block_configurable_reports');

		return $errors;
	}

}

