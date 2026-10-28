@block @block_configurable_reports
Feature: Control who can see a report
  In order to share a report without giving away the whole site
  As a manager
  I need to say which users are allowed to view it

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | city      |
      | student1 | Alice     | Archer   | student1@example.com | Barcelona |
      | student2 | Bob       | Baker    | student2@example.com | Madrid    |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Reporting 101 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | Everybody list |
      | Type of report | Users report   |
      | Total Records  | 1              |
    And I press "Add"
    And I set the field "plugin" to "User profile field"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | Full name |
    And I press "Add"

  @javascript
  Scenario: A student cannot see a report with no permissions set
    When I log in as "student1"
    And I am on "Reporting 101" course homepage
    Then I should not see "Everybody list"

  @javascript
  Scenario: The Anyone permission opens a report up to every user
    When I follow "Permissions"
    Then I should see "No permissions yet"
    When I set the field "plugin" to "Anyone"
    Then I should see "Anyone"
    And I should not see "No permissions yet"
    When I log in as "student1"
    And I am on "Reporting 101" course homepage
    And I follow "Everybody list"
    Then I should see "Alice Archer"
    And I should see "Bob Baker"

  @javascript
  Scenario: A user field value permission restricts a report by profile field
    When I follow "Permissions"
    And I set the field "plugin" to "User field value"
    And I set the following fields to these values:
      | Column | city   |
      | Value  | Madrid |
    And I press "Add"
    Then I should see "User field value"
    When I log in as "student2"
    And I am on "Reporting 101" course homepage
    And I follow "Everybody list"
    Then I should see "Alice Archer"
    When I log in as "student1"
    And I am on "Reporting 101" course homepage
    Then I should not see "Everybody list"

  @javascript
  Scenario: A hidden report is not shown to students
    When I follow "Permissions"
    And I set the field "plugin" to "Anyone"
    And I follow "Manage reports"
    And I click on "Hide" "link" in the "Everybody list" "table_row"
    And I log in as "student1"
    And I am on "Reporting 101" course homepage
    Then I should not see "Everybody list"
