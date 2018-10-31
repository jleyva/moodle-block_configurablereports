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

// Email form added to enable email to selected users.
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');

require_login();
global $PAGE, $USER, $DB, $COURSE;
$context = context_course::instance($COURSE->id);
$PAGE->set_context($context);

if (!has_capability('block/configurable_reports:managereports', $context) && !has_capability('block/configurable_reports:manageownreports', $context)) {
    print_error('badpermissions');
}

class sendemail_form extends moodleform {

    public function definition() {
        global $COURSE;

        $mform =& $this->_form;
        $context = \context_course::instance($COURSE->id);
        $editoroptions = [
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'context' => $context
        ];

        $mform->addElement('hidden', 'usersids', $this->_customdata['usersids']);
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);

        $mform->addElement('text', 'subject', get_string('email_subject', 'block_configurable_reports'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required');

        $mform->addElement('editor', 'content', get_string('email_message', 'block_configurable_reports'), null, $editoroptions);

        $buttons = array();
        $buttons[] =& $mform->createElement('submit', 'send', get_string('email_send', 'block_configurable_reports'));
        $buttons[] =& $mform->createElement('cancel');

        $mform->addGroup($buttons, 'buttons', get_string('actions'), array(' '), false);
    }
}

$form = new \sendemail_form(null, ['usersids' => implode(',', $_POST['userids']), 'courseid' => $_POST['courseid']]);

if ($form->is_cancelled()) {
    redirect(new \moodle_url('/course/view.php?id='.$data->courseid));
} else if ($data = $form->get_data()) {
    foreach (explode(',', $data->usersids) as $userid) {
        $abouttosenduser = $DB->get_record('user', ['id' => $userid]);
        email_to_user($abouttosenduser, $USER, $data->subject, format_text($data->content['text']), $data->content['text']);
    }
    // After emails were sent... go back to where you came from.
    redirect(new \moodle_url('/course/view.php?id='.$data->courseid));
}

$PAGE->set_title(get_string('email', 'questionnaire'));
$PAGE->set_heading(format_string($COURSE->fullname));
$PAGE->navbar->add(get_string('email', 'questionnaire'));

echo $OUTPUT->header();

echo \html_writer::start_tag('div', ['class' => 'no-overflow']);
$form->display();
echo \html_writer::end_tag('div');

echo $OUTPUT->footer();
