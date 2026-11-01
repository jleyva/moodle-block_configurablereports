@block @block_configurable_reports
Feature: Manage the reports of a course
  In order to keep my list of reports tidy
  As a manager
  I need to hide, duplicate, export and delete reports

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
      | configurable_reports | Course       | C2        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name                 | People list  |
      | Type of report       | Users report |
      | Export in CSV format | 1            |
    And I press "Add"
    And I set the field "plugin" to "User profile field"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | Full name |
    And I press "Add"
    And I follow "Manage reports"

  @javascript
  Scenario: Duplicate a report
    When I click on "Duplicate" "link" in the "People list" "table_row"
    Then I should see "copy People list"
    And I should see "People list"

  @javascript
  Scenario: Hide and show a report again
    When I click on "Hide" "link" in the "People list" "table_row"
    Then "Show" "link" should exist in the "People list" "table_row"
    When I click on "Show" "link" in the "People list" "table_row"
    Then "Hide" "link" should exist in the "People list" "table_row"

  @javascript
  Scenario: Delete a report after confirming
    When I click on "Delete" "link" in the "People list" "table_row"
    Then I should see "Are you sure you want to delete this report?"
    When I press "No"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"
    Then I should see "People list"
    When I click on "Delete" "link" in the "People list" "table_row"
    And I press "Yes"
    Then I should see "No reports available"

  @javascript
  Scenario: Download a report in CSV format
    Then following "CSV" should download between "10" and "5000" bytes

  @javascript
  Scenario: A course report is not listed in another course
    When I am on "Reporting 202" course homepage
    And I follow "Manage reports"
    Then I should see "No reports available"

  @javascript
  Scenario: A global report is offered by the block of another course
    When I click on "Edit" "link" in the "People list" "table_row"
    And I set the field "Global report" to "1"
    And I press "Update"
    And I am on "Reporting 202" course homepage with editing mode on
    And I open the "Configurable Reports" blocks action menu
    And I click on "Configure Configurable Reports block" "link" in the "Configurable Reports" "block"
    And I set the field "Display global reports" to "1"
    And I press "Save changes"
    Then I should see "People list" in the "Configurable Reports" "block"

  @javascript
  Scenario: A report offers a download link for each enabled export format
    When I click on "Edit" "link" in the "People list" "table_row"
    And I set the field "Export in XLS format" to "1"
    And I press "Update"
    And I follow "Manage reports"
    Then I should see "CSV" in the "People list" "table_row"
    And I should see "XLS" in the "People list" "table_row"
    And I should not see "ODS" in the "People list" "table_row"
    And following "XLS" should download between "10" and "50000" bytes
