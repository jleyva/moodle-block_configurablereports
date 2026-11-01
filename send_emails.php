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

/**
 * Configurable Reports a Moodle block for creating customizable reports
 *
 * @copyright  2020 Juan Leyva <juan@moodle.com>
 * @package    block_configurable_reports
 * @author     Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Email form added to enable email to selected users.
require_once('../../config.php');

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');

$courseid = optional_param('courseid', SITEID, PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
if ($course->id == SITEID) {
    require_login();
    $context = context_system::instance();
} else {
    require_login($course);
    $context = context_course::instance($course->id);
}
$PAGE->set_context($context);
$PAGE->set_url('/blocks/configurable_reports/send_emails.php', ['courseid' => $course->id]);

if (!has_any_capability(['block/configurable_reports:managereports', 'block/configurable_reports:manageownreports'], $context)) {
    throw new moodle_exception('badpermissions', 'block_configurable_reports');
}

/**
 * Class sendemail_form
 *
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 */
class sendemail_form extends moodleform {

    /**
     * Form definition
     */
    public function definition(): void {
        global $PAGE;

        $mform =& $this->_form;
        $context = $PAGE->context;
        $editoroptions = [
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'context' => $context,
        ];

        $mform->addElement('hidden', 'usersids', $this->_customdata['usersids']);
        $mform->setType('usersids', PARAM_SEQUENCE);
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('text', 'subject', get_string('email_subject', 'block_configurable_reports'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required');

        $mform->addElement('editor', 'content', get_string('email_message', 'block_configurable_reports'), null, $editoroptions);

        $buttons = [];
        $buttons[] =& $mform->createElement('submit', 'send', get_string('email_send', 'block_configurable_reports'));
        $buttons[] =& $mform->createElement('cancel');

        $mform->addGroup($buttons, 'buttons', get_string('actions'), [' '], false);
    }

}

// The users come from the report table checkboxes, and then from the hidden field of this form.
$userids = optional_param_array('userids', [], PARAM_INT);
$usersids = $userids ? implode(',', $userids) : optional_param('usersids', '', PARAM_SEQUENCE);

$form = new sendemail_form(null, [
    'usersids' => $usersids,
    'courseid' => $course->id,
]);

$returnurl = new moodle_url('/course/view.php', ['id' => $course->id]);
if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    $html = format_text($data->content['text'], $data->content['format'], ['context' => $context]);
    foreach (array_filter(explode(',', $data->usersids)) as $userid) {
        $recipient = core_user::get_user((int) $userid);
        // Only email active users, enrolled in the course when the report is not a site report.
        if (!$recipient || $recipient->deleted || $recipient->suspended ||
                ($course->id != SITEID && !is_enrolled($context, $recipient))) {
            continue;
        }
        email_to_user($recipient, $USER, $data->subject, html_to_text($html), $html);
    }
    // After emails were sent... go back to where you came from.
    redirect($returnurl);
}

$title = get_string('sendemail', 'block_configurable_reports');
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($title);

echo $OUTPUT->header();

echo html_writer::start_tag('div', ['class' => 'no-overflow']);
$form->display();
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
