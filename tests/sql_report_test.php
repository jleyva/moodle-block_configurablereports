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
 * Unit tests for SQL reports and the components that run their query (calcs, plots, template)
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/sql/report.class.php');

/**
 * Tests for report_sql with filters, and for components that call prepare_sql()
 *
 * @package    block_configurable_reports
 * @covers     \report_sql
 */
#[CoversClass(\report_sql::class)]
final class sql_report_test extends advanced_testcase {

    /** @var string Report query: one row per test user, filtered by city */
    private const QUERY = "SELECT u.username, u.id AS userid FROM {user} u
                            WHERE u.username LIKE 'crtest%' %%FILTER_SEARCHTEXT_city:u.city:~%%
                         ORDER BY u.username";

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_user(['username' => 'crtest1', 'city' => 'Oberhausen']);
        $this->getDataGenerator()->create_user(['username' => 'crtest2', 'city' => 'Coburg']);
        $this->getDataGenerator()->create_user(['username' => 'crtest3', 'city' => 'Madrid']);

        // The filter value is set so every component runs the query with bound params.
        $_GET['filter_searchtext_city'] = 'obe';
    }

    protected function tearDown(): void {
        unset($_GET['filter_searchtext_city']);
        parent::tearDown();
    }

    /**
     * Create a SQL report with search text filters.
     *
     * @param string $query
     * @param string[] $searchtextids Idnumbers of the search text filters.
     * @return \stdClass The report record.
     */
    private function create_report(string $query = self::QUERY, array $searchtextids = ['city']): \stdClass {
        global $DB, $SITE, $USER;

        $filters = [];
        foreach ($searchtextids as $idnumber) {
            $filters[] = ['pluginname' => 'searchtext', 'formdata' => (object) ['idnumber' => $idnumber, 'label' => $idnumber]];
        }
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
     * The report applies the filter with bound params and returns the matching rows.
     */
    public function test_create_report_with_filter(): void {
        $report = new \report_sql($this->create_report());

        $this->assertTrue($report->create_report());

        $this->assertSame(['username', 'userid'], $report->finalreport->table->head);
        $this->assertSame(['crtest1'], array_column($report->finalreport->table->data, 0));
    }

    /**
     * Issue #294: two search text filters on aliased columns failed with 'missing param "u"'.
     */
    public function test_searchtext_filters_on_aliased_columns(): void {
        $this->getDataGenerator()->create_user(['username' => 'crtest4', 'firstname' => 'Cheese', 'lastname' => 'Maker']);
        $this->getDataGenerator()->create_user(['username' => 'crtest5', 'firstname' => 'Cheesecake', 'lastname' => 'Baker']);
        $this->getDataGenerator()->create_user(['username' => 'crtest6', 'firstname' => 'Bread', 'lastname' => 'Maker']);
        $query = "SELECT u.username FROM {user} u WHERE u.username LIKE 'crtest%'
                  %%FILTER_SEARCHTEXT_FIRSTNAME:u.firstname:~%%
                  %%FILTER_SEARCHTEXT_LASTNAME:u.lastname:~%%
                  ORDER BY u.username";
        $report = new \report_sql($this->create_report($query, ['FIRSTNAME', 'LASTNAME']));

        // No filter values: all rows.
        unset($_GET['filter_searchtext_city']);
        $this->assertTrue($report->create_report());
        $this->assertCount(6, $report->finalreport->table->data);

        // One filter.
        $_GET['filter_searchtext_FIRSTNAME'] = 'cheese';
        $this->assertTrue($report->create_report());
        $this->assertSame(['crtest4', 'crtest5'], array_column($report->finalreport->table->data, 0));

        // Both filters.
        $_GET['filter_searchtext_LASTNAME'] = 'maker';
        try {
            $this->assertTrue($report->create_report());
        } finally {
            unset($_GET['filter_searchtext_FIRSTNAME'], $_GET['filter_searchtext_LASTNAME']);
        }
        $this->assertSame(['crtest4'], array_column($report->finalreport->table->data, 0));
    }

    /**
     * Issue #294: saving the custom SQL failed with a preg_match() TypeError, in both SQL security modes.
     *
     * @dataProvider security_provider
     * @param bool $highsecurity
     */
    #[DataProvider('security_provider')]
    public function test_customsql_form_validation(bool $highsecurity): void {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/configurable_reports/components/customsql/form.php');

        set_config('sqlsecurity', (int) $highsecurity, 'block_configurable_reports');
        $reportclass = new \report_sql($this->create_report());
        $form = new \customsql_form(null, ['reportclass' => $reportclass]);

        $this->assertSame([], $form->validation(['querysql' => self::QUERY, 'singlerow' => 1], []));

        // Invalid SQL is reported as a form error, not thrown.
        $errors = $form->validation(['querysql' => 'SELECT nosuchcolumn FROM {user}'], []);
        $this->assertArrayHasKey('querysql', $errors);
    }

    /**
     * Data provider for test_customsql_form_validation.
     *
     * @return array
     */
    public static function security_provider(): array {
        return ['high security' => [true], 'low security' => [false]];
    }

    /**
     * Calcs plugins run the report query to name the column.
     *
     * @dataProvider calcs_provider
     * @param string $plugin
     */
    #[DataProvider('calcs_provider')]
    public function test_calcs_summary(string $plugin): void {
        global $CFG;
        require_once($CFG->dirroot . "/blocks/configurable_reports/components/calcs/{$plugin}/plugin.class.php");

        $classname = "plugin_{$plugin}";
        $calc = new $classname($this->create_report());

        $this->assertSame('userid', $calc->summary((object) ['column' => 1]));
    }

    /**
     * Data provider for test_calcs_summary.
     *
     * @return array
     */
    public static function calcs_provider(): array {
        return [['sum'], ['average'], ['max'], ['min'], ['percent']];
    }

    /**
     * Plot and template forms run the report query to list the columns.
     *
     * @dataProvider forms_provider
     * @param string $path Form file, relative to the components folder.
     * @param string $classname
     */
    #[DataProvider('forms_provider')]
    public function test_forms_list_columns(string $path, string $classname): void {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . "/blocks/configurable_reports/components/{$path}");

        $PAGE->set_url('/blocks/configurable_reports/editcomp.php');
        $report = $this->create_report();
        $form = new $classname(null, ['report' => $report, 'compclass' => null]);

        $html = $form->render();
        $this->assertStringContainsString('username', $html);
        $this->assertStringContainsString('userid', $html);
    }

    /**
     * Data provider for test_forms_list_columns.
     *
     * @return array
     */
    public static function forms_provider(): array {
        return [
            'pie' => ['plot/pie/form.php', 'pie_form'],
            'bar' => ['plot/bar/form.php', 'bar_form'],
            'line' => ['plot/line/form.php', 'line_form'],
            'template' => ['template/form.php', 'template_form'],
        ];
    }
}
