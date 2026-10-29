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
 * Unit tests for the user field ordering plugin
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
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/ordering/userfieldorder/plugin.class.php');

/**
 * Tests for plugin_userfieldorder::summary()
 *
 * @package    block_configurable_reports
 * @covers     \plugin_userfieldorder
 */
#[CoversClass(\plugin_userfieldorder::class)]
final class userfieldorder_test extends advanced_testcase {

    /** @var \plugin_userfieldorder */
    private \plugin_userfieldorder $plugin;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->plugin = new \plugin_userfieldorder((object) ['id' => 1, 'type' => 'users', 'components' => '']);
    }

    /**
     * Core names some user fields, and those are the ones the summary should translate.
     */
    public function test_a_column_core_names_is_translated(): void {
        $this->assertSame(
            get_string('firstname') . ' ASC',
            $this->plugin->summary((object) ['column' => 'firstname', 'direction' => 'asc'])
        );
        $this->assertDebuggingNotCalled();
    }

    /**
     * Core names neither "id" nor "auth", so the summary has to fall back rather than ask for a missing string.
     */
    public function test_a_column_core_does_not_name_falls_back_to_the_column(): void {
        $this->assertSame('id ASC', $this->plugin->summary((object) ['column' => 'id', 'direction' => 'asc']));
        $this->assertSame('auth DESC', $this->plugin->summary((object) ['column' => 'auth', 'direction' => 'desc']));
        $this->assertDebuggingNotCalled();
    }

}
