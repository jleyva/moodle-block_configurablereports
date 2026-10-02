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

/**
 * cr_print_js_function
 *
 * @return void
 */
function cr_print_js_function() {
    echo '<script>
        function printDiv(id) {
            let cdiv, tmpw;

            cdiv = document.getElementById(id);
            tmpw = window.open(" ", "Print");

            tmpw.document.open();
            tmpw.document.write(\'<html><body>\');
            tmpw.document.write(cdiv.innerHTML);
            tmpw.document.write(\'</body></html>\');
            tmpw.document.close();

            setTimeout(function() {
                tmpw.print();
                tmpw.close();
            }, 1000);
        }
    </script>';
}

/**
 * cr_add_jsdatatables
 *
 * @param string $cssid
 * @param moodle_page $page
 * @return void
 */
function cr_add_jsdatatables(string $cssid, moodle_page $page) {
    $data = [];
    $data['selector'] = $cssid;

    $page->requires->string_for_js('thousandssep', 'langconfig');
    $page->requires->strings_for_js(
        [
            'datatables_sortascending',
            'datatables_sortdescending',
            'datatables_first',
            'datatables_last',
            'datatables_next',
            'datatables_previous',
            'datatables_emptytable',
            'datatables_info',
            'datatables_infoempty',
            'datatables_infofiltered',
            'datatables_lengthmenu',
            'datatables_loadingrecords',
            'datatables_processing',
            'datatables_search',
            'datatables_zerorecords',
        ],
        'block_configurable_reports'
    );

    $page->requires->js_call_amd('block_configurable_reports/main', 'add_jsdatatables', [$data]);
}

/**
 * cr_add_jsordering
 *
 * @param string $cssid
 * @param moodle_page|null $page
 */
function cr_add_jsordering(string $cssid, moodle_page $page = null) {
    global $OUTPUT;

    if (!empty($page)) {
        $data = [];
        $data['selector'] = $cssid;
        if (method_exists($OUTPUT, 'image_url')) {
            $data['background'] = $OUTPUT->image_url('normal', 'block_configurable_reports')->out();
            $data['backgroundasc'] = $OUTPUT->image_url('asc', 'block_configurable_reports')->out();
            $data['backgrounddesc'] = $OUTPUT->image_url('desc', 'block_configurable_reports')->out();
        }
        $page->requires->js_call_amd('block_configurable_reports/main', 'js_order', [$data]);
    }
}

/**
 * urlencode_recursive
 *
 * @param mixed $var
 * @return array|mixed|stdClass|string
 */
function urlencode_recursive($var) {
    if (is_object($var)) {
        $newvar = new stdClass();
        $properties = get_object_vars($var);
        foreach ($properties as $property => $value) {
            $newvar->$property = urlencode_recursive($value);
        }
    } else if (is_array($var)) {
        $newvar = [];
        foreach ($var as $property => $value) {
            $newvar[$property] = urlencode_recursive($value);
        }
    } else if (is_string($var)) {
        $newvar = urlencode($var);
    } else {
        // Nulls, integers, etc.
        $newvar = $var;
    }

    return $newvar;
}

/**
 * urldecode_recursive
 *
 * @param mixed $var
 * @return mixed
 */
function urldecode_recursive($var) {
    if (is_object($var)) {
        $newvar = new stdClass();
        $properties = get_object_vars($var);
        foreach ($properties as $property => $value) {
            $newvar->$property = urldecode_recursive($value);
        }
    } else if (is_array($var)) {
        $newvar = [];
        foreach ($var as $property => $value) {
            $newvar[$property] = urldecode_recursive($value);
        }
    } else if (is_string($var)) {
        $newvar = urldecode($var);
    } else {
        $newvar = $var;
    }

    return $newvar;
}

/**
 * cr_get_my_reports
 *
 * @param int $courseid
 * @param int $userid
 * @param bool $allcourses
 * @return array
 */
function cr_get_my_reports(int $courseid, int $userid, bool $allcourses = true): array {
    global $DB;

    if ($courseid === SITEID) {
        $context = context_system::instance();
    } else {
        $context = context_course::instance($courseid);
    }

    if (has_capability('block/configurable_reports:managereports', $context, $userid)) {
        if ($courseid === SITEID && $allcourses) {
            $reports = $DB->get_records('block_configurable_reports', null, 'name ASC');
        } else {
            $reports = $DB->get_records('block_configurable_reports', ['courseid' => $courseid], 'name ASC');
        }
    } else {
        $reports = $DB->get_records_select(
            'block_configurable_reports',
            'ownerid = ? AND courseid = ? ORDER BY name ASC',
            [$userid, $courseid]
        );
    }

    return $reports;
}

/**
 * cr_serialize
 *
 * @param mixed $data
 * @return string
 */
function cr_serialize($data): string {
    return serialize(urlencode_recursive($data));
}

/**
 * Update serialized data
 *
 * @param string $data
 * @return array
 */
function cr_unserialize(string $data): array {
    // It's needed to convert the object to stdClass to avoid __PHP_Incomplete_Class error.
    $data = preg_replace('/O:6:"object"/', 'O:8:"stdClass"', $data);
    // To make SQL queries compatible with PostgreSQL it's needed to replace " to '.
    $data = preg_replace('/THEN\+%22(.+?)%22/', 'THEN+%27${1}%27', $data);
    $data = preg_replace('/%60/', '+++', $data);

    // TODO remove unserialize.
    $data = unserialize($data);

    return (array) urldecode_recursive($data);
}

/**
 * cr_check_report_permissions
 *
 * @param object $report
 * @param int $userid
 * @param context $context
 * @return mixed
 */
function cr_check_report_permissions($report, int $userid, context $context) {
    global $CFG;

    require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
    require_once($CFG->dirroot . '/blocks/configurable_reports/reports/' . $report->type . '/report.class.php');

    $classn = 'report_' . $report->type;

    return (new $classn($report->id))->check_permissions($userid, $context);
}

/**
 * cr_get_report_plugins
 *
 * @param int $courseid
 * @return array
 */
function cr_get_report_plugins(int $courseid): array {
    $pluginoptions = [];
    $context = ($courseid === SITEID) ? context_system::instance() : context_course::instance($courseid);
    $plugins = get_list_of_plugins('blocks/configurable_reports/reports');

    if ($plugins) {
        foreach ($plugins as $p) {
            if ($p === 'sql' && !block_configurable_reports_can_managesqlreports($context)) {
                continue;
            }
            $pluginoptions[$p] = get_string('report_' . $p, 'block_configurable_reports');
        }
    }

    return $pluginoptions;
}

/**
 * cr_get_export_plugins
 *
 * @return array
 */
function cr_get_export_plugins(): array {

    $plugins = get_list_of_plugins('blocks/configurable_reports/export');
    $pluginoptions = [];
    if ($plugins) {
        foreach ($plugins as $p) {
            $pluginoptions[$p] = get_string('export_' . $p, 'block_configurable_reports');
        }
    }

    return $pluginoptions;
}

/**
 * Available automatic email schedule options for a report.
 *
 * @return array
 */
function cr_get_email_schedule_options(): array {
    return [
        0 => get_string('emailschedule_disabled', 'block_configurable_reports'),
        1 => get_string('emailschedule_daily', 'block_configurable_reports'),
        2 => get_string('emailschedule_weekly', 'block_configurable_reports'),
        3 => get_string('emailschedule_monthly', 'block_configurable_reports'),
    ];
}

/**
 * Same as cr_get_email_schedule_options() but without the "do not send" option, for entities
 * (like a report bundle) that only exist to be sent on a recurring basis.
 *
 * @return array
 */
function cr_get_recurring_schedule_options(): array {
    $options = cr_get_email_schedule_options();
    unset($options[0]);
    return $options;
}

/**
 * JavaScript that progressively enhances a plain textarea (one email address per line, or
 * comma/semicolon separated) into an Outlook/Gmail-style "chip" input: typed addresses turn
 * into removable pills as you type, while the underlying textarea is kept in sync (one
 * address per line) so existing server-side parsing/validation keeps working unchanged.
 *
 * Pure vanilla JS with no AMD/build step dependency, since it just needs to progressively
 * enhance a plain form field.
 *
 * @param string $textareaid The id of the textarea element to enhance.
 * @return string JavaScript source, ready for $PAGE->requires->js_init_code().
 */
function cr_email_chip_input_js(string $textareaid): string {
    $id = json_encode($textareaid);

    return <<<JS
(function() {
    var ta = document.getElementById({$id});
    if (!ta || ta.dataset.crChipified) {
        return;
    }
    ta.dataset.crChipified = '1';
    ta.style.display = 'none';

    var wrapper = document.createElement('div');
    wrapper.className = 'cr-email-chip-input form-control';
    wrapper.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;align-items:center;' +
        'min-height:2.4em;height:auto;padding:4px;cursor:text;';
    ta.parentNode.insertBefore(wrapper, ta);

    var input = document.createElement('input');
    input.type = 'text';
    input.setAttribute('aria-label', ta.getAttribute('aria-label') || '');
    input.style.cssText = 'border:none;outline:none;flex:1 1 auto;min-width:150px;' +
        'background:transparent;';

    function syncTextarea() {
        var chips = wrapper.querySelectorAll('.cr-email-chip');
        var emails = [];
        chips.forEach(function(c) {
            emails.push(c.getAttribute('data-email'));
        });
        ta.value = emails.join('\\n');
        ta.dispatchEvent(new Event('change'));
    }

    function addChip(raw) {
        var email = raw.trim().replace(/[,;]+$/, '').trim();
        if (!email) {
            return;
        }
        var chip = document.createElement('span');
        chip.className = 'cr-email-chip badge badge-light border';
        chip.setAttribute('data-email', email);
        chip.style.cssText = 'display:inline-flex;align-items:center;background:#e9ecef;' +
            'border-radius:12px;padding:2px 4px 2px 10px;font-weight:normal;';
        chip.appendChild(document.createTextNode(email));

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.setAttribute('aria-label', 'Remove');
        remove.textContent = '\\u00d7';
        remove.style.cssText = 'margin-left:6px;border:none;background:none;cursor:pointer;' +
            'line-height:1;font-size:1rem;padding:0 4px;';
        remove.addEventListener('click', function() {
            wrapper.removeChild(chip);
            syncTextarea();
            input.focus();
        });
        chip.appendChild(remove);
        wrapper.insertBefore(chip, input);
        syncTextarea();
    }

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',' || e.key === ';' || e.key === 'Tab') {
            if (input.value.trim()) {
                e.preventDefault();
                addChip(input.value);
                input.value = '';
            }
        } else if (e.key === 'Backspace' && input.value === '') {
            var chips = wrapper.querySelectorAll('.cr-email-chip');
            if (chips.length) {
                wrapper.removeChild(chips[chips.length - 1]);
                syncTextarea();
            }
        }
    });

    input.addEventListener('blur', function() {
        if (input.value.trim()) {
            addChip(input.value);
            input.value = '';
        }
    });

    input.addEventListener('paste', function(e) {
        var text = (e.clipboardData || window.clipboardData).getData('text');
        if (text && /[,;\\n]/.test(text)) {
            e.preventDefault();
            text.split(/[\\r\\n,;]+/).forEach(function(part) {
                addChip(part);
            });
        }
    });

    wrapper.addEventListener('click', function(e) {
        if (e.target === wrapper) {
            input.focus();
        }
    });

    wrapper.appendChild(input);

    (ta.value || '').split(/[\\r\\n,;]+/).forEach(function(part) {
        part = part.trim();
        if (part) {
            addChip(part);
        }
    });
})();
JS;
}

