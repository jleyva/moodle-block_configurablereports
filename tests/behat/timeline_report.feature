@block @block_configurable_reports
Feature: Create a timeline report
  In order to follow activity over time
  As a manager
  I need to create a timeline report with one row per time interval

  Background:
    Given the following "courses" exist:
      | fullname      | shortname | category |
      | Reporting 101 | C1        | 0        |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"

  @javascript
  Scenario: Create a timeline report covering the previous seven days
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Last seven days |
      | Type of report | Timeline report |
      | Total Records  | 1               |
    And I press "Add"
    Then I should see "Time mode"
    When I set the following fields to these values:
      | Time mode     | Previous days |
      | Previous start | 7            |
      | Previous end   | 0            |
      | Time interval  | 1            |
      | Ordering       | ASC          |
    And I press "Save changes"
    And I follow "Columns"
    Then I should see "No columns yet"
    When I set the field "plugin" to "Date"
    And I set the following fields to these values:
      | Date               | Start date |
      | Date format        | Custom     |
      | Custom date format | %Y%m%d     |
      | Name               | Day        |
    And I press "Add"
    Then I should see "Day"
    When I follow "View report"
    Then I should see "Day"
    And I should see "Total record count = 7"
    And I should see "##7 days ago##%Y%m%d##"
    And I should see "##yesterday##%Y%m%d##"
