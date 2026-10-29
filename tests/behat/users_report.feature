@block @block_configurable_reports
Feature: Create a users report
  In order to report on the people using my site
  As a manager
  I need to create a users report and pick the user fields it shows

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | city      |
      | student1 | Alice     | Archer   | student1@example.com | Barcelona |
      | student2 | Bob       | Baker    | student2@example.com | Madrid    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Manage reports"

  @javascript
  Scenario: Create a users report listing every user on the site
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Site users   |
      | Type of report | Users report |
      | Total Records  | 1            |
    And I press "Add"
    Then I should see "No columns yet"
    When I set the field "plugin" to "User profile field"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | Full name |
    And I press "Add"
    Then I should see "Full name"
    And I should not see "No columns yet"
    When I follow "View report"
    Then I should see "Alice Archer"
    And I should see "Bob Baker"
    And I should see "Total record count = 4"

  @javascript
  Scenario: Restrict a users report with a condition on a profile field
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Users from Madrid |
      | Type of report | Users report      |
      | Total Records  | 1                 |
    And I press "Add"
    And I set the field "plugin" to "User profile field"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | Full name |
    And I press "Add"
    And I follow "Conditions"
    Then I should see "No conditions yet"
    When I set the field "plugin" to "User field condition"
    And I set the following fields to these values:
      | Column   | city   |
      | Operator | =      |
      | Value    | Madrid |
    And I press "Add"
    And I follow "View report"
    Then I should see "Bob Baker"
    And I should not see "Alice Archer"
    And I should see "Total record count = 1"

  @javascript
  Scenario: The report appears in the block and in the manage reports list
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Site users   |
      | Type of report | Users report |
    And I press "Add"
    And I set the field "plugin" to "User profile field"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | Full name |
    And I press "Add"
    And I follow "Manage reports"
    Then I should see "Site users"
    And I should see "Users report"
    When I am on "Course 1" course homepage
    Then I should see "Site users" in the "block_configurable_reports" "block"