/**
 * Split a free-text list of recipient addresses (newline/comma/semicolon separated) into
 * a clean array of unique, valid email addresses.
 *
 * @param string $emailto
 * @return array List of valid email addresses.
 */
function cr_parse_email_recipients(string $emailto): array {
    $parts = preg_split('/[\r\n,;]+/', $emailto);
    $emails = [];

    foreach ($parts as $part) {
        $email = trim($part);
        if ($email !== '' && validate_email($email)) {
            $emails[strtolower($email)] = $email;
        }
    }

    return array_values($emails);
}

/**
 * Validate a free-text list of recipient addresses, returning any invalid entries found.
 *
 * @param string $emailto
 * @return array List of invalid entries (empty when everything is valid).
 */
function cr_get_invalid_email_recipients(string $emailto): array {
    $invalid = [];

    foreach (preg_split('/[\r\n,;]+/', $emailto) as $part) {
        $email = trim($part);
        if ($email !== '' && !validate_email($email)) {
            $invalid[] = $email;
        }
    }

    return $invalid;
}

/**
 * Check whether a report's scheduled email is due to be sent, based on its
 * emailschedule frequency and the last time it was emailed.
 *
 * @param stdClass $report
 * @return bool
 */
function cr_report_email_is_due(stdClass $report): bool {
    if (empty($report->emailschedule)) {
        return false;
    }

    if (empty($report->lastemailtime)) {
        return true;
    }

    $now = time();

    switch ((int) $report->emailschedule) {
        case 1: // Daily.
            $nextdue = $report->lastemailtime + DAYSECS;
            break;
        case 2: // Weekly.
            $nextdue = $report->lastemailtime + WEEKSECS;
            break;
        case 3: // Monthly.
            $nextdue = strtotime('+1 month', $report->lastemailtime);
            break;
        default:
            return false;
    }

    return $now >= $nextdue;
}

