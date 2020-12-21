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
 * Configurable Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 */

// Based on Custom SQL Reports Plugin
// See http://moodle.org/mod/data/view.php?d=13&rid=2884.

if (!defined('MOODLE_INTERNAL')) {
    //  It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class customsql_form extends moodleform {

    public function definition() {
        global $DB, $CFG, $COURSE;

        $mform =& $this->_form;

        $mform->addElement('textarea', 'querysql', get_string('querysql', 'block_configurable_reports'), 'rows="35" cols="80"');
        $mform->addRule('querysql', get_string('required'), 'required', null, 'client');
        $mform->setType('querysql', PARAM_RAW);

        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();

        $mform->addElement('static', 'note', '', get_string('listofsqlreports', 'block_configurable_reports'));

        if ($userandrepo = get_config('block_configurable_reports', 'sharedsqlrepository')) {

            $github = new \block_configurable_reports\github;
            $github->set_repo($userandrepo);
            $res = $github->get('/contents');
            $res = json_decode($res);

            if (is_array($res)) {
                $reportcategories = array(get_string('choose'));
                foreach ($res as $item) {
                    if ($item->type == 'dir') {
                        $reportcategories[$item->path] = $item->path;
                    }
                }

                $reportcatstr = get_string('reportcategories', 'block_configurable_reports');
                $reportcatattrs = ['onchange' => 'M.block_configurable_reports.onchange_reportcategories(this,"'.sesskey().'")'];
                $mform->addElement('select', 'reportcategories', $reportcatstr, $reportcategories, $reportcatattrs);

                $reportsincatstr = get_string('reportsincategory', 'block_configurable_reports');
                $reportsincatattrs = ['onchange' => 'M.block_configurable_reports.onchange_reportsincategory(this,"'.sesskey().'")'];
                $mform->addElement('select', 'reportsincategory', $reportsincatstr, $reportcategories, $reportsincatattrs);

                $mform->addElement('textarea', 'remotequerysql', get_string('remotequerysql', 'block_configurable_reports'),
                    'rows="15" cols="90"');
            }
        }
    }

    public function validation($data, $files) {
        if (get_config('block_configurable_reports', 'sqlsecurity')) {
            return $this->validation_high_security($data, $files);
        } else {
            return $this->validation_low_security($data, $files);
        }
    }

    public function validation_high_security($data, $files) {
        global $DB, $CFG, $db, $USER;

        $errors = parent::validation($data, $files);

        $sql = $data['querysql'];
        $sql = trim($sql);

        // Simple test to avoid evil stuff in the SQL.
        $regex = '/\b(ALTER|CREATE|DELETE|DROP|GRANT|INSERT|INTO|TRUNCATE|UPDATE|SET|VACUUM|REINDEX|DISCARD|LOCK)\b/i';
        if (preg_match($regex, $sql)) {
            $errors['querysql'] = get_string('notallowedwords', 'block_configurable_reports');

        } else if (strpos($sql, ';') !== false) {
            // Do not allow any semicolons.
            $errors['querysql'] = get_string('nosemicolon', 'report_customsql');

        } else if ($CFG->prefix != '' && preg_match('/\b' . $CFG->prefix . '\w+/i', $sql)) {
            // Make sure prefix is prefix_, not explicit.
            $errors['querysql'] = get_string('noexplicitprefix', 'block_configurable_reports');

        } else {
            // Now try running the SQL, and ensure it runs without errors.

            $sql = $this->_customdata['reportclass']->prepare_sql($sql);
            $rs = null;
            try {
                $rs = $this->_customdata['reportclass']->execute_query($sql, 2);
            } catch (dml_read_exception $e) {
                $errors['querysql'] = get_string('queryfailed', 'block_configurable_reports', $e->error );
            }
            if ($rs && !empty($data['singlerow'])) {
                if (rs_EOF($rs)) {
                    $errors['querysql'] = get_string('norowsreturned', 'block_configurable_reports');
                }
            }

            if ($rs) {
                $rs->close();
            }
        }

        return $errors;
    }

    public function validation_low_security($data, $files) {
        global $DB, $CFG, $db, $USER;

        $errors = parent::validation($data, $files);

        $sql = $data['querysql'];
        $sql = trim($sql);

        if (preg_match('/\b(ALTER|DELETE|DROP|GRANT|TRUNCATE|UPDATE|SET|VACUUM|REINDEX|DISCARD|LOCK)\b/i', $sql)) {
            // Only allow INSERT|INTO|CREATE in low security.
            $errors['querysql'] = get_string('notallowedwords', 'block_configurable_reports');

        } else if (preg_match('/\b(INSERT|INTO|CREATE)\b/i', $sql) && empty($CFG->block_configurable_reports_enable_sql_execution)) {
            // Only allow INSERT|INTO|CREATE in low security when SQL execution is enabled in the server.
            $errors['querysql'] = get_string('notallowedwords', 'block_configurable_reports');
        } else {
            // Now try running the SQL, and ensure it runs without errors.
            $sql = $this->_customdata['reportclass']->prepare_sql($sql);
            $rs = $this->_customdata['reportclass']->execute_query($sql, 2);
            if (!$rs) {
                $errors['querysql'] = get_string('queryfailed', 'block_configurable_reports', $db->ErrorMsg());
            } else if (!empty($data['singlerow'])) {
                if (rs_EOF($rs)) {
                    $errors['querysql'] = get_string('norowsreturned', 'block_configurable_reports');
                }
            }

            if ($rs) {
                $rs->close();
            }
        }

        return $errors;
    }
}
