<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class bar_form extends moodleform {
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
		                            $key = "$i,$colname";
		                            $options[$key] = str_replace('_', ' ', $colname);
		                            $i++;
		                        }
		                        break;
		                    }
					$rs->close();
				}
			}
		}


		$mform->addElement(
			'header',  
			'crformheader' ,
			get_string('head_data','block_configurable_reports'), 
			''
		);

		$mform->addElement(
			'select', 
			'label_field',
			get_string('label_field','block_configurable_reports'), 
			$options
		);
		$mform->addHelpButton('label_field','label_field','block_configurable_reports');
		$valueselect = $mform->addElement(
			'select', 
			'value_fields', 
			get_string('value_fields','block_configurable_reports'), 
			$options
		);
		$valueselect->setMultiple(true);
		$mform->addHelpButton('value_fields','value_fields','block_configurable_reports');
		
		$this->add_formatting_elements($mform);
	}
	
	function add_formatting_elements($mform) {
		$mform->addElement(
			'header', 
			'size', 
			get_string('head_size', 'block_configurable_reports')
		);
		
		$mform->addElement(
			'text',
			'width',
			get_string('width','block_configurable_reports')
		);
		$mform->setDefault("width",900);
		$mform->setType("width", PARAM_INT);
		$mform->addElement(
			'text',
			'height',
			get_string('height','block_configurable_reports')
		);
		$mform->setDefault("height",500);
		$mform->setType("height", PARAM_INT);
		
        /* Shouldn't use these without a way to automatically
         * calculate colors for the text and bars that contrast
         * with the chosen background.
         *
		$mform->addElement(
			'header', 
			'color', 
			get_string('head_color', 'block_configurable_reports')
		);
		$mform->addElement(
			'text',
			'color_r',
			"R",
			"size = 5"
		);
		$mform->setDefault("color_r",170);
		$mform->addElement(
			'text',
			'color_g',
			"G",
			"size = 5"
		);
		$mform->setDefault("color_g",183);
		$mform->addElement(
			'text',
			'color_b',
			"B",
			"size = 5"
		);		
		$mform->setDefault("color_b",87);
        */

        // buttons
        $this->add_action_buttons(true, get_string('add'));

    }

}