/**
 * Run a report and prepare everything needed to email it: an HTML preview of its data
 * and (when it has any rows) a CSV file on disk ready to attach.
 *
 * The caller is responsible for deleting the returned csvpath (if any) once done.
 *
 * @param stdClass $report A record from block_configurable_reports.
 * @return stdClass Object with ->name, ->html, ->csvpath (full path or '') and ->csvfilename.
 */
function cr_generate_report_for_email(stdClass $report): stdClass {
    global $CFG, $DB, $COURSE;

    if (!$course = $DB->get_record('course', ['id' => $report->courseid])) {
        throw new moodle_exception('nosuchcourseid', 'block_configurable_reports');
    }
    $COURSE = $course;

    require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
    require_once($CFG->dirroot . '/blocks/configurable_reports/reports/' . $report->type . '/report.class.php');

    $reportclassname = 'report_' . $report->type;
    $reportclass = new $reportclassname($report);
    $reportclass->create_report();

    $result = new stdClass();
    $result->name = format_string($report->name);
    $table = $reportclass->finalreport->table ?? null;

    if (!empty($table) && !empty($table->data)) {
        $result->html = cr_table_to_html($table);

        $csvcontent = cr_table_to_csv($table);
        $tmpdir = make_temp_directory('block_configurable_reports');
        $result->csvfilename = clean_filename($result->name) . '.csv';
        $result->csvpath = $tmpdir . '/' . md5($report->id . '_' . microtime()) . '_' . $result->csvfilename;
        file_put_contents($result->csvpath, $csvcontent);
    } else {
        $result->html = html_writer::tag('p', get_string('norecordsfound', 'block_configurable_reports'));
        $result->csvpath = '';
        $result->csvfilename = '';
    }

    return $result;
}

