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

}
