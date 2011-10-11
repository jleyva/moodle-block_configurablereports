<?php  

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class report_edit_form extends moodleform {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('filter', 'block_configurable_reports'));

		$this->_customdata->add_filter_elements($mform);
		
		
		$mform->addElement('hidden', 'id', $this->_customdata->config->id);
        // buttons
        $this->add_action_buttons(true, get_string('add'));

    }

}

?>