/**
 * Generate a report and email it to its predefined recipients, as CSV attachment
 * plus an HTML preview of the data in the message body.
 *
 * @param stdClass $report A record from block_configurable_reports.
 * @return bool True if the report was generated and emailed to at least one recipient.
 */
function cr_send_scheduled_report_email(stdClass $report): bool {
    global $CFG, $DB;

    $recipients = cr_parse_email_recipients((string) $report->emailto);
    if (empty($recipients)) {
        return false;
    }

    $generated = cr_generate_report_for_email($report);

    $htmlbody = html_writer::tag('p', get_string('reportemailintro', 'block_configurable_reports', $generated->name));
    $htmlbody .= $generated->html;
    $textbody = html_to_text($htmlbody);

    $attachment = '';
    if (!empty($generated->csvpath)) {
        $attachment = str_replace($CFG->dataroot . '/', '', $generated->csvpath);
    }

    $subject = get_string('reportemailsubject', 'block_configurable_reports', $generated->name);
    $fromuser = core_user::get_noreply_user();

    $sentany = false;
    foreach ($recipients as $email) {
        $touser = cr_make_email_recipient($email);
        if (email_to_user($touser, $fromuser, $subject, $textbody, $htmlbody, $attachment, $generated->csvfilename)) {
            $sentany = true;
        }
    }

    if (!empty($generated->csvpath) && file_exists($generated->csvpath)) {
        unlink($generated->csvpath);
    }

    if ($sentany) {
        $DB->set_field('block_configurable_reports', 'lastemailtime', time(), ['id' => $report->id]);
    }

    return $sentany;
}

// Email send mode: one email per recipient, with every selected report attached
// individually (no zipping).
define('CR_REPORTS_EMAIL_COMBINED', 'combined');

// Email send mode: one independent email per recipient per report.
define('CR_REPORTS_EMAIL_SEPARATE', 'separate');

/**
 * Available "how to send" options for the ad-hoc multi-report email form.
 *
 * @return array
 */
function cr_get_reports_email_modes(): array {
    return [
        CR_REPORTS_EMAIL_COMBINED => get_string('sendmode_combined', 'block_configurable_reports'),
        CR_REPORTS_EMAIL_SEPARATE => get_string('sendmode_separate', 'block_configurable_reports'),
    ];
}

/**
 * Generate one or more reports and email them to a list of recipients, with a custom
 * subject and message. Used for ad-hoc (non-scheduled) sending of a batch of reports,
 * e.g. from the manage reports page.
 *
 * Two mutually exclusive send modes are supported:
 * - CR_REPORTS_EMAIL_COMBINED: a single email per recipient, with every report's CSV
 *   attached individually (not zipped together).
 * - CR_REPORTS_EMAIL_SEPARATE: one independent email per recipient, per report.
 *
 * @param int[] $reportids Ids of records in block_configurable_reports.
 * @param string[] $recipients Valid recipient email addresses.
 * @param string $subject Custom email subject.
 * @param string|array $messages Custom email message(s) (HTML), shown before the report
 *        previews. For CR_REPORTS_EMAIL_COMBINED, a single string shared by all reports.
 *        For CR_REPORTS_EMAIL_SEPARATE, an array keyed by report id, one message per report.
 * @param string $sendmode One of the CR_REPORTS_EMAIL_* constants.
 * @return int Number of individual emails successfully sent.
 */
function cr_send_reports_email(
    array $reportids,
    array $recipients,
    string $subject,
    $messages,
    string $sendmode = CR_REPORTS_EMAIL_COMBINED
): int {
    global $DB;

    if (empty($reportids) || empty($recipients)) {
        return 0;
    }

    $reports = $DB->get_records_list('block_configurable_reports', 'id', $reportids);
    if (empty($reports)) {
        return 0;
    }

    if ($sendmode === CR_REPORTS_EMAIL_SEPARATE) {
        return cr_send_reports_email_separately($reports, $recipients, $subject, (array) $messages);
    }

    return cr_send_reports_email_combined($reports, $recipients, $subject, (string) $messages);
}

/**
 * Send every report as its own, independent email to every recipient, each with its own
 * custom message.
 *
 * @param array $reports Records from block_configurable_reports.
 * @param string[] $recipients Valid recipient email addresses.
 * @param string $subject Custom email subject (used for every report).
 * @param array $messages Custom email message (HTML) per report id; missing/empty entries
 *        are sent with no extra message, just the report preview.
 * @return int Number of individual emails successfully sent.
 */
function cr_send_reports_email_separately(array $reports, array $recipients, string $subject, array $messages): int {
    $fromuser = core_user::get_noreply_user();
    $sentcount = 0;

    foreach ($reports as $report) {
        $generated = cr_generate_report_for_email($report);

        $messagehtml = $messages[$report->id] ?? '';
        $htmlbody = $messagehtml . $generated->html;
        $textbody = html_to_text($htmlbody);
        $reportsubject = $subject . ' - ' . $generated->name;

        $attachments = [];
        if (!empty($generated->csvpath)) {
            $attachments[] = ['path' => $generated->csvpath, 'name' => $generated->csvfilename];
        }

        foreach ($recipients as $email) {
            $touser = cr_make_email_recipient($email);
            if (cr_send_email_with_attachments($touser, $fromuser, $reportsubject, $textbody, $htmlbody, $attachments)) {
                $sentcount++;
            }
        }

        if (!empty($generated->csvpath) && file_exists($generated->csvpath)) {
            unlink($generated->csvpath);
        }
    }

    return $sentcount;
}

