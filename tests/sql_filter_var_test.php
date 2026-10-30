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
     * @dataProvider value_provider
     * @param string $filtervar
     * @param mixed $expected The bound value.
     */
    #[DataProvider('value_provider')]
    public function test_value_is_bound(string $filtervar, $expected): void {
        global $DB;
        $this->resetAfterTest();

        [$sql, $params] = $this->prepare($filtervar);

        $this->assertSame('SELECT u.id FROM {user} u WHERE u.id = :filtervar1 ORDER BY u.id', $sql);
        $this->assertSame(['filtervar1' => $expected], $params);

        // The query runs, and SQL in the value is just a value that matches nothing.
        $DB->get_records_sql($sql, $params);
    }

    /**
     * Data provider for test_value_is_bound.
     *
     * @return array
     */
    public static function value_provider(): array {
        return [
            'integer' => ['2', 2],
            'negative' => [' -5 ', -5],
            'decimal' => ['1.5', 1.5],
            'text' => ['admin', 'admin'],
            'tautology' => ['2 OR 1=1', '2 OR 1=1'],
            'union' => ['0 UNION SELECT password FROM {user}', '0 UNION SELECT password FROM {user}'],
            'quote' => ["admin' OR '1'='1", "admin' OR '1'='1"],
            'semicolon' => ['2; DROP TABLE {user}', '2; DROP TABLE {user}'],
            'comment' => ['2 -- x', '2 -- x'],
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
}
