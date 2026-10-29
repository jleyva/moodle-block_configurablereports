@block @block_configurable_reports
Feature: Create a courses report
  In order to see what courses exist on my site
  As a manager
  I need to create a courses report and pick the course fields it shows

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber |
      | Humanities | 0        | HUM      |
    And the following "courses" exist:
      | fullname        | shortname | category |
      | Reporting 101   | C1        | 0        |
      | Ancient history | C2        | HUM      |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"

  @javascript
  Scenario: Create a courses report listing every course on the site
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | All courses    |
      | Type of report | Courses report |
      | Total Records  | 1              |
    And I press "Add"
    Then I should see "No columns yet"
    When I set the field "plugin" to "Course field"
    And I set the following fields to these values:
      | Column | fullname  |
      | Name   | Full name |
    And I press "Add"
    Then I should see "Full name"
    When I set the field "plugin" to "Course field"
    And I set the following fields to these values:
      | Column | shortname  |
      | Name   | Short name |
    And I press "Add"
    Then I should see "Short name"
    When I follow "View report"
    Then I should see "Reporting 101"
    And I should see "Ancient history"
    And I should see "C1"
    And I should see "C2"

  @javascript
  Scenario: Restrict a courses report to the courses in one category
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Humanities courses |
      | Type of report | Courses report     |
      | Total Records  | 1                  |
    And I press "Add"
    And I set the field "plugin" to "Course field"
    And I set the following fields to these values:
      | Column | fullname  |
      | Name   | Full name |
    And I press "Add"
    And I follow "Conditions"
    Then I should see "No conditions yet"
    When I set the field "plugin" to "Course field condition"
    And I set the following fields to these values:
      | Column   | shortname |
      | Operator | =         |
      | Value    | C2        |
    And I press "Add"
    And I follow "View report"
    Then I should see "Ancient history"
    And I should not see "Reporting 101"
    And I should see "Total record count = 1"
