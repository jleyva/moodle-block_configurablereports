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
 * Regression tests for the security review findings
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversFunction;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/sql/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/yearhebrew/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/columns/userfield/plugin.class.php');

/**
 * Regression tests for the security review findings
 *
 * @package    block_configurable_reports
 * @covers     ::cr_import_xml
 */
#[CoversFunction('cr_import_xml')]
final class security_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    protected function tearDown(): void {
        unset($_GET['filter_yearhebrew']);
        parent::tearDown();
    }

    /**
     * Create a SQL report owned by the current user.
     *
     * @param string $query
     * @param array $filters
     * @return \stdClass
     */
    private function create_sql_report(string $query, array $filters = []): \stdClass {
        global $DB, $SITE, $USER;

        $components = [
            'customsql' => ['config' => (object) ['querysql' => $query]],
            'filters' => ['elements' => $filters],
        ];
        $report = (object) [
            'courseid' => $SITE->id,
            'ownerid' => $USER->id,
            'visible' => 1,
            'name' => 'SQL test report',
            'summary' => '',
            'type' => 'sql',
            'components' => cr_serialize($components),
            'remote' => 0,
        ];
        $report->id = $DB->insert_record('block_configurable_reports', $report);

        return $DB->get_record('block_configurable_reports', ['id' => $report->id]);
    }

    /**
     * Build an import XML like the one created by export.php.
     *
     * @param string $type
     * @param array $extra Extra report fields.
     * @return string
     */
    private function build_import_xml(string $type, array $extra = []): string {
        $components = cr_serialize([
            'customsql' => ['config' => (object) ['querysql' => 'SELECT id, username FROM {user}']],
        ]);
        $fields = array_merge([
            'name' => 'Imported',
            'summary' => '',
            'type' => $type,
            'visible' => 1,
            'components' => base64_encode($components),
        ], $extra);

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<report version="2024051300">';
        foreach ($fields as $key => $value) {
            $xml .= "<$key><![CDATA[$value]]></$key>\n";
        }

        return $xml . '</report>';
    }

    /**
     * The Hebrew year filter value is bound as a parameter, so quotes cannot break out of the query.
     */
    public function test_yearhebrew_filter_is_bound(): void {
        $this->setAdminUser();
        $this->getDataGenerator()->create_user(['username' => 'crtest1', 'idnumber' => 'abc']);

        $query = "SELECT u.username FROM {user} u WHERE u.username LIKE 'crtest%' %%FILTER_YEARHEBREW:u.idnumber%%";
        $_GET['filter_yearhebrew'] = "x' UNION SELECT password FROM {user} WHERE username LIKE '";

        $plugin = new \plugin_yearhebrew($this->create_sql_report($query));
        [$sql, $params] = $plugin->execute_for_sql_report($query, new \stdClass());

        $this->assertStringNotContainsString('UNION', $sql);
        $this->assertArrayHasKey('filteryearhebrew', $params);

        $report = new \report_sql($this->create_sql_report($query, [['pluginname' => 'yearhebrew',
            'formdata' => new \stdClass()]]));
        $report->create_report();
        $this->assertEmpty($report->finalreport->table->data);
    }

    /**
     * Users without managesqlreports cannot import SQL reports.
     */
    public function test_import_sql_report_requires_capability(): void {
        global $DB, $SITE;

        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('block/configurable_reports:managereports', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        $this->setUser($user);

        try {
            cr_import_xml($this->build_import_xml('sql'), $SITE);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertSame('nosqlpermissions', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('block_configurable_reports', []));

        // Unknown or path-like report types are rejected too.
        try {
            cr_import_xml($this->build_import_xml('../../sql'), $SITE);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertSame('nosqlpermissions', $e->errorcode);
        }
        // Ignore xmlize() deprecation notices.
        $this->resetDebugging();
    }

    /**
     * Imports ignore fields that must be set by the current context, such as the owner and the course.
     */
    public function test_import_ignores_protected_fields(): void {
        global $DB, $SITE, $USER;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $xml = $this->build_import_xml('sql', ['ownerid' => 12345, 'courseid' => $course->id, 'id' => 999]);
        $this->assertTrue(cr_import_xml($xml, $SITE));
        // Ignore xmlize() deprecation notices.
        $this->resetDebugging();

        $report = $DB->get_record('block_configurable_reports', []);
        $this->assertEquals($USER->id, $report->ownerid);
        $this->assertEquals($SITE->id, $report->courseid);
        $this->assertNotEquals(999, $report->id);
    }

    /**
     * Serialized data cannot instantiate classes other than stdClass.
     */
    public function test_unserialize_only_allows_stdclass(): void {
        $data = 'a:1:{s:1:"x";O:17:"core\\task\\manager":0:{}}';

        $result = cr_unserialize($data);

        $this->assertNotInstanceOf(\core\task\manager::class, $result['x']);
    }

    /**
     * SQL report cells are cleaned, so user-authored HTML cannot run script.
     */
    public function test_sql_report_cells_are_cleaned(): void {
        $this->setAdminUser();
        $this->getDataGenerator()->create_user(['username' => 'crtest1',
            'description' => '<img src="x" onerror="alert(1)"><a href="https://moodle.org">link</a>']);

        $report = new \report_sql($this->create_sql_report(
            "SELECT u.description FROM {user} u WHERE u.username = 'crtest1'"));
        $report->create_report();

        $cell = $report->finalreport->table->data[0][0];
        $this->assertStringNotContainsString('onerror', $cell);
        $this->assertStringContainsString('<a href="https://moodle.org">link</a>', $cell);
    }

    /**
     * User field columns escape plain fields.
     */
    public function test_userfield_column_is_escaped(): void {
        global $DB;

        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user(['description' => '<img src="x" onerror="alert(1)">']);
        $row = $DB->get_record('user', ['id' => $user->id]);
        $row->city = '<script>alert(1)</script>';

        $plugin = new \plugin_userfield($this->create_sql_report('SELECT 1'));

        $this->assertStringNotContainsString('<script>', $plugin->execute((object) ['column' => 'city'], clone $row));
        $this->assertStringNotContainsString('onerror', $plugin->execute((object) ['column' => 'description'], clone $row));
    }
}
