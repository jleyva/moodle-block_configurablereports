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

    /** @var string Report query used by all tests */
    private const QUERY = 'SELECT u.id FROM {user} u WHERE u.deleted = 0 %%FILTER_VAR%% ORDER BY u.id';

    /**
     * Run prepare_sql() with the given filter_var value.
     *
     * @param string $filtervar
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
     * Valid clauses are kept, with the value bound as a parameter.
     *
     * @dataProvider valid_provider
     * @param string $filtervar
     * @param string $expectedclause Expected SQL fragment (before parameter placeholders).
     * @param array $expectedvalues Expected bound values, in order.
     */
    #[DataProvider('valid_provider')]
    public function test_valid_clauses(string $filtervar, string $expectedclause, array $expectedvalues): void {
        global $DB;
        $this->resetAfterTest();

        [$sql, $params] = $this->prepare($filtervar);

        $this->assertStringContainsString($expectedclause, $sql);
        $this->assertStringNotContainsString('%%FILTER_VAR%%', $sql);
        $this->assertSame($expectedvalues, array_values($params));

        // The resulting query must run.
        $DB->get_records_sql($sql, $params);
    }

    /**
     * Data provider for test_valid_clauses.
     *
     * @return array
     */
    public static function valid_provider(): array {
        return [
            'equals' => ['AND id = 2', 'AND id = :filtervar', ['2']],
            'quoted value' => ["AND username = 'admin'", 'AND username = :filtervar', ['admin']],
            'less or equal' => ['AND id <= 5', 'AND id <= :filtervar', ['5']],
            'not equal' => ['AND id <> 1', 'AND id <> :filtervar', ['1']],
            'alias' => ['AND u.id = 2', 'AND u.id = :filtervar', ['2']],
            'or' => ['or id = 2', 'OR id = :filtervar', ['2']],
            'like' => ["AND username LIKE 'adm%'", 'AND username LIKE :filtervar', ['adm%']],
            'not like' => ["AND username NOT LIKE 'adm%'", 'AND username NOT LIKE :filtervar', ['adm%']],
            'function' => ["AND LOWER(username) = 'admin'", 'AND LOWER(username) = :filtervar', ['admin']],
            'in' => ['AND id IN (2,3)', 'AND id IN (', ['2', '3']],
            'not in' => ["AND id NOT IN ('1', '2')", 'AND id NOT IN (', ['1', '2']],
            'between' => ['AND id BETWEEN 1 AND 5', 'AND id BETWEEN :filtervarfrom AND :filtervarto', ['1', '5']],
            'column starting with in' => ['AND institution = x', 'AND institution = :filtervar', ['x']],
            'value with sql' => ["AND username = admin' OR '1'='1", 'AND username = :filtervar', ["admin' OR '1'='1"]],
        ];
    }

    /**
     * Anything other than a plain column (or whitelisted function of one) on the left side is dropped.
     *
     * @dataProvider injection_provider
     * @param string $filtervar
     */
    #[DataProvider('injection_provider')]
    public function test_injection_rejected(string $filtervar): void {
        $this->resetAfterTest();

        [$sql, $params] = $this->prepare($filtervar);

        $this->assertSame('SELECT u.id FROM {user} u WHERE u.deleted = 0  ORDER BY u.id', $sql);
        $this->assertSame([], $params);
    }

    /**
     * Data provider for test_injection_rejected.
     *
     * @return array
     */
    public static function injection_provider(): array {
        return [
            'tautology' => ['AND 1=1 OR id = 2'],
            'union' => ['AND 1=0 UNION SELECT password FROM {user} WHERE id = 2'],
            'comment' => ['AND id -- = 2'],
            'semicolon' => ['AND id; DROP TABLE {user}; SELECT 1 WHERE id = 2'],
            'other function' => ['AND SLEEP(id) = 0'],
            'nested function' => ['AND LOWER(UPPER(username)) = x'],
            'expression' => ['AND id + 0 = 2'],
            'quoted identifier' => ['AND "id" = 2'],
            'braces' => ['AND {user}.id = 2'],
            'no conjunction' => ['id = 2'],
            'no operator' => ['AND id 2'],
            'password' => ["AND password LIKE '$2y%'"],
            'password with alias and function' => ["AND LOWER(u.password) LIKE 'a%'"],
            'secret' => ["AND u.secret = 'x'"],
        ];
    }

    /**
     * Authors can limit the columns viewers may filter on.
     */
    public function test_allowed_columns(): void {
        $this->resetAfterTest();
        $query = 'SELECT u.id FROM {user} u WHERE u.deleted = 0 %%FILTER_VAR:u.city, u.institution%% ORDER BY u.id';

        [$sql, $params] = $this->prepare("AND u.city = 'Madrid'", $query);
        $this->assertStringContainsString('AND u.city = :filtervar', $sql);
        $this->assertSame(['Madrid'], array_values($params));

        [$sql, $params] = $this->prepare("AND u.email LIKE 'a%'", $query);
        $this->assertSame('SELECT u.id FROM {user} u WHERE u.deleted = 0  ORDER BY u.id', $sql);
        $this->assertSame([], $params);
    }
}
