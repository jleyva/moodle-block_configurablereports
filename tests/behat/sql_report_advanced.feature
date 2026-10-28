@block @block_configurable_reports
Feature: Build an advanced SQL report
  In order to report on anything my site stores
  As a manager
  I need placeholders, filters, calculations and templates in my SQL reports

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Alice     | Archer   | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Reporting 101 | C1        | 0        |
      | Reporting 202 | C2        | 0        |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | Course query |
      | Type of report | SQL Report   |
      | Total Records  | 1            |
    And I press "Add"

  Scenario: The current user placeholder resolves to the person viewing the report
    When I set the report SQL query to "SELECT username, firstname FROM prefix_user WHERE id = %%USERID%%"
    And I press "Save changes"
    And I follow "View report"
    Then I should see "admin"
    And I should see "Total record count = 1"

  Scenario: The current course placeholder resolves to the course the report lives in
    When I set the report SQL query to "SELECT fullname, shortname FROM prefix_course WHERE id = %%COURSEID%%"
    And I press "Save changes"
    And I follow "View report"
    Then I should see "Reporting 101"
    And I should not see "Reporting 202"

  Scenario: A template replaces the report table with my own layout
    When I set the report SQL query to "SELECT shortname, fullname FROM prefix_course WHERE id > 1 ORDER BY shortname"
    And I press "Save changes"
    And I follow "Template"
    And I set the following fields to these values:
      | Template        | Enabled                                   |
      | Header          | <h3>Courses on this site</h3>             |
      | Record template | <p>[[shortname]] is called [[fullname]]</p> |
      | Footer          | <p>End of list</p>                        |
    And I press "Save changes"
    And I follow "View report"
    Then I should see "Courses on this site"
    And I should see "C1 is called Reporting 101"
    And I should see "C2 is called Reporting 202"
    And I should see "End of list"

  @javascript
  Scenario: A calculation summarises a column of the report
    When I set the report SQL query to "SELECT c.shortname AS course, 7 AS points FROM prefix_course c WHERE c.id > 1"
    And I press "Save changes"
    And I follow "Calculations"
    Then I should see "No calculations yet"
    When I set the field "plugin" to "Sum"
    And I set the field "Column" to "points"
    And I press "Add"
    Then I should see "Sum"
    When I follow "View report"
    Then I should see "14"

  @javascript
  Scenario: A search text filter narrows the rows of an SQL report
    When I set the report SQL query to "SELECT shortname, fullname FROM prefix_course WHERE id > 1 %%FILTER_SEARCHTEXT_name:fullname:~%%"
    And I press "Save changes"
    And I click on "Filters" "link" in the "#region-main" "css_element"
    Then I should see "No filters yet"
    When I set the field "plugin" to "Search text"
    And I set the following fields to these values:
      | ID Number | name        |
      | Label     | Course name |
    And I press "Add"
    Then I should not see "No filters yet"
    When I follow "View report"
    Then I should see "Reporting 101"
    And I should see "Reporting 202"
    When I set the field "Course name" to "202"
    And I press "Apply"
    Then I should see "Reporting 202"
    And I should not see "Reporting 101"
    And I should see "Total record count = 1"
