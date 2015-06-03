<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class pie_form extends moodleform {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;
		$options = array();

		$report = $this->_customdata['report'];

		if($report->type != 'sql'){
			$components = cr_unserialize($this->_customdata['report']->components);

			if(!is_array($components) || empty($components['columns']['elements']))
				print_error('nocolumns');

			$columns = $components['columns']['elements'];
			foreach($columns as $c){
				if (!empty($c['summary'])) {
					$options[] = $c['summary'];
				}
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


		$mform->addElement('header',  'crformheader' ,get_string('coursefield','block_configurable_reports'), '');

		$mform->addElement('select', 'areaname', get_string('pieareaname','block_configurable_reports'), $options);
		$mform->addElement('select', 'areavalue', get_string('pieareavalue','block_configurable_reports'), $options);

		// New Feature: Select Limit Categories in PIE.
		$limits = array();
		for ($i=0;$i<=10;$i++) {
			$limits[$i] = $i;
		}
		$mform->addElement('select', 'limitcategories', get_string('limitcategories','block_configurable_reports'), $limits);
		
		// New Feature: Select Decimals in PIE
		$decimals = array('0'=>0, '1'=>1, '2'=>2);
		$mform->addElement('select', 'decimals', get_string('decimals','block_configurable_reports'), $decimals);
		


		$mform->addElement('checkbox', 'group', get_string('groupvalues','block_configurable_reports'));

        // buttons
        $this->add_action_buttons(true, get_string('add'));

    }

}

