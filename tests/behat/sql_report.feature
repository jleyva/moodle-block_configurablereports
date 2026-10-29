@block @block_configurable_reports
Feature: Create an SQL report
  In order to report on anything my site stores
  As a manager
  I need to create a report from a custom SQL query

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Alice     | Archer   | student1@example.com |
      | student2 | Bob       | Baker    | student2@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Reporting 101 | C1        | 0        |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Reporting 101" course homepage
    And I follow "Manage reports"

  Scenario: Create an SQL report from a custom query
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Usernames   |
      | Type of report | SQL Report  |
      | Total Records  | 1           |
    And I press "Add"
    Then I should see "SQL Query"
    When I set the field "SQL Query" to "SELECT firstname, lastname, username FROM prefix_user WHERE deleted = 0 ORDER BY username"
    And I press "Save changes"
    And I follow "View report"
    Then I should see "Alice"
    And I should see "Archer"
    And I should see "student1"
    And I should see "Bob"
    And I should see "student2"
    And I should see "Total record count = 4"
    And I should not see "This report is incomplete"

  Scenario: A report cut short by the report row limit says so
    Given the following config values are set as admin:
      | reportlimit | 2 | block_configurable_reports |
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Usernames   |
      | Type of report | SQL Report  |
      | Total Records  | 1           |
    And I press "Add"
    And I set the field "SQL Query" to "SELECT firstname, lastname, username FROM prefix_user WHERE deleted = 0 ORDER BY username"
    And I press "Save changes"
    And I follow "View report"
    Then I should see "This report is incomplete"
    And I should see "more than 2 rows"
    And I should see "Total record count = 2"

  Scenario: An SQL report built around the current course
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | This course |
      | Type of report | SQL Report  |
    And I press "Add"
    And I set the field "SQL Query" to "SELECT fullname, shortname FROM prefix_course WHERE id = %%COURSEID%%"
    And I press "Save changes"
    And I follow "View report"
    Then I should see "Reporting 101"
    And I should not see "Site home"

  Scenario: A query that changes data is rejected
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Dangerous report |
      | Type of report | SQL Report       |
    And I press "Add"
    And I set the field "SQL Query" to "DELETE FROM prefix_user WHERE id = 1"
    And I press "Save changes"
    Then I should see "Not allowed words"

  Scenario: A query containing a semicolon is rejected
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Two statements |
      | Type of report | SQL Report     |
    And I press "Add"
    And I set the field "SQL Query" to "SELECT id FROM prefix_user;"
    And I press "Save changes"
    Then I should see "No semicolon"

  Scenario: A query that does not run is rejected
    When I follow "Add report"
    And I set the following fields to these values:
      | Name           | Broken report |
      | Type of report | SQL Report    |
    And I press "Add"
    And I set the field "SQL Query" to "SELECT nosuchcolumn FROM prefix_user"
    And I press "Save changes"
    Then I should see "Query failed"

  Scenario: A report that returns no rows still downloads as CSV
    When I follow "Add report"
    And I set the following fields to these values:
      | Name                 | Nobody at all |
      | Type of report       | SQL Report    |
      | Export in CSV format | 1             |
    And I press "Add"
    And I set the field "SQL Query" to "SELECT id, username FROM prefix_user WHERE deleted = 2"
    And I press "Save changes"
    And I follow "Manage reports"
    Then following "CSV" should download between "0" and "5" bytes
