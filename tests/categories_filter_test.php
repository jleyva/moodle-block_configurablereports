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
 * Unit tests for the categories filter
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_configurable_reports;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/configurable_reports/filter.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/components/filters/categories/plugin.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/report.class.php');
require_once($CFG->dirroot . '/blocks/configurable_reports/reports/sql/report.class.php');

/**
 * Tests for plugin_categories
 *
 * @package    block_configurable_reports
 * @copyright  2026 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class categories_filter_test extends advanced_testcase {

    /** @var \stdClass The report the filter belongs to */
    private \stdClass $report;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Filters read the report database from $remotedb, which is set per request rather than at
        // file level because PHPUnit includes test files from inside a function.
        global $DB, $remotedb;
        $remotedb = $DB;

        // Core leaves out every category a user may not see, and nobody is logged in by default.
        $this->setAdminUser();

        $this->report = (object) [
            'id' => 1,
            'name' => 'Categories',
            'type' => 'sql',
            'components' => '',
        ];
    }

    /**
     * The options the filter offers, in the order it offers them.
     *
     * @return array
     */
    private function options(): array {
        $filter = new \plugin_categories($this->report);

        $mform = new \MoodleQuickForm('f', 'post', '');
        $filter->print_filter($mform);

        $labels = [];
        foreach ($mform->getElement('filter_categories')->_options as $option) {
            $labels[$option['attr']['value']] = $option['text'];
        }

        return $labels;
    }

    /**
     * CONTRIB-4159: the categories came back in the order they were created, which on a site with
     * nested categories is no order at all. They follow the category tree now.
     */
    public function test_options_follow_the_category_tree(): void {
        $generator = $this->getDataGenerator();

        $zulu = $generator->create_category(['name' => 'Zulu']);
        $alpha = $generator->create_category(['name' => 'Alpha']);
        $generator->create_category(['name' => 'Child', 'parent' => $alpha->id]);

        // Move Alpha above Zulu, so the order the site arranges them in is no longer the order they
        // were created in, which is what the report saw before.
        \core_course_category::get($alpha->id)->change_sortorder_by_one(true);

        $options = $this->options();
        unset($options[0]);

        $this->assertSame(
            array_keys(\core_course_category::make_categories_list()),
            array_map('intval', array_keys($options))
        );
        $this->assertContains('Alpha / Child', $options);
        $this->assertContains('Zulu', $options);
        $order = array_values($options);
        $this->assertGreaterThan(array_search('Alpha', $order, true), array_search('Zulu', $order, true));
    }

    /**
     * The first option stands for no filtering at all, and has to stay first.
     */
    public function test_the_first_option_is_the_placeholder(): void {
        $this->getDataGenerator()->create_category(['name' => 'Alpha']);

        $options = $this->options();

        $this->assertSame(
            get_string('filter_all', 'block_configurable_reports'),
            reset($options)
        );
        $this->assertSame('0', (string) array_key_first($options));
    }

    /**
     * CONTRIB-4159 also reported categories showing that the user is not allowed to see.
     */
    public function test_a_hidden_category_is_not_offered(): void {
        $generator = $this->getDataGenerator();
        $generator->create_category(['name' => 'Visible']);
        $generator->create_category(['name' => 'Secret', 'visible' => 0]);

        $this->setUser($generator->create_user());

        $options = $this->options();

        $this->assertContains('Visible', $options);
        $this->assertNotContains('Secret', $options);
    }

}