/**
 * Send all reports together in a single email per recipient, each report's CSV attached
 * individually (no zipping).
 *
 * @param array $reports Records from block_configurable_reports.
 * @param string[] $recipients Valid recipient email addresses.
 * @param string $subject Custom email subject.
 * @param string $messagehtml Custom email message (HTML), shown before the report previews.
 * @return int Number of individual emails successfully sent.
 */
function cr_send_reports_email_combined(array $reports, array $recipients, string $subject, string $messagehtml): int {
    $htmlbody = $messagehtml;
    $attachments = [];

    foreach ($reports as $report) {
        $generated = cr_generate_report_for_email($report);

        $htmlbody .= html_writer::tag('h4', $generated->name);
        $htmlbody .= $generated->html;

        if (!empty($generated->csvpath)) {
            $attachments[] = ['path' => $generated->csvpath, 'name' => $generated->csvfilename];
        }
    }

    $textbody = html_to_text($htmlbody);
    $fromuser = core_user::get_noreply_user();

    $sentcount = 0;
    foreach ($recipients as $email) {
        $touser = cr_make_email_recipient($email);
        if (cr_send_email_with_attachments($touser, $fromuser, $subject, $textbody, $htmlbody, $attachments)) {
            $sentcount++;
        }
    }

    foreach ($attachments as $attachment) {
        if (file_exists($attachment['path'])) {
            unlink($attachment['path']);
        }
    }

    return $sentcount;
}

/**
 * Send a single email that may carry zero, one or several file attachments, none of them
 * zipped together. email_to_user() only supports a single attachment, so for more than one
 * file this talks to the mailer directly (the same approach email_to_user() itself uses
 * under the hood).
 *
 * @param stdClass $touser Recipient, as built by cr_make_email_recipient().
 * @param stdClass $fromuser Sender.
 * @param string $subject
 * @param string $textbody Plain text body.
 * @param string $htmlbody HTML body.
 * @param array $attachments List of ['path' => full filesystem path, 'name' => filename to show].
 * @return bool
 */
function cr_send_email_with_attachments(
    stdClass $touser,
    stdClass $fromuser,
    string $subject,
    string $textbody,
    string $htmlbody,
    array $attachments = []
): bool {
    global $CFG;

    if (count($attachments) <= 1) {
        $attachment = '';
        $attachname = '';
        if (!empty($attachments)) {
            $attachment = str_replace($CFG->dataroot . '/', '', $attachments[0]['path']);
            $attachname = $attachments[0]['name'];
        }

        return (bool) email_to_user($touser, $fromuser, $subject, $textbody, $htmlbody, $attachment, $attachname);
    }

    if (!validate_email($touser->email)) {
        return false;
    }

    $mail = get_mailer();

    $mail->Subject = substr($subject, 0, 900);
    $mail->FromName = fullname($fromuser);
    $mail->From = $fromuser->email;
    $mail->Sender = $fromuser->email;
    $mail->addAddress($touser->email, fullname($touser));

    if ($htmlbody) {
        $mail->isHTML(true);
        $mail->Encoding = 'base64';
        $mail->Body = $htmlbody;
        $mail->AltBody = "\n$textbody\n";
    } else {
        $mail->isHTML(false);
        $mail->Body = "\n$textbody\n";
    }

    foreach ($attachments as $attachment) {
        $mimetype = mimeinfo('type', $attachment['name']);
        $mail->addAttachment($attachment['path'], $attachment['name'], 'base64', $mimetype);
    }

    try {
        return (bool) $mail->send();
    } catch (\Throwable $e) {
        debugging('Error sending configurable report email: ' . $e->getMessage(), DEBUG_NORMAL);
        return false;
    }
}

/**
 * Get the recurring combined-email bundles ("send these N reports together, on a
 * schedule") visible to a user in a course: all of them for managers, only their own
 * otherwise. Mirrors cr_get_my_reports().
 *
 * @param int $courseid
 * @param int $userid
 * @param bool $allcourses When true and $courseid is SITEID, managers see bundles from every course.
 * @return stdClass[] Keyed by id.
 */
function cr_get_my_bundles(int $courseid, int $userid, bool $allcourses = true): array {
    global $DB;

    $context = ($courseid === SITEID) ? context_system::instance() : context_course::instance($courseid);

    if (has_capability('block/configurable_reports:managereports', $context, $userid)) {
        if ($courseid === SITEID && $allcourses) {
            return $DB->get_records('block_configurable_reports_bundles', null, 'name ASC');
        }
        return $DB->get_records('block_configurable_reports_bundles', ['courseid' => $courseid], 'name ASC');
    }

    return $DB->get_records_select(
        'block_configurable_reports_bundles',
        'ownerid = ? AND courseid = ? ORDER BY name ASC',
        [$userid, $courseid]
    );
}

