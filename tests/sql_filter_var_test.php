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
 * Unit tests for the %%FILTER_VAR%% placeholder of SQL reports
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/sql/report.class.php');

/**
 * Tests for report_sql::prepare_sql() handling of filter_var
 *
 * @package    block_configurable_reports
 * @covers     \report_sql::prepare_sql
 */
#[CoversMethod(\report_sql::class, 'prepare_sql')]
final class sql_filter_var_test extends advanced_testcase {

    /** @var string Report query used by most tests */
    private const QUERY = 'SELECT u.id FROM {user} u WHERE u.id = %%FILTER_VAR%% ORDER BY u.id';

    /**
     * Run prepare_sql() with the given filter_var value.
     *
     * @param string $filtervar
     * @param string $query
     * @return array [$sql, $params]
     */
    private function prepare(string $filtervar, string $query = self::QUERY): array {
        global $SITE;

        $_GET['filter_var'] = $filtervar;

        $config = (object) [
            'id' => 0,
            'courseid' => $SITE->id,
            'remote' => 0,
            'type' => 'sql',
            'components' => serialize([]),
        ];
        $report = new \report_sql($config);

        try {
            return $report->prepare_sql($query);
        } finally {
            unset($_GET['filter_var']);
        }
    }

    /**
     * The value is always bound as a parameter, and numbers are bound as numbers.
     *
     * Each value is compared with an expression of a matching type, as PostgreSQL refuses comparing a number
     * column with text: in a report that only means the report author has to compare like with like.
     *
     * @dataProvider value_provider
     * @param string $expression The left side of the comparison.
     * @param string $filtervar
     * @param mixed $expected The bound value.
     */
    #[DataProvider('value_provider')]
    public function test_value_is_bound(string $expression, string $filtervar, $expected): void {
        global $DB;
        $this->resetAfterTest();

        $query = "SELECT u.id FROM {user} u WHERE $expression = %%FILTER_VAR%% ORDER BY u.id";
        [$sql, $params] = $this->prepare($filtervar, $query);

        $this->assertSame("SELECT u.id FROM {user} u WHERE $expression = :filtervar1 ORDER BY u.id", $sql);
        $this->assertSame(['filtervar1' => $expected], $params);

        // The query runs, and SQL in the value is just a value that matches nothing.
        $this->assertSame([], $DB->get_records_sql($sql, $params));
    }

    /**
     * Data provider for test_value_is_bound.
     *
     * @return array
     */
    public static function value_provider(): array {
        return [
            'integer' => ['u.id', '-2', -2],
            'negative' => ['u.id', ' -5 ', -5],
            'decimal' => ['u.id * 1.0', '1.5', 1.5],
            'text' => ['u.username', 'nobody', 'nobody'],
            'tautology' => ['u.username', 'x OR 1=1', 'x OR 1=1'],
            'union' => ['u.username', "x' UNION SELECT password FROM {user} --", "x' UNION SELECT password FROM {user} --"],
            'quote' => ['u.username', "admin' OR '1'='1", "admin' OR '1'='1"],
            'semicolon' => ['u.username', 'x; DROP TABLE {user}', 'x; DROP TABLE {user}'],
            'comment' => ['u.username', 'x -- y', 'x -- y'],
        ];
    }

    /**
     * Typical uses: a value in a comparison and in arithmetic.
     */
    public function test_typical_uses(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        [$sql, $params] = $this->prepare((string) $USER->id);
        $this->assertEquals([$USER->id], array_keys($DB->get_records_sql($sql, $params)));

        $query = 'SELECT u.id FROM {user} u WHERE u.id = ' . $USER->id .
            ' AND (u.timecreated - %%FILTER_VAR%% * 86400) < u.timecreated';
        [$sql, $params] = $this->prepare('7', $query);
        $this->assertStringContainsString(':filtervar1 * 86400', $sql);
        $this->assertEquals([$USER->id], array_keys($DB->get_records_sql($sql, $params)));
    }

    /**
     * Every placeholder gets its own parameter, as Moodle does not allow repeating a named parameter.
     */
    public function test_several_placeholders(): void {
        global $DB;
        $this->resetAfterTest();

        $query = 'SELECT u.id FROM {user} u WHERE u.id = %%FILTER_VAR%% OR u.id = %%FILTER_VAR%%';
        [$sql, $params] = $this->prepare('2', $query);

        $this->assertSame('SELECT u.id FROM {user} u WHERE u.id = :filtervar1 OR u.id = :filtervar2', $sql);
        $this->assertSame(['filtervar1' => 2, 'filtervar2' => 2], $params);
        $DB->get_records_sql($sql, $params);
    }

    /**
     * CONTRIB-10669: no filter_var is not an error. A placeholder used as a value is NULL, anywhere else it is removed.
     */
    public function test_no_value_is_not_an_error(): void {
        global $DB;
        $this->resetAfterTest();

        [$sql, $params] = $this->prepare('');
        $this->assertSame('SELECT u.id FROM {user} u WHERE u.id = NULL ORDER BY u.id', $sql);
        $this->assertSame([], $params);
        $this->assertSame([], $DB->get_records_sql($sql, $params));

        $query = 'SELECT u.id FROM {user} u WHERE u.deleted = 0 %%FILTER_VAR%% ORDER BY u.id';
        [$sql, $params] = $this->prepare('', $query);
        $this->assertSame('SELECT u.id FROM {user} u WHERE u.deleted = 0  ORDER BY u.id', $sql);
        $DB->get_records_sql($sql, $params);
    }

    /**
     * A value the database refuses for the comparison, such as text against a number on PostgreSQL, says so.
     */
    public function test_value_of_the_wrong_type(): void {
        global $DB, $SITE, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        if ($DB->get_dbfamily() !== 'postgres') {
            $this->markTestSkipped('Only PostgreSQL refuses comparing a number with text.');
        }

        $components = ['customsql' => ['config' => (object) ['querysql' => self::QUERY]]];
        $report = (object) ['courseid' => $SITE->id, 'ownerid' => $USER->id, 'visible' => 1, 'name' => 'Report',
            'type' => 'sql', 'components' => cr_serialize($components), 'remote' => 0];
        $report->id = $DB->insert_record('block_configurable_reports', $report);

        $_GET['filter_var'] = 'abc';
        try {
            (new \report_sql($report->id))->create_report();
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertSame('invalidfiltervar', $e->errorcode);
        } finally {
            unset($_GET['filter_var']);
        }
    }
}
