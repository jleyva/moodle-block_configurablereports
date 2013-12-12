<?php
// email form added to enable email to selected users
include_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');

require_login();
global $PAGE, $USER, $DB, $COURSE;
$context = context_course::instance($COURSE->id);
$PAGE->set_context($context );

class sendemail_form extends moodleform {

    function definition() {
        global $COURSE;

        $mform    =& $this->_form;
        $context = context_course::instance($COURSE->id);
        $editor_options = array(
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'context' => $context
        );

        $mform->addElement('hidden', 'usersids', $this->_customdata['usersids']);
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);

        $mform->addElement('text', 'subject', get_string('email_subject','block_configurable_reports'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required');

        $mform->addElement('editor', 'content', get_string('email_message','block_configurable_reports'),null, $editor_options);

        $buttons = array();
        $buttons[] =& $mform->createElement('submit', 'send', get_string('email_send','block_configurable_reports'));
        $buttons[] =& $mform->createElement('cancel');

        $mform->addGroup($buttons, 'buttons', get_string('actions'), array(' '), false);
    }
}

$form = new sendemail_form(null, array('usersids'=>implode(',',$_POST['userids']), 'courseid' => $_POST['courseid']) );

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php?id='.$data->courseid));
} else if ($data = $form->get_data()) {
    //var_dump($data);
    foreach(explode(',',$data->usersids) as $userid){
        //echo "userid=".$userid."<br/>";
        $abouttosenduser = $DB->get_record('user',array('id'=>$userid));
        email_to_user($abouttosenduser ,$USER,$data->subject,format_text($data->content['text']),$data->content['text']);
    }
    // after emails were sent... go back to where you came from
    redirect(new moodle_url('/course/view.php?id='.$data->courseid));
}

$PAGE->set_title(get_string('email', 'questionnaire'));
$PAGE->set_heading(format_string($COURSE->fullname));
$PAGE->navbar->add(get_string('email', 'questionnaire'));
//$PAGE->navbar->add(get_string('sendemail'));

echo $OUTPUT->header() ; //  header();

echo html_writer::start_tag('div', array('class' => 'no-overflow'));
$form->display();
echo html_writer::end_tag('div');

echo $OUTPUT->footer();