/**
 * Generate and send a recurring combined-email bundle to its recipients, then update its
 * lastemailtime. Reports the bundle references that no longer exist are silently skipped.
 *
 * @param stdClass $bundle A record from block_configurable_reports_bundles.
 * @return bool True if emailed to at least one recipient.
 */
function cr_send_scheduled_bundle_email(stdClass $bundle): bool {
    global $DB;

    $recipients = cr_parse_email_recipients((string) $bundle->emailto);
    if (empty($recipients)) {
        return false;
    }

    $reportids = array_filter(array_map('intval', explode(',', (string) $bundle->reportids)));
    if (empty($reportids)) {
        return false;
    }

    $messagehtml = format_text((string) $bundle->message, (int) $bundle->messageformat);
    $subject = (string) $bundle->subject;

    $sentcount = cr_send_reports_email($reportids, $recipients, $subject, $messagehtml, CR_REPORTS_EMAIL_COMBINED);

    if ($sentcount > 0) {
        $DB->set_field('block_configurable_reports_bundles', 'lastemailtime', time(), ['id' => $bundle->id]);
    }

    return $sentcount > 0;
}

/**
 * Build a throwaway user object suitable for email_to_user(), for a recipient that may not
 * have a Moodle account.
 *
 * @param string $email
 * @return stdClass
 */
function cr_make_email_recipient(string $email): stdClass {
    global $DB;

    if ($existing = $DB->get_record('user', ['email' => $email, 'deleted' => 0, 'suspended' => 0], '*', IGNORE_MULTIPLE)) {
        return $existing;
    }

    $user = new stdClass();
    $user->id = -1;
    $user->email = $email;
    $user->firstname = $email;
    $user->lastname = '';
    $user->firstnamephonetic = '';
    $user->lastnamephonetic = '';
    $user->middlename = '';
    $user->alternatename = '';
    $user->maildisplay = true;
    $user->mailformat = 1;
    $user->auth = 'manual';
    $user->suspended = 0;
    $user->deleted = 0;
    $user->emailstop = 0;
    $user->lang = current_language();

    return $user;
}

/**
 * Flatten a configurable reports table object into a CSV string.
 *
 * @param object $table Object with ->head and ->data, as produced by report_base::create_report().
 * @return string
 */
function cr_table_to_csv(object $table): string {
    $csvdelimiter = get_config('block_configurable_reports', 'csvdelimiter') ?: 'comma';
    $delimiters = ['comma' => ',', 'semicolon' => ';', 'colon' => ':', 'tab' => "\t", 'cfg' => ','];
    $delimiter = $delimiters[$csvdelimiter] ?? ',';

    $lines = [];

    if (!empty($table->head)) {
        $lines[] = cr_csv_row($table->head, $delimiter);
    }

    if (!empty($table->data)) {
        foreach ($table->data as $row) {
            $lines[] = cr_csv_row($row, $delimiter);
        }
    }

    return implode("\r\n", $lines);
}

/**
 * Build a single CSV row from a report row, stripping tags/formatting.
 *
 * @param array $row
 * @param string $delimiter
 * @return string
 */
function cr_csv_row(array $row, string $delimiter): string {
    $cells = [];

    foreach ($row as $item) {
        $value = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br(format_string((string) $item)))));
        $cells[] = '"' . str_replace('"', '""', $value) . '"';
    }

    return implode($delimiter, $cells);
}

/**
 * Render a configurable reports table object (head/data) as a plain, self-contained HTML
 * table suitable for embedding in an email body (no forms or JS, unlike cr_print_table()).
 *
 * @param object $table Object with ->head and ->data, as produced by report_base::create_report().
 * @return string
 */
function cr_table_to_html(object $table): string {
    $htmltable = new html_table();
    $htmltable->head = $table->head ?? [];
    $htmltable->data = $table->data ?? [];
    $htmltable->attributes['class'] = 'generaltable';

    return html_writer::table($htmltable);
}

/**
 * cr_print_table
 *
 * @param object $table
 * @param bool $return
 * @return string|true
 */
