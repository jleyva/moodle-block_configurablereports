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
 * @package    block_configurable_reports
 * @copyright  2027 Configurable Reports contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form to send one or more reports by email, to a custom list of recipients, with
 * a custom subject and message.
 *
 * @package   block_configurable_reports
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sendreportsemail_form extends moodleform {

    /**
     * Form definition
     */
    public function definition(): void {
        global $PAGE;

        $mform =& $this->_form;
        /** @var stdClass[] $reports Selected report records, keyed by id. */
        $reports = $this->_customdata['reports'];
        $reportids = array_keys($reports);

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'reportids', implode(',', $reportids));
        $mform->setType('reportids', PARAM_SEQUENCE);

        $mform->addElement(
            'select',
            'sendmode',
            get_string('sendmode', 'block_configurable_reports'),
            cr_get_reports_email_modes()
        );
        $mform->addHelpButton('sendmode', 'sendmode', 'block_configurable_reports');
        $mform->setDefault('sendmode', CR_REPORTS_EMAIL_COMBINED);

        $mform->addElement(
            'textarea',
            'emailto',
            get_string('emailto', 'block_configurable_reports'),
            ['rows' => 3, 'cols' => 50, 'id' => 'id_emailto']
        );
        $mform->setType('emailto', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('emailto', 'emailto', 'block_configurable_reports');
        $mform->addRule('emailto', null, 'required', null, 'client');

        $mform->addElement('text', 'subject', get_string('email_subject', 'block_configurable_reports'), ['size' => 60]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        // Combined mode: one shared message for all selected reports.
        $editoroptions = [
            'trusttext' => true,
            'subdirs' => 0,
            'maxfiles' => 0,
        ];
        $mform->addElement('editor', 'content', get_string('email_message', 'block_configurable_reports'), null, $editoroptions);
        $mform->setType('content', PARAM_RAW);
        $mform->hideIf('content', 'sendmode', 'eq', CR_REPORTS_EMAIL_SEPARATE);

        // Separate mode: one independent message per selected report.
        foreach ($reports as $report) {
            $fieldname = 'message_' . $report->id;
            $mform->addElement(
                'textarea',
                $fieldname,
                get_string('email_message_for', 'block_configurable_reports', format_string($report->name)),
                ['rows' => 3, 'cols' => 50]
            );
            $mform->setType($fieldname, PARAM_RAW_TRIMMED);
            $mform->hideIf($fieldname, 'sendmode', 'eq', CR_REPORTS_EMAIL_COMBINED);
        }

        $this->add_action_buttons(true, get_string('email_send', 'block_configurable_reports'));

        $PAGE->requires->js_init_code(cr_email_chip_input_js('id_emailto'), true);
    }

    /**
     * Validate the recipients field.
     *
     * @param array $data
     * @param array $files
     * @return array Errors, keyed by element name.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $emailto = trim((string) ($data['emailto'] ?? ''));

        if ($emailto === '') {
            $errors['emailto'] = get_string('emailtorequired', 'block_configurable_reports');
        } else {
            $invalid = cr_get_invalid_email_recipients($emailto);
            if (!empty($invalid)) {
                $errors['emailto'] = get_string('invalidemail', 'block_configurable_reports', implode(', ', $invalid));
            }
        }

        return $errors;
    }

}
