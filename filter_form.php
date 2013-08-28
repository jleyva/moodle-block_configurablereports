<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class report_edit_form extends moodleform {
    function definition() {
        global $DB, $USER, $CFG, $COURSE;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('filter', 'block_configurable_reports'));

		$this->_customdata->add_filter_elements($mform);


		$mform->addElement('hidden', 'id', $this->_customdata->config->id);
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('id', PARAM_INT);
        $mform->setType('courseid', PARAM_INT);

        // buttons
        $this->add_action_buttons(true, get_string('filter_apply', 'block_configurable_reports'));

    }

}

