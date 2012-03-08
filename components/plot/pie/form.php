<?php  

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/plugin_form.class.php');

class pie_form extends plot_plugin_form {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;

		$options = $this->get_column_options();
		
		$mform->addElement('header', '', get_string('coursefield','block_configurable_reports'), '');

		$mform->addElement('select', 'areaname', get_string('pieareaname','block_configurable_reports'), $options);
		$mform->addElement('select', 'areavalue', get_string('pieareavalue','block_configurable_reports'), $options);
		$mform->addElement('checkbox', 'group', get_string('groupvalues','block_configurable_reports'));
				
        $this->add_action_buttons(true, get_string('add'));
    }

}

?>