@block @block_configurable_reports
Feature: Configure the components of a report
  In order to get exactly the report I need
  As a manager
  I need to order, filter and summarise the rows a report returns

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | city      |
      | student1 | Alice     | Archer   | student1@example.com | Barcelona |
      | student2 | Bob       | Baker    | student2@example.com | Madrid    |
      | student3 | Carol     | Cooper   | student3@example.com | Madrid    |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Reporting 101 | C1        | 0        |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | People       |
      | Type of report | Users report |
      | Total Records  | 1            |
    And I press "Add"
    And I set the field "plugin" to "User profile field"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | Full name |
    And I press "Add"

  @javascript
  Scenario: Order a users report by a user field
    When I follow "Ordering"
    Then I should see "No ordering yet"
    When I set the field "plugin" to "User field order"
    And I set the following fields to these values:
      | Column    | lastname |
      | Direction | DESC     |
    And I press "Add"
    Then I should not see "No ordering yet"
    When I follow "View report"
    Then "Carol Cooper" "text" should appear before "Bob Baker" "text"
    And "Bob Baker" "text" should appear before "Alice Archer" "text"

  @javascript
  Scenario: Filter a users report when viewing it
    When I click on "Filters" "link" in the "#region-main" "css_element"
    Then I should see "No filters yet"
    When I set the field "plugin" to "User field filter"
    And I set the field "Field" to "city"
    And I press "Add"
    Then I should not see "No filters yet"
    When I follow "View report"
    Then I should see "Alice Archer"
    And I should see "Bob Baker"
    When I set the field "City" to "Madrid"
    And I press "Apply"
    Then I should see "Bob Baker"
    And I should see "Carol Cooper"
    And I should not see "Alice Archer"
    And I should see "Total record count = 2"

  @javascript
  Scenario: Combine two conditions with a condition expression
    When I follow "Conditions"
    And I set the field "plugin" to "User field condition"
    And I set the following fields to these values:
      | Column   | city      |
      | Operator | =         |
      | Value    | Barcelona |
    And I press "Add"
    And I set the field "plugin" to "User field condition"
    And I set the following fields to these values:
      | Column   | city   |
      | Operator | =      |
      | Value    | Madrid |
    And I press "Add"
    And I set the field "Condition" to "c1 and c2"
    And I press "Update"
    And I follow "View report"
    Then I should not see "Alice Archer"
    And I should not see "Bob Baker"
    When I follow "Conditions"
    And I set the field "Condition" to "c1 or c2"
    And I press "Update"
    And I follow "View report"
    Then I should see "Alice Archer"
    And I should see "Bob Baker"
    And I should see "Carol Cooper"
    And I should see "Total record count = 3"

  @javascript
  Scenario: An invalid condition expression is rejected
    When I follow "Conditions"
    And I set the field "plugin" to "User field condition"
    And I set the following fields to these values:
      | Column   | city      |
      | Operator | =         |
      | Value    | Barcelona |
    And I press "Add"
    And I set the field "Condition" to "c1 and ("
    And I press "Update"
    Then I should see "Incorrect condition expression"
