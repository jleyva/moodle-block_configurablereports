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
 * Steps definitions for the configurable reports block.
 *
 * @package    block_configurable_reports
 * @category   test
 * @copyright  2026 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Steps definitions for the configurable reports block.
 *
 * @package    block_configurable_reports
 * @category   test
 * @copyright  2026 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_configurable_reports extends behat_base {

    /**
     * Sets the query of an SQL report.
     *
     * The query textarea is enhanced with CodeMirror, which hides the real field and copies its own
     * contents over it on submit, so with JavaScript on the value has to go into the editor instead.
     *
     * @Given /^I set the report SQL query to "(?P<query_string>(?:[^"]|\\")*)"$/
     * @param string $query The SQL query.
     */
    public function i_set_the_report_sql_query_to(string $query): void {
        $query = str_replace('\\"', '"', $query);

        if (!$this->running_javascript()) {
            $this->execute('behat_forms::i_set_the_field_to', ['SQL Query', $query]);
            return;
        }

        $this->execute_script('
            (function(value) {
                var textarea = document.getElementById("id_querysql");
                var wrapper = textarea ? textarea.nextElementSibling : null;
                if (wrapper && wrapper.CodeMirror) {
                    wrapper.CodeMirror.setValue(value);
                } else if (textarea) {
                    textarea.value = value;
                }
            })(' . json_encode($query) . ');
        ');
    }

    /**
     * Converts a page description into a URL.
     *
     * Recognised page names are:
     * | Page type            | Identifier                             | Page                                  |
     * | component            | Report name > component               | The editor for one report component   |
     * | component in course  | Report name > component > shortname   | The same, with a crafted course id    |
     * | plugin               | Report name > component > plugin      | Adding a plugin, without a sesskey    |
     * | view with debug      | Report name                           | The report, asking for the query dump |
     * | edit in admin mode   | Report name                           | The report settings, in admin mode    |
     * | remote import        | Course shortname > url                | A remote import, without a sesskey    |
     *
     * @param string $type The type of page.
     * @param string $identifier Identifies the page.
     * @return moodle_url The page URL.
     */
    public function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        $parts = array_map('trim', explode('>', $identifier));

        switch (strtolower($type)) {
            case 'component':
                $report = $this->get_report($parts[0]);
                return new moodle_url('/blocks/configurable_reports/editcomp.php',
                    ['id' => $report->id, 'comp' => $parts[1]]);

            case 'component in course':
                $report = $this->get_report($parts[0]);
                return new moodle_url('/blocks/configurable_reports/editcomp.php',
                    ['id' => $report->id, 'comp' => $parts[1], 'courseid' => $this->get_course_id($parts[2])]);

            case 'plugin':
                $report = $this->get_report($parts[0]);
                return new moodle_url('/blocks/configurable_reports/editplugin.php',
                    ['id' => $report->id, 'comp' => $parts[1], 'pname' => $parts[2]]);

            case 'view with debug':
                $report = $this->get_report($parts[0]);
                return new moodle_url('/blocks/configurable_reports/viewreport.php',
                    ['id' => $report->id, 'courseid' => $report->courseid, 'debug' => 1]);

            case 'edit in admin mode':
                $report = $this->get_report($parts[0]);
                return new moodle_url('/blocks/configurable_reports/editreport.php',
                    ['id' => $report->id, 'courseid' => $report->courseid, 'adminmode' => 1]);

            case 'remote import':
                return new moodle_url('/blocks/configurable_reports/managereport.php',
                    ['courseid' => $this->get_course_id($parts[0]), 'importurl' => $parts[1]]);

            default:
                throw new Exception('Unrecognised configurable reports page type "' . $type . '."');
        }
    }

    /**
     * Returns the report with the given name.
     *
     * @param string $name The report name.
     * @return stdClass The report record.
     */
    protected function get_report(string $name): stdClass {
        global $DB;

        return $DB->get_record('block_configurable_reports', ['name' => $name], '*', MUST_EXIST);
    }

    /**
     * Returns the id of the course with the given short name.
     *
     * @param string $shortname The course short name.
     * @return int The course id.
     */
    protected function get_course_id(string $shortname): int {
        global $DB;

        return (int) $DB->get_field('course', 'id', ['shortname' => $shortname], MUST_EXIST);
    }

    /**
     * Sets a form field to the id of the given course.
     *
     * Course ids are not known when the feature file is written, so they cannot be typed into it.
     *
     * @Given /^I set the "(?P<field_string>(?:[^"]|\\")*)" field to the id of course "(?P<shortname_string>(?:[^"]|\\")*)"$/
     * @param string $field The field label.
     * @param string $shortname The course short name.
     */
    public function i_set_the_field_to_the_id_of_course(string $field, string $shortname): void {
        $this->execute('behat_forms::i_set_the_field_to', [$field, $this->get_course_id($shortname)]);
    }

    /**
     * Visits one of the pages above and checks that it refuses access.
     *
     * The framework fails any step that leaves an exception on the page, so visiting and checking
     * have to happen in one step: the error is cleared here, before the check that runs after every
     * step would see it.
     *
     * @Then /^the "(?P<identifier_string>(?:[^"]|\\")*)" "(?P<page_string>[^"]*)" page should show the error "(?P<text_string>(?:[^"]|\\")*)"$/
     * @param string $identifier Identifies the page.
     * @param string $page The page type, as in the table above.
     * @param string $text The text the error is expected to contain.
     */
    public function the_page_should_show_the_error(string $identifier, string $page, string $text): void {
        // The step is written with the full page name, as the core steps are.
        $page = preg_replace('/^\s*block_configurable_reports\s*>\s*/', '', $page);

        $url = $this->resolve_page_instance_url($page, $identifier);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));

        $error = $this->getSession()->getPage()->find('css', '[data-rel="fatalerror"]');

        if (!$error) {
            throw new ExpectationException('The page does not refuse access', $this->getSession());
        }

        $message = $error->getText();

        $this->execute_script('
            document.querySelectorAll(\'[data-rel="fatalerror"],[data-rel="debugging"],[data-rel="phpdebugmessage"]\')
                .forEach(function(node) {
                    node.remove();
                });
            var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
            var backtrace = [];
            while (walker.nextNode()) {
                if (walker.currentNode.nodeValue.indexOf(": call to ") !== -1) {
                    backtrace.push(walker.currentNode);
                }
            }
            backtrace.forEach(function(node) {
                node.nodeValue = "";
            });
        ');

        if (strpos($message, $text) === false) {
            throw new ExpectationException(
                'The error does not say "' . $text . '", it says "' . $message . '"',
                $this->getSession()
            );
        }
    }
}
