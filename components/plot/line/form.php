<?php  

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/plugin_form.class.php');

class line_form extends plot_plugin_form {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;
		
        $options = $this->get_column_options();
        
		$mform->addElement('header', '', get_string('linegraph','block_configurable_reports'), '');

		$mform->addElement('select', 'xaxis', get_string('xaxis','block_configurable_reports'), $options);
		$mform->addRule('xaxis', null, 'required', null, 'client');
		
		$mform->addElement('select', 'serieid', get_string('serieid','block_configurable_reports'), $options);
		$mform->addRule('serieid', null, 'required', null, 'client');
		
		$mform->addElement('select', 'yaxis', get_string('yaxis','block_configurable_reports'), $options);
		$mform->addRule('yaxis', null, 'required', null, 'client');
		
		$mform->addElement('checkbox', 'group', get_string('groupseries','block_configurable_reports'));
				
        $this->add_action_buttons(true, get_string('add'));
    }
	
	function validation($data, $files){
		$errors = parent::validation($data, $files);
	
		if($data['xaxis'] == $data['yaxis'])
			$errors['yaxis'] = get_string('xandynotequal','block_configurable_reports');
	
		return $errors;
	}

}

?>