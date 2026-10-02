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
 * Create, edit or delete a recurring combined-email bundle.
 *
 * @package    block_configurable_reports
 * @copyright  2027 Configurable Reports contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot . "/blocks/configurable_reports/locallib.php");

$id = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('courseid', SITEID, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$bundle = null;
if ($id) {
    if (!$bundle = $DB->get_record('block_configurable_reports_bundles', ['id' => $id])) {
        throw new moodle_exception('bundledoesnotexist', 'block_configurable_reports');
    }
    $courseid = $bundle->courseid;
}

if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new moodle_exception('nosuchcourseid', 'block_configurable_reports');
}

if ((int) $course->id === SITEID) {
    require_login();
    $context = context_system::instance();
} else {
    require_login($course->id);
    $context = context_course::instance($course->id);
}

$hasmanagereportcap = has_capability('block/configurable_reports:managereports', $context);
if (!$hasmanagereportcap && !has_capability('block/configurable_reports:manageownreports', $context)) {
    throw new moodle_exception('badpermissions');
}

if ($bundle && !$hasmanagereportcap && $bundle->ownerid != $USER->id) {
    throw new moodle_exception('badpermissions');
}

$PAGE->set_url('/blocks/configurable_reports/editbundle.php', ['id' => $id, 'courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$managereporturl = new moodle_url('/blocks/configurable_reports/managereport.php', ['courseid' => $courseid]);

if ($delete && confirm_sesskey()) {
    if (!$bundle) {
        throw new moodle_exception('bundledoesnotexist', 'block_configurable_reports');
    }

    if (!$confirm) {
        $title = get_string('deletebundle', 'block_configurable_reports');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        echo $OUTPUT->header();
        $message = get_string('confirmdeletebundle', 'block_configurable_reports', format_string($bundle->name));
        $optionsyes = ['id' => $bundle->id, 'delete' => 1, 'sesskey' => sesskey(), 'confirm' => 1];
        $buttoncontinue = new single_button(new moodle_url('editbundle.php', $optionsyes), get_string('yes'), 'get');
        $buttoncancel = new single_button($managereporturl, get_string('no'), 'get');
        echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
        echo $OUTPUT->footer();
        exit;
    }

    $DB->delete_records('block_configurable_reports_bundles', ['id' => $bundle->id]);
    redirect($managereporturl, get_string('bundledeleted', 'block_configurable_reports'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$myreports = cr_get_my_reports($courseid, $USER->id);

require_once('editbundle_form.php');

$customdata = ['courseid' => $courseid, 'availablereports' => $myreports];
if ($bundle) {
    $customdata['id'] = $bundle->id;
}

$mform = new editbundle_form(null, $customdata);

if ($bundle) {
    $formdata = clone $bundle;
    $formdata->reportids = array_filter(array_map('intval', explode(',', (string) $bundle->reportids)));
    // No file attachments in the message (maxfiles => 0 in the form), so a plain array is
    // enough to prefill the editor -- no need for file_prepare_standard_editor()'s draft
    // area handling, which also expects a "message_editor" field name, not "message".
    $formdata->message = ['text' => $bundle->message, 'format' => $bundle->messageformat];
    $mform->set_data($formdata);
}

if ($mform->is_cancelled()) {
    redirect($managereporturl);
} else if ($data = $mform->get_data()) {
    $record = new stdClass();
    $record->courseid = $courseid;
    $record->name = $data->name;
    $record->reportids = implode(',', array_map('intval', (array) $data->reportids));
    $record->emailto = $data->emailto;
    $record->subject = $data->subject;
    $record->message = $data->message['text'];
    $record->messageformat = $data->message['format'];
    $record->emailschedule = $data->emailschedule;

    if ($bundle) {
        $record->id = $bundle->id;
        $DB->update_record('block_configurable_reports_bundles', $record);
    } else {
        $record->ownerid = $USER->id;
        $record->lastemailtime = 0;
        $record->timecreated = time();
        $DB->insert_record('block_configurable_reports_bundles', $record);
    }

    redirect($managereporturl, get_string('bundlesaved', 'block_configurable_reports'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$title = $bundle ? get_string('editbundle', 'block_configurable_reports') : get_string('addbundle', 'block_configurable_reports');

$PAGE->navbar->add(get_string('managereports', 'block_configurable_reports'), $managereporturl);
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$mform->display();

echo $OUTPUT->footer();
