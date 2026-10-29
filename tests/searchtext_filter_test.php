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
 * Unit tests for the search text filter on SQL reports
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/filter.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/searchtext/plugin.class.php');

/**
 * Tests for plugin_searchtext::execute_for_sql_report()
 *
 * @package    block_configurable_reports
 * @covers     \plugin_searchtext
 */
#[CoversClass(\plugin_searchtext::class)]
final class searchtext_filter_test extends advanced_testcase {

    /** @var \plugin_searchtext */
    private \plugin_searchtext $filter;

    /** @var int[] Ids of the users created for the tests, keyed by city */
    private array $users = [];

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->filter = new \plugin_searchtext((object) ['id' => 1, 'type' => 'sql', 'components' => '']);

        foreach (['Oberhausen', 'Coburg', 'Madrid', 'Barcelona', '0'] as $city) {
            $this->users[$city] = (int) $this->getDataGenerator()->create_user(['city' => $city])->id;
        }
    }

    protected function tearDown(): void {
        unset($_GET['filter_searchtext_city']);
        parent::tearDown();
    }

    /**
     * Apply the filter (idnumber "city") to the query and return the ids of the matching test users.
     *
     * @param string $placeholder
     * @param string $value
     * @return array [$sql, $params, $ids]
     */
    private function run_filter(string $placeholder, string $value): array {
        global $DB;

        $_GET['filter_searchtext_city'] = $value;
        $sql = "SELECT u.id FROM {user} u WHERE u.deleted = 0 {$placeholder} ORDER BY u.id";
        [$sql, $params] = $this->filter->execute_for_sql_report($sql, (object) ['idnumber' => 'city']);

        $ids = array_map('intval', array_keys($DB->get_records_sql($sql, $params)));
        return [$sql, $params, array_values(array_intersect($ids, $this->users))];
    }

    /**
     * Contains (~) wraps the value in wildcards, is case-insensitive and works with aliased columns.
     */
    public function test_contains_with_alias(): void {
        [$sql, $params, $ids] = $this->run_filter('%%FILTER_SEARCHTEXT_city:u.city:~%%', 'OBE');

        $this->assertSame([$this->users['Oberhausen']], $ids);
        $this->assertSame(['%OBE%'], array_values($params));
        foreach (array_keys($params) as $name) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $name);
        }
        $this->assertStringNotContainsString('%%', $sql);
    }

    /**
     * "in" builds an OR of LIKEs without a stray AND, trims quotes and spaces and keeps escaped commas.
     */
    public function test_in_operator(): void {
        [$sql, $params, $ids] = $this->run_filter('%%FILTER_SEARCHTEXT_city:u.city:in%%', "madrid, 'Coburg'");

        $this->assertEqualsCanonicalizing([$this->users['Madrid'], $this->users['Coburg']], $ids);
        $this->assertSame(['madrid', 'Coburg'], array_values($params));
        $this->assertStringNotContainsString('(AND', $sql);
        $this->assertStringNotContainsString('OR AND', $sql);

        [, $params] = $this->run_filter('%%FILTER_SEARCHTEXT_city:u.city:in%%', 'a\,b,c');
        $this->assertSame(['a,b', 'c'], array_values($params));
    }

    /**
     * Comparison operators bind the value to a safe param name.
     */
    public function test_equals_operator(): void {
        [$sql, $params, $ids] = $this->run_filter('%%FILTER_SEARCHTEXT_city:u.city:=%%', 'Barcelona');

        $this->assertSame([$this->users['Barcelona']], $ids);
        $this->assertSame(['Barcelona'], array_values($params));
        $this->assertStringContainsString('AND u.city = :', $sql);
    }

    /**
     * "0" is a value like any other, so it has to filter rather than read as no filter at all.
     */
    public function test_zero_is_filtered(): void {
        [$sql, $params, $ids] = $this->run_filter('%%FILTER_SEARCHTEXT_city:u.city:=%%', '0');

        $this->assertSame([$this->users['0']], $ids);
        $this->assertSame(['0'], array_values($params));
        $this->assertStringNotContainsString('%%', $sql);
    }

    /**
     * The value is always bound, never inserted into the SQL.
     */
    public function test_value_is_bound(): void {
        [$sql, $params, $ids] = $this->run_filter('%%FILTER_SEARCHTEXT_city:u.city:=%%', "x' OR '1'='1");

        $this->assertSame([], $ids);
        $this->assertStringNotContainsString("'1'='1", $sql);
        $this->assertSame(["x' OR '1'='1"], array_values($params));
    }

    /**
     * When the placeholder is not in the query the SQL is returned unchanged (not emptied).
     */
    public function test_sql_unchanged_without_placeholder(): void {
        $_GET['filter_searchtext_city'] = 'Madrid';
        $sql = 'SELECT id FROM {user}';

        [$result, $params] = $this->filter->execute_for_sql_report($sql, (object) ['idnumber' => 'city']);

        $this->assertSame($sql, $result);
        $this->assertSame([], $params);
    }

    /**
     * Without a value the placeholder is removed.
     */
    public function test_placeholder_removed_without_value(): void {
        [$sql, $params] = $this->filter->execute_for_sql_report(
            'SELECT id FROM {user} WHERE 1 = 1 %%FILTER_SEARCHTEXT_city:city:~%%',
            (object) ['idnumber' => 'city'],
        );

        $this->assertSame('SELECT id FROM {user} WHERE 1 = 1 ', $sql);
        $this->assertSame([], $params);
    }

    /**
     * Issue #295: an empty filter only removes its own placeholder, not those of other search text filters.
     */
    public function test_empty_filter_keeps_other_placeholders(): void {
        $sql = 'SELECT id FROM {user} WHERE 1 = 1 %%FILTER_SEARCHTEXT_FIRSTNAME:u.firstname:~%%'
            . ' %%FILTER_SEARCHTEXT_FIRST:u.idnumber:=%% %%FILTER_SEARCHTEXT:u.city:~%%';

        [$result, $params] = $this->filter->execute_for_sql_report($sql, (object) ['idnumber' => 'FIRST']);
        $this->assertSame('SELECT id FROM {user} WHERE 1 = 1 %%FILTER_SEARCHTEXT_FIRSTNAME:u.firstname:~%%'
            . '  %%FILTER_SEARCHTEXT:u.city:~%%', $result);
        $this->assertSame([], $params);

        // Filters without idnumber only remove the placeholder without idnumber.
        [$result] = $this->filter->execute_for_sql_report($sql, (object) []);
        $this->assertSame('SELECT id FROM {user} WHERE 1 = 1 %%FILTER_SEARCHTEXT_FIRSTNAME:u.firstname:~%%'
            . ' %%FILTER_SEARCHTEXT_FIRST:u.idnumber:=%% ', $result);
    }

    /**
     * Unknown operators are rejected.
     */
    public function test_invalid_operator(): void {
        $this->expectException(\moodle_exception::class);
        $this->run_filter('%%FILTER_SEARCHTEXT_city:u.city:!!%%', 'Madrid');
    }
}
