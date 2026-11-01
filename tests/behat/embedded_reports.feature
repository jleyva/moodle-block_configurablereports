@block @block_configurable_reports
Feature: Embed one report inside another
  In order to show related data side by side
  As a manager
  I need to use a column of one report as a column of another

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Alice     | Archer   | student1@example.com |
      | student2 | Bob       | Baker    | student2@example.com |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Humanities | 0        | HUM      |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Reporting 101 | C1        | 0        |
      | Reporting 202 | C2        | HUM      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C2     | student |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | Course people |
      | Type of report | Users report  |
    And I press "Add"
    And I set the field "plugin" to "User profile field"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | Full name |
    And I press "Add"
    And I follow "Manage reports"

  @javascript
  Scenario: A courses report shows the users of each course through an embedded report
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Courses and people |
      | Type of report | Courses report     |
      | Total Records  | 1                  |
    And I press "Add"
    And I set the field "plugin" to "Course field"
    And I set the following fields to these values:
      | Column | fullname    |
      | Name   | Course name |
    And I press "Add"
    And I set the field "plugin" to "Other report column"
    And I set the field "Report" to "Course people"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | People    |
    And I press "Add"
    Then I should see "People"
    When I follow "View report"
    Then I should see "Reporting 101"
    And I should see "Reporting 202"
    And "Alice Archer" "text" should exist in the "Reporting 101" "table_row"
    And "Bob Baker" "text" should exist in the "Reporting 202" "table_row"

  @javascript
  Scenario: A categories report shows the courses of each category through an embedded report
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Course list    |
      | Type of report | Courses report |
    And I press "Add"
    And I set the field "plugin" to "Course field"
    And I set the following fields to these values:
      | Column | shortname  |
      | Name   | Short name |
    And I press "Add"
    And I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | Categories and courses |
      | Type of report | Categories report      |
    And I press "Add"
    And I set the field "plugin" to "Category field"
    And I set the following fields to these values:
      | Column | name          |
      | Name   | Category name |
    And I press "Add"
    And I set the field "plugin" to "Other report column"
    And I set the field "Report" to "Course list"
    And I set the following fields to these values:
      | Column | Short name |
      | Name   | Courses    |
    And I press "Add"
    And I follow "View report"
    Then I should see "Humanities"
    And "C2" "text" should exist in the "Humanities" "table_row"

  @javascript
  Scenario: An embedded report stays empty for a viewer who may not see it
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Courses and people |
      | Type of report | Courses report     |
    And I press "Add"
    And I set the field "plugin" to "Course field"
    And I set the following fields to these values:
      | Column | fullname    |
      | Name   | Course name |
    And I press "Add"
    And I set the field "plugin" to "Other report column"
    And I set the field "Report" to "Course people"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | People    |
    And I press "Add"
    And I click on "Permissions" "link" in the "#region-main" "css_element"
    And I set the field "plugin" to "Anyone"
    And I follow "View report"
    Then "Alice Archer" "text" should exist in the "Reporting 101" "table_row"
    When I log in as "student1"
    And I am on "Reporting 101" course homepage
    And I follow "Courses and people"
    Then I should see "Reporting 101"
    And "Alice Archer" "text" should not exist in the "#reporttable" "css_element"
