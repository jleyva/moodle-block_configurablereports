@block @block_configurable_reports
Feature: Create a categories report
  In order to see how my site is organised
  As a manager
  I need to create a categories report and pick the category fields it shows

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber |
      | Humanities | 0        | HUM      |
      | Sciences   | 0        | SCI      |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Reporting 101 | C1        | 0        |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"

  @javascript
  Scenario: Create a categories report listing every course category
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | All categories    |
      | Type of report | Categories report |
      | Total Records  | 1                 |
    And I press "Add"
    Then I should see "No columns yet"
    When I set the field "plugin" to "Category field"
    And I set the following fields to these values:
      | Column | name          |
      | Name   | Category name |
    And I press "Add"
    Then I should see "Category name"
    When I set the field "plugin" to "Category field"
    And I set the following fields to these values:
      | Column | idnumber |
      | Name   | ID number |
    And I press "Add"
    Then I should see "ID number"
    When I follow "View report"
    Then I should see "Humanities"
    And I should see "Sciences"
    And I should see "HUM"
    And I should see "SCI"
