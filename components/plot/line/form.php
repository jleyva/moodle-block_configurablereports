<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class line_form extends moodleform {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;
		$options = array(0=>get_string('choose'));

		$report = $this->_customdata['report'];

		if($report->type != 'sql'){
			$components = cr_unserialize($this->_customdata['report']->components);

			if(!is_array($components) || empty($components['columns']['elements']))
				print_error('nocolumns');

			$columns = $components['columns']['elements'];
			foreach($columns as $c){
				$options[] = $c['summary'];
			}
		}
		else{

			require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
			require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

			$reportclassname = 'report_'.$report->type;
			$reportclass = new $reportclassname($report);

			$components = cr_unserialize($report->components);
			$config = (isset($components['customsql']['config']))? $components['customsql']['config'] : new stdclass;

			if(isset($config->querysql)){

				$sql = $config->querysql;
				$sql = $reportclass->prepare_sql($sql);
				if($rs = $reportclass->execute_query($sql)){
					foreach($rs as $row){
						$i = 1;
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


		$mform->addElement('header',  'crformheader' ,get_string('line','block_configurable_reports'), '');

		$mform->addElement('select', 'xaxis', get_string('xaxis','block_configurable_reports'), $options);
		$mform->addRule('xaxis', null, 'required', null, 'client');

		$mform->addElement('select', 'serieid', get_string('serieid','block_configurable_reports'), $options);
		$mform->addRule('serieid', null, 'required', null, 'client');

		$mform->addElement('select', 'yaxis', get_string('yaxis','block_configurable_reports'), $options);
		$mform->addRule('yaxis', null, 'required', null, 'client');

		$mform->addElement('checkbox', 'group', get_string('groupseries','block_configurable_reports'));

        // buttons
        $this->add_action_buttons(true, get_string('add'));

    }

	function validation($data, $files){
		$errors = parent::validation($data, $files);

		if($data['xaxis'] == $data['yaxis'])
			$errors['yaxis'] = get_string('xandynotequal','block_configurable_reports');

		return $errors;
	}

}

