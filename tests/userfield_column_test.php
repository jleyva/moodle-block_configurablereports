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
 * Unit tests for the user field column
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
require_once($CFG->dirroot . '/blocks/configurable_reports/locallib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/columns/userfield/plugin.class.php');

/**
 * Tests for plugin_userfield
 *
 * @package    block_configurable_reports
 * @covers     \plugin_userfield
 */
#[CoversClass(\plugin_userfield::class)]
final class userfield_column_test extends advanced_testcase {

    /**
     * Date columns of the user table.
     *
     * @return array
     */
    public static function date_columns_provider(): array {
        return [
            'timecreated' => ['timecreated'],
            'timemodified' => ['timemodified'],
            'lastaccess' => ['lastaccess'],
        ];
    }

    /**
     * The date columns of the user table are shown as dates, not as timestamps.
     *
     * @param string $column
     */
    #[DataProvider('date_columns_provider')]
    public function test_date_columns_are_formatted(string $column): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $DB->set_field('user', $column, 1700000000, ['id' => $user->id]);

        $plugin = new \plugin_userfield((object) ['id' => 1, 'type' => 'users']);
        $value = $plugin->execute((object) ['column' => $column], $DB->get_record('user', ['id' => $user->id]));

        $this->assertSame(userdate(1700000000), $value);
    }
}
