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
 * Send one or more configurable reports by email, to a custom list of recipients.
 *
 * @package    block_configurable_reports
 * @copyright  2027 Configurable Reports contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot . "/blocks/configurable_reports/locallib.php");

$courseid = optional_param('courseid', SITEID, PARAM_INT);
$ids = optional_param_array('ids', [], PARAM_INT);

if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new moodle_exception('nosuchcourseid', 'block_configurable_reports');
}

// Force user login in course (SITE or Course).
if ((int) $course->id === SITEID) {
    require_login();
    $context = context_system::instance();
} else {
    require_login($course->id);
    $context = context_course::instance($course->id);
}

if (!has_capability('block/configurable_reports:managereports', $context) &&
    !has_capability('block/configurable_reports:manageownreports', $context)) {
    throw new moodle_exception('badpermissions');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
}

$PAGE->set_url('/blocks/configurable_reports/sendreportsemail.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$managereporturl = new moodle_url('/blocks/configurable_reports/managereport.php', ['courseid' => $courseid]);

// Only allow reports the current user can actually see/manage in this course.
$myreports = cr_get_my_reports($courseid, $USER->id);
$ids = array_values(array_intersect($ids, array_keys($myreports)));

if (empty($ids)) {
    redirect($managereporturl, get_string('noreportsselected', 'block_configurable_reports'), null,
        \core\output\notification::NOTIFY_ERROR);
}

$selectedreports = array_intersect_key($myreports, array_flip($ids));

require_once('sendreportsemail_form.php');

$mform = new sendreportsemail_form(null, ['courseid' => $courseid, 'reports' => $selectedreports]);

if ($mform->is_cancelled()) {
    redirect($managereporturl);
} else if ($data = $mform->get_data()) {
    $reportids = array_map('intval', explode(',', $data->reportids));
    $recipients = cr_parse_email_recipients($data->emailto);

    if ($data->sendmode === CR_REPORTS_EMAIL_SEPARATE) {
        $messages = [];
        foreach ($reportids as $reportid) {
            $field = 'message_' . $reportid;
            $messages[$reportid] = !empty($data->$field) ? format_text($data->$field, FORMAT_PLAIN) : '';
        }
    } else {
        $messages = format_text($data->content['text'], $data->content['format']);
    }

    $sentcount = cr_send_reports_email($reportids, $recipients, $data->subject, $messages, $data->sendmode);

    redirect($managereporturl, get_string('reportsemailsent', 'block_configurable_reports', $sentcount), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$title = get_string('sendreportsbyemail', 'block_configurable_reports');

$PAGE->navbar->add(get_string('managereports', 'block_configurable_reports'), $managereporturl);
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$names = array_map(function ($r) {
    return format_string($r->name);
}, $selectedreports);
echo html_writer::tag('p', get_string('sendreportsbyemaildesc', 'block_configurable_reports', implode(', ', $names)));

$mform->display();

echo $OUTPUT->footer();
