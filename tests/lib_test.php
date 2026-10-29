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
 * Unit tests for the plugin's callbacks
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;
use environment_results;
use PHPUnit\Framework\Attributes\CoversFunction;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/environmentlib.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/lib.php');

/**
 * Tests for block_configurable_reports_check_freetype()
 *
 * @package    block_configurable_reports
 * @covers     ::block_configurable_reports_check_freetype
 */
#[CoversFunction('block_configurable_reports_check_freetype')]
final class lib_test extends advanced_testcase {

    /**
     * Issue #233: the environment check has to be callable the way environmentlib.php calls it, and say
     * nothing at all when GD can draw text, which is how a custom check reports that it is happy.
     */
    public function test_the_freetype_check_passes_when_gd_can_draw_text(): void {
        if (!function_exists('imagettftext')) {
            $this->markTestSkipped('GD on this machine was built without freetype.');
        }

        $this->assertNull(block_configurable_reports_check_freetype(new environment_results('custom_check')));
    }

    /**
     * The string the check reports with has to exist in this plugin, since environmentlib.php reads a plain
     * identifier from the admin component instead.
     */
    public function test_the_feedback_string_exists_in_this_plugin(): void {
        $this->assertTrue(
            get_string_manager()->string_exists('freetypenotfound', 'block_configurable_reports')
        );
    }

}