function cr_print_table(object $table, bool $return = false) {
    global $COURSE;

    $output = '';

    if (isset($table->align)) {
        foreach ($table->align as $key => $aa) {
            if ($aa) {
                $align[$key] = ' text-align:' . fix_align_rtl($aa) . ';';  // Fix for RTL languages.
            } else {
                $align[$key] = '';
            }
        }
    }
    if (isset($table->size)) {
        foreach ($table->size as $key => $ss) {
            if ($ss) {
                $size[$key] = ' width:' . $ss . ';';
            } else {
                $size[$key] = '';
            }
        }
    }
    if (isset($table->wrap)) {
        foreach ($table->wrap as $key => $ww) {
            if ($ww) {
                $wrap[$key] = ' white-space:nowrap;';
            } else {
                $wrap[$key] = '';
            }
        }
    }

    if (empty($table->width)) {
        $table->width = '80%';
    }

    if (empty($table->tablealign)) {
        $table->tablealign = 'center';
    }

    if (!isset($table->cellpadding)) {
        $table->cellpadding = '5';
    }

    if (!isset($table->cellspacing)) {
        $table->cellspacing = '1';
    }

    if (empty($table->class)) {
        $table->class = 'generaltable';
    }

    $tableid = empty($table->id) ? '' : 'id="' . $table->id . '"';
    $output .= '<form action="send_emails.php" method="post" id="sendemail">';
    $output .= '<table width="' . $table->width . '" ';
    if (!empty($table->summary)) {
        $output .= " summary=\"$table->summary\"";
    }
    $output .= " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\"
                class=\"$table->class boxalign$table->tablealign\" $tableid>\n";

    $countcols = 0;
    $isuserid = -1;

    if (!empty($table->head)) {
        $countcols = count($table->head);
        $output .= '<thead><tr>';
        $keys = array_keys($table->head);
        $lastkey = end($keys);
        foreach ($table->head as $key => $heading) {
            if ($heading === 'sendemail') {
                $isuserid = $key;
            }
            if (!isset($size[$key])) {
                $size[$key] = '';
            }
            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if ($key == $lastkey) {
                $extraclass = ' lastcol';
            } else {
                $extraclass = '';
            }

            $output .= '<th style="vertical-align:top;' . $align[$key] . $size[$key] . ';white-space:normal;" class="header c' .
                $key . $extraclass . '" scope="col">' . $heading . '</th>';
        }
        $output .= '</tr></thead>' . "\n";
    }

    if (!empty($table->data)) {
        $oddeven = 1;
        $keys = array_keys($table->data);
        $lastrowkey = end($keys);
        foreach ($table->data as $key => $row) {
            $oddeven = $oddeven ? 0 : 1;
            if (!isset($table->rowclass[$key])) {
                $table->rowclass[$key] = '';
            }

            if ($key == $lastrowkey) {
                $table->rowclass[$key] .= ' lastrow';
            }

            $output .= '<tr class="r' . $oddeven . ' ' . $table->rowclass[$key] . '">' . "\n";
            if ($row === 'hr' && $countcols) {
                $output .= '<td colspan="' . $countcols . '"><div class="tabledivider"></div></td>';
            } else {  // It's a normal row of data.
                $keys2 = array_keys($row);
                $lastkey = end($keys2);

                foreach ($row as $keyouter => $item) {
                    if (!isset($size[$keyouter])) {
                        $size[$keyouter] = '';
                    }
                    if (!isset($align[$keyouter])) {
                        $align[$keyouter] = '';
                    }
                    if (!isset($wrap[$keyouter])) {
                        $wrap[$keyouter] = '';
                    }
                    if ($keyouter == $lastkey) {
                        $extraclass = ' lastcol';
                    } else {
                        $extraclass = '';
                    }
                    if ($keyouter == $isuserid) {
                        $output .= '<td style="' . $align[$keyouter] . $size[$keyouter] . $wrap[$keyouter] . '" class="cell c' .
                            $keyouter .
                            $extraclass . '"><input name="userids[]" type="checkbox" value="' . s($item) . '"></td>';
                    } else {
                        $output .= '<td style="' . $align[$keyouter] . $size[$keyouter] . $wrap[$keyouter] . '" class="cell c' .
                            $keyouter .
                            $extraclass . '">' . $item . '</td>';
                    }

                }
            }
            $output .= '</tr>' . "\n";
        }
    }
    $output .= '</table>' . "\n";
    $output .= '<input type="hidden" name="courseid" value="' . $COURSE->id . '">';
    if ($isuserid != -1) {
        $output .= '<input type="submit" value="send emails">';
    }
    $output .= '</form>';

    if ($return) {
        return $output;
    }

    echo $output;

    return true;
}

/**
 * table_to_excel
 *
 * @param string $filename
 * @param object $table
 * @return void
 */
function table_to_excel(string $filename, $table) {
    global $CFG;

    require_once($CFG->dirroot . '/lib/excellib.class.php');

    if (!empty($table->head)) {
        foreach ($table->head as $key => $heading) {
            $matrix[0][$key] = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($heading))));
        }
    }

    if (!empty($table->data)) {
        foreach ($table->data as $rkey => $row) {
            foreach ($row as $key => $item) {
                $matrix[$rkey + 1][$key] = str_replace("\n", ' ', htmlspecialchars_decode(strip_tags(nl2br($item))));
            }
        }
    }

    $downloadfilename = clean_filename($filename);
    // Creating a workbook.
    $workbook = new MoodleExcelWorkbook("-");
    // Sending HTTP headers.
    $workbook->send($downloadfilename);

    // Adding the worksheet.
    $myxls = $workbook->add_worksheet($filename);

    foreach ($matrix as $ri => $col) {
        foreach ($col as $ci => $cv) {
            $myxls->write_string($ri, $ci, $cv);
        }
    }

    $workbook->close();
    exit;
}

/**
 * Returns contexts in deprecated and current modes
 *
 * @param int $context The context
 * @param int|null $id The context id
 * @param null $strictness The flags to be used
 * @return stdClass     An object instance
 */
