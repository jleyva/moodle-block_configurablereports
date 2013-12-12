<?php

require_once("../../config.php");

global $USER, $CFG, $COURSE;

$confirm = optional_param('confirm', 0, PARAM_BOOL);

require_login();

if (!array_key_exists($USER->id,get_admins())) {
    echo 'Needs to an Admin for that.';
    die;
}

$PAGE->set_context(context_course::instance($COURSE->id));
$PAGE->set_url('/blocks/configurable_reports/migrate_sqlreport.php');
$PAGE->set_title("$COURSE->fullname");
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_pagelayout('course');
echo $OUTPUT->header();

if (!$confirm) {
    $formcontinue = new single_button(new moodle_url('migrate_sqlreports.php', array('confirm' => 1)), get_string('yes'));
    $formcancel = new single_button(new moodle_url($CFG->wwwroot), get_string('no'), 'get');
    echo $OUTPUT->confirm('Are you sure?', $formcontinue, $formcancel);
    echo $OUTPUT->footer();
    die;
}

echo "Migrating...<br/>";

$sql = "INSERT INTO mdl_block_configurable_reports (courseid, ownerid, visible, name, summary, type, pagination, components)
SELECT IFNULL(crr.courseid,1) AS courseid, crr.ownerid, crr.visible, crr.name, crr.summary, 'sql' AS type, crr.pagination,
CONCAT('a:1:{s:9:\"customsql\";a:1:{s:6:\"config\";' , REPLACE(crc.configdata,' ',''), '}}') AS components
FROM mdl_block_cr_component AS crc
JOIN mdl_block_cr_report AS crr ON crr.id = crc.reportid
WHERE crc.component = 'customsql'";

$MoodleDB = mysqli_connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass,$CFG->dbname);

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

mysqli_query($MoodleDB, $sql);

mysqli_close($MoodleDB);

echo 'Migrating mdl_block_cr_component and mdl_block_cr_report into <br/>NEW mdl_block_configurable_reports...<br/>DONE :-)';

echo $OUTPUT->footer();