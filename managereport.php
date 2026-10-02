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
 * @package   block_configurable_reports
 * @author    Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot . "/blocks/configurable_reports/locallib.php");
require_once('import_form.php');

$courseid = optional_param('courseid', SITEID, PARAM_INT);
$importurl = optional_param('importurl', '', PARAM_RAW);

if (!$course = $DB->get_record("course", ['id' => $courseid])) {
    throw new moodle_exception("No such course id");
}

// Force user login in course (SITE or Course).
if ($course->id == SITEID) {
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

$PAGE->set_url('/blocks/configurable_reports/managereport.php', ['courseid' => $course->id]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

if ($importurl) {
    $c = new curl();
    if ($data = $c->get($importurl)) {
        $data = json_decode($data);
        $xml = base64_decode($data->content);
    } else {
        throw new moodle_exception('errorimporting');
    }

    if (cr_import_xml($xml, $course)) {
        redirect(
            "$CFG->wwwroot/blocks/configurable_reports/managereport.php?courseid={$course->id}",
            get_string('reportcreated', 'block_configurable_reports')
        );
    } else {
        throw new moodle_exception('errorimporting');
    }
}

$mform = new import_form(null, $course->id);

if ($data = $mform->get_data()) {
    if ($xml = $mform->get_file_content('userfile')) {
        if (cr_import_xml($xml, $course)) {
            redirect(
                "$CFG->wwwroot/blocks/configurable_reports/managereport.php?courseid={$course->id}",
                get_string('reportcreated', 'block_configurable_reports')
            );
        } else {
            throw new moodle_exception('errorimporting');
        }
    }
}

$reports = cr_get_my_reports($course->id, $USER->id);

$title = get_string('reports', 'block_configurable_reports');

$PAGE->navbar->add(get_string('managereports', 'block_configurable_reports'));

$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_cacheable(true);
$jsmodule = [
    'name' => 'block_configurable_reports',
    'fullpath' => '/blocks/configurable_reports/js/configurable_reports.js',
    'requires' => ['io'],
];
$PAGE->requires->js_init_call('M.block_configurable_reports.loadReportCategories', null, false, $jsmodule);

echo $OUTPUT->header();

if ($reports) {
    $table = new stdclass;
    $table->width = "100%";
    $table->head = [
        get_string('name'),
        get_string('reportsmanage', 'admin') . ' ' . get_string('course'),
        get_string('type', 'block_configurable_reports'),
        get_string('username'),
        get_string('emailschedule', 'block_configurable_reports'),
        get_string('edit'),
        get_string('download', 'block_configurable_reports'),
    ];
    $table->align = ['left', 'left', 'left', 'left', 'left', 'center', 'center'];
    $table->size = ['25%', '10%', '10%', '10%', '15%', '15%', '15%'];
    $emailscheduleoptions = cr_get_email_schedule_options();
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $strhide = get_string('hide');
    $strshow = get_string('show');
    $strcopy = get_string('duplicate');
    $strexport = get_string('exportreport', 'block_configurable_reports');

    foreach ($reports as $r) {
        if ($r->courseid == 1) {
            $coursename = '<a href="' . $CFG->wwwroot . '">' . get_string('site') . '</a>';
        } else if (!$coursename = $DB->get_field('course', 'fullname', ['id' => $r->courseid])) {
            $coursename = get_string('deleted');
        } else {
            $coursename = format_string($coursename);
            $coursename =
                '<a href="' . $CFG->wwwroot . '/blocks/configurable_reports/managereport.php?courseid=' . $r->courseid . '">' .
                $coursename . '</a>';
        }

        if ($owneruser = $DB->get_record('user', ['id' => $r->ownerid])) {
            $owner = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $r->ownerid . '">' . fullname($owneruser) . '</a>';
        } else {
            $owner = get_string('deleted');
        }
        if ($r->type === 'sql' && !block_configurable_reports_can_managesqlreports($context)) {
            $editcell = '';
        } else {
            $editcell = '<a title="' . $stredit . '"  href="editreport.php?id=' . $r->id . '">' .
                $OUTPUT->pix_icon('t/edit', $stredit) .
                '</a>&nbsp;&nbsp;';
            $editcell .= '<a title="' . $strdelete . '"  href="editreport.php?id=' . $r->id . '&amp;delete=1&amp;sesskey=' .
                $USER->sesskey . '">' .
                $OUTPUT->pix_icon('t/delete', $strdelete) .
                '</a>&nbsp;&nbsp;';

            if (!empty($r->visible)) {
                $editcell .= '<a title="' . $strhide . '" href="editreport.php?id=' . $r->id . '&amp;hide=1&amp;sesskey=' .
                    $USER->sesskey . '">' .
                    $OUTPUT->pix_icon('t/hide', $strhide) .
                    '</a> ';
            } else {
                $editcell .= '<a title="' . $strshow . '" href="editreport.php?id=' . $r->id . '&amp;show=1&amp;sesskey=' .
                    $USER->sesskey . '">' .
                    $OUTPUT->pix_icon('t/show', $strshow) .
                    '</a> ';
            }
            $editcell .= '<a title="' . $strcopy . '" href="editreport.php?id=' . $r->id . '&amp;duplicate=1&amp;sesskey=' .
                $USER->sesskey . '">' .
                $OUTPUT->pix_icon('t/copy', $strcopy) .
                '</a>&nbsp;&nbsp;';
            $editcell .= '<a title="' . $strexport . '" href="export.php?id=' . $r->id . '&amp;sesskey=' . $USER->sesskey . '">' .
                $OUTPUT->pix_icon('t/backup', $strexport) .
                '</a>&nbsp;&nbsp;';

        }

        $download = '';
        $export = explode(',', $r->export);
        if (!empty($export)) {
            foreach ($export as $e) {
                if ($e) {
                    $download .= '<a href="viewreport.php?id=' . $r->id . '&amp;download=1&amp;format=' . $e . '">' .
                        '<img src="' . $CFG->wwwroot . '/blocks/configurable_reports/export/' . $e . '/pix.gif" alt="' . $e . '">' .
                        '&nbsp;' . (strtoupper($e)) . '</a>&nbsp;&nbsp;';
                }
            }
        }

        if (!empty($r->emailschedule)) {
            $recipientcount = count(cr_parse_email_recipients((string) $r->emailto));
            $emailstatus = '<a href="editreport.php?id=' . $r->id . '" title="' .
                s($emailscheduleoptions[$r->emailschedule]) . '">' .
                $OUTPUT->pix_icon('t/email', $emailscheduleoptions[$r->emailschedule]) . '&nbsp;' .
                $emailscheduleoptions[$r->emailschedule] . ' (' . $recipientcount . ')</a>';
        } else {
            $emailstatus = '<a href="editreport.php?id=' . $r->id . '" class="text-muted">' .
                $emailscheduleoptions[0] . '</a>';
        }

        $table->data[] = [
            '<a href="viewreport.php?id=' . $r->id . '">' . format_string($r->name) . '</a>',
            $coursename,
            get_string('report_' . $r->type, 'block_configurable_reports'),
            $owner,
            $emailstatus,
            $editcell,
            $download,
        ];
    }

    $table->id = 'reportslist';
    cr_add_jsordering("#reportslist", $PAGE);
    cr_print_table($table);

    echo html_writer::start_tag('form', [
        'action' => 'sendreportsemail.php',
        'method' => 'post',
        'class' => 'mform mt-3',
        'id' => 'cr_sendreportsemail_form',
    ]);
    echo html_writer::tag('legend', get_string('sendreportsbyemail', 'block_configurable_reports'));
    echo html_writer::tag('p', get_string('sendreportsbyemailintro', 'block_configurable_reports'), ['class' => 'text-muted']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $course->id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::start_tag('div', ['class' => 'mb-2']);
    echo html_writer::link('#', get_string('selectall'), [
        'onclick' => 'document.querySelectorAll("#cr_sendreportsemail_form input[type=checkbox]")' .
            '.forEach(function(c){c.checked=true;}); return false;',
    ]);
    echo ' / ';
    echo html_writer::link('#', get_string('deselectall'), [
        'onclick' => 'document.querySelectorAll("#cr_sendreportsemail_form input[type=checkbox]")' .
            '.forEach(function(c){c.checked=false;}); return false;',
    ]);
    echo html_writer::end_tag('div');

    foreach ($reports as $r) {
        echo html_writer::start_tag('div', ['class' => 'form-check']);
        echo html_writer::checkbox(
            'ids[]',
            $r->id,
            false,
            format_string($r->name),
            ['id' => 'cr_sendreport_' . $r->id, 'class' => 'form-check-input']
        );
        echo html_writer::end_tag('div');
    }

    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('sendreportsbyemail', 'block_configurable_reports'),
        'class' => 'btn btn-secondary mt-2',
    ]);
    echo html_writer::end_tag('form');
} else {
    echo $OUTPUT->heading(get_string('noreportsavailable', 'block_configurable_reports'));
}

// Recurring combined email schedules (bundle several reports into one recurring email).
$bundles = cr_get_my_bundles($course->id, $USER->id);
$bundleschedules = cr_get_recurring_schedule_options();
$strbundleedit = get_string('editbundle', 'block_configurable_reports');
$strbundledelete = get_string('deletebundle', 'block_configurable_reports');

echo html_writer::start_tag('div', ['class' => 'mt-4']);
echo $OUTPUT->heading(get_string('recurringemailbundles', 'block_configurable_reports'), 4);
echo html_writer::tag('p', get_string('recurringemailbundlesdesc', 'block_configurable_reports'), ['class' => 'text-muted']);

if ($bundles) {
    $bundletable = new stdclass;
    $bundletable->width = '100%';
    $bundletable->id = 'bundleslist';
    $bundletable->head = [
        get_string('name'),
        get_string('bundlereports', 'block_configurable_reports'),
        get_string('emailschedule', 'block_configurable_reports'),
        get_string('emailto', 'block_configurable_reports'),
        get_string('edit'),
    ];
    $bundletable->align = ['left', 'left', 'left', 'left', 'center'];
    $bundletable->size = ['20%', '35%', '15%', '15%', '15%'];

    foreach ($bundles as $bundle) {
        $bundlereportids = array_filter(array_map('intval', explode(',', (string) $bundle->reportids)));
        $bundlereportnames = [];
        foreach ($bundlereportids as $brid) {
            if (isset($reports[$brid])) {
                $bundlereportnames[] = format_string($reports[$brid]->name);
            }
        }

        $editurl = 'editbundle.php?id=' . $bundle->id;
        $deleteurl = 'editbundle.php?id=' . $bundle->id . '&amp;delete=1&amp;sesskey=' . $USER->sesskey;

        $bundleeditcell = '<a title="' . $strbundleedit . '" href="' . $editurl . '">' .
            $OUTPUT->pix_icon('t/edit', $strbundleedit) . '</a>&nbsp;&nbsp;';
        $bundleeditcell .= '<a title="' . $strbundledelete . '" href="' . $deleteurl . '">' .
            $OUTPUT->pix_icon('t/delete', $strbundledelete) . '</a>';

        $bundletable->data[] = [
            '<a href="' . $editurl . '">' . format_string($bundle->name) . '</a>',
            implode(', ', $bundlereportnames),
            $bundleschedules[$bundle->emailschedule] ?? '',
            (string) count(cr_parse_email_recipients((string) $bundle->emailto)),
            $bundleeditcell,
        ];
    }

    cr_print_table($bundletable);
} else {
    echo html_writer::tag('p', get_string('nobundlesyet', 'block_configurable_reports'));
}

$addbundleurl = $CFG->wwwroot . '/blocks/configurable_reports/editbundle.php?courseid=' . $course->id;
echo html_writer::tag(
    'a',
    get_string('addbundle', 'block_configurable_reports'),
    ['href' => $addbundleurl, 'class' => 'btn btn-secondary']
);
echo html_writer::end_tag('div');

$addreporturl = $CFG->wwwroot . '/blocks/configurable_reports/editreport.php?courseid=' . $course->id;
echo $OUTPUT->heading(
    '<div class="addbutton">
                       <a href="' . $addreporturl . '" class="btn btn-secondary">' .
    get_string('addreport', 'block_configurable_reports') .
    '</a> </div>'
);

// Repository report import.
if ($userandrepo = get_config('block_configurable_reports', 'crrepository')) {
    echo html_writer::start_tag('div', ['class' => 'mform']);
    echo html_writer::start_tag('fieldset');
    echo html_writer::tag('legend', get_string('importfromrepository', 'block_configurable_reports'));

    echo $OUTPUT->help_icon('repository', 'block_configurable_reports') . "&nbsp;&nbsp;";

    $reportcategories = ['' => '...'];
    echo get_string('categories', 'block_configurable_reports');

    $attrs = [
        'onchange' => 'M.block_configurable_reports.onchange_crreportcategories(this,"' . sesskey() . '")',
        'id' => 'id_crreportcategories',
    ];
    echo html_writer::select($reportcategories, 'crreportcategories', '', null, $attrs);
    echo get_string('report', 'block_configurable_reports');

    $attrs = [
        'onchange' => 'M.block_configurable_reports.onchange_crreportnames(this,"' . sesskey() . '")',
        'id' => 'id_crreportnames',
    ];
    echo html_writer::select([], 'crreportnames', '', [], $attrs);

    echo html_writer::end_tag('fieldset');
    echo html_writer::end_tag('div');
}

$mform->display();

echo $OUTPUT->footer();