function cr_get_context(int $context, ?int $id = null, $strictness = null) {

    if ($context === CONTEXT_SYSTEM) {
        return context_system::instance();
    }

    if ($context === CONTEXT_COURSE) {
        return context_course::instance($id, $strictness);
    }

    if ($context === CONTEXT_COURSECAT) {
        return context_coursecat::instance($id, $strictness);
    }

    if ($context === CONTEXT_BLOCK) {
        return context_block::instance($id, $strictness);
    }

    if ($context === CONTEXT_MODULE) {
        return context_module::instance($id, $strictness);
    }

    if ($context === CONTEXT_USER) {
        return context_user::instance($id, $strictness);
    }

    return get_context_instance($context, $id, $strictness);
}

/**
 * cr_make_categories_list
 *
 * @param array $list
 * @param array $parents
 * @param string $requiredcapability
 * @param int $excludeid
 * @param object $category
 * @param string $path
 * @return void
 */
function cr_make_categories_list(&$list, &$parents, $requiredcapability = '', $excludeid = 0, $category = null, $path = '') {
    global $DB;

    // For categories list use just this one function.
    if (empty($list)) {
        $list = [];
    }

    $list += core_course_category::make_categories_list($requiredcapability, $excludeid);

    // Building the list of all parents of all categories in the system is highly undesirable and hardly ever needed.
    // Usually user needs only parents for one particular category, in which case should be used:
    // coursecat::get($categoryid)->get_parents().
    if (empty($parents)) {
        $parents = [];
    }
    $all = $DB->get_records_sql('SELECT id, parent FROM {course_categories} ORDER BY sortorder');
    foreach ($all as $record) {
        if ($record->parent) {
            $parents[$record->id] = array_merge($parents[$record->parent], [$record->parent]);
        } else {
            $parents[$record->id] = [];
        }
    }
}

/**
 * cr_import_xml
 *
 * @param string $xml
 * @param object $course
 * @return bool
 */
function cr_import_xml(string $xml, object $course) {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/lib/xmlize.php');
    $data = xmlize($xml, 1, 'UTF-8');

    if (isset($data['report']['@']['version'])) {
        $newreport = new stdclass;
        foreach ($data['report']['#'] as $key => $val) {
            if ($key === 'components') {
                $val[0]['#'] = base64_decode(trim($val[0]['#']));
                // Fix url_encode " and ' when importing SQL queries.
                $tempcomponents = cr_unserialize($val[0]['#']);

                if (array_key_exists('customsql', $tempcomponents)) {

                    // Set current courseid.
                    $tempcomponents['customsql']['config']->courseid = $course->id;
                    $querysql = str_replace(["\'", '\"'], ["'", '"'], $tempcomponents['customsql']['config']->querysql);
                    $tempcomponents['customsql']['config']->querysql = $querysql;
                }

                $val[0]['#'] = cr_serialize($tempcomponents);
            }
            $newreport->{$key} = trim($val[0]['#']);
        }
        $newreport->courseid = $course->id;
        $newreport->ownerid = $USER->id;
        $newreport->name .= " (" . userdate(time()) . ")";

        if (!$DB->insert_record('block_configurable_reports', $newreport)) {
            return false;
        }

        return true;
    }

    return false;
}

/**
 * cr_logging_info
 *
 * @return array
 */
function cr_logging_info(): array {

    static $uselegacyreader;
    static $useinternalreader;
    static $logtable;

    if (isset($uselegacyreader, $useinternalreader, $logtable)) {
        return [
            $uselegacyreader,
            $useinternalreader,
            $logtable,
        ];
    }

    $uselegacyreader = false; // Flag to determine if we should use the legacy reader.
    $useinternalreader = false; // Flag to determine if we should use the internal reader.
    $logtable = '';

    // Get list of readers.
    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers();

    // Get preferred reader.
    if (!empty($readers)) {
        foreach ($readers as $readerpluginname => $reader) {
            // If legacy reader is preferred reader.
            if ($readerpluginname == 'logstore_legacy') {
                $uselegacyreader = true;
                $logtable = 'log';
            }

            // If sql_internal_table_reader is preferred reader.
            if ($reader instanceof \core\log\sql_internal_table_reader || $reader instanceof \core\log\sql_internal_reader) {
                $useinternalreader = true;
                $logtable = $reader->get_internal_log_table_name();
            }
        }
    }

    return [
        $uselegacyreader,
        $useinternalreader,
        $logtable,
    ];
}

/**
 * Check if the current user is allowed to manage sql reports.
 *
 * @param context $context
 * @return bool
 */
function block_configurable_reports_can_managesqlreports($context): bool {
    global $USER;
    if (has_capability('block/configurable_reports:managesqlreports', $context)) {
        $allowedusers = get_config('block_configurable_reports', 'allowedsqlusers');
        if (!empty($allowedusers)) {
            $allowedusers = explode(',', $allowedusers);
            if (in_array($USER->username, $allowedusers)) {
                return true;
            }
        } else {
            return true;
        }
    }

    return false;
}
