@block @block_configurable_reports
Feature: Reports enforce their capability checks
  In order to keep report data and queries away from people who should not see them
  As a site administrator
  I need the report pages to check capabilities in the course the report belongs to

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | managera | Mary      | Managera | managera@example.com |
      | managerb | Bob       | Managerb | managerb@example.com |
      | student1 | Alice     | Archer   | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course A | CA        | 0        |
      | Course B | CB        | 0        |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | managera | manager | Course       | CA        |
      | managerb | manager | Course       | CB        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | CA     | student |
      | managerb | CA     | student |
    And the following "blocks" exist:
      | blockname            | contextlevel | reference | pagetypepattern | defaultregion |
      | configurable_reports | Course       | CA        | course-view-*   | side-pre      |
      | configurable_reports | Course       | CB        | course-view-*   | side-pre      |
    And I log in as "admin"
    And I am on "Course A" course homepage
    And I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | Course A people |
      | Type of report | Users report    |
    And I press "Add"

  @javascript
  Scenario: A manager of another course cannot reach a report through a crafted course id
    When I log in as "managerb"
    Then the "Course A people > columns > CB" "block_configurable_reports > component in course" page should show the error "You do not have permission to perform this action"
    And I should not see "No columns yet"

  @javascript
  Scenario: The manager of the report course can still edit its components
    When I log in as "managera"
    And I am on the "Course A people > columns" "block_configurable_reports > component" page
    Then I should see "No columns yet"

  @javascript
  Scenario: Adding a plugin without a sesskey is refused
    When the "Course A people > permissions > anyone" "block_configurable_reports > plugin" page should show the error "A required parameter (sesskey) was missing"
    When I am on the "Course A people > permissions" "block_configurable_reports > component" page
    Then I should see "No permissions yet"

  @javascript
  Scenario: A manager without the SQL capability cannot edit the plugins of an SQL report
    Given I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | Course A query |
      | Type of report | SQL Report     |
    And I press "Add"
    And I set the report SQL query to "SELECT username FROM prefix_user WHERE deleted = 0"
    And I press "Save changes"
    When I log in as "managera"
    Then the "Course A query > filters > searchtext" "block_configurable_reports > plugin" page should show the error "You do not have permission to manage SQL reports"

  @javascript
  Scenario: The report query is only dumped for users who may manage SQL reports
    Given I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | Course A query |
      | Type of report | SQL Report     |
    And I press "Add"
    And I set the report SQL query to "SELECT username FROM prefix_user WHERE deleted = 0"
    And I press "Save changes"
    And I click on "Permissions" "link" in the "#region-main" "css_element"
    And I set the field "plugin" to "Anyone"
    When I am on the "Course A query" "block_configurable_reports > view with debug" page
    Then "#debug" "css_element" should exist
    And I should see "SELECT username FROM"
    When I log in as "student1"
    And I am on the "Course A query" "block_configurable_reports > view with debug" page
    Then "#debug" "css_element" should not exist
    And I should not see "SELECT username FROM"
    And I should see "admin"

  @javascript
  Scenario: Only a site administrator may retarget a report at another course
    When I log in as "managera"
    And I am on the "Course A people" "block_configurable_reports > edit in admin mode" page
    Then I should not see "Set courseid"
    When I log in as "admin"
    And I am on the "Course A people" "block_configurable_reports > edit in admin mode" page
    Then I should see "Set courseid"
    When I set the "Set courseid" field to the id of course "CB"
    And I press "Update"
    And I am on "Course B" course homepage
    And I follow "Manage reports"
    Then I should see "Course A people"

  @javascript
  Scenario: A remote import without a sesskey is refused
    Then the "CA > https://example.com/evil.json" "block_configurable_reports > remote import" page should show the error "A required parameter (sesskey) was missing"

  @javascript
  Scenario: Values entered by the report author are escaped in the components list
    Given I set the field "plugin" to "User profile field"
    And I set the following fields to these values:
      | Column | Full name |
      | Name   | Full name |
    And I press "Add"
    And I follow "Conditions"
    And I set the field "plugin" to "User field condition"
    And I set the following fields to these values:
      | Column   | city                       |
      | Operator | =                          |
      | Value    | <script>alert(1)</script>  |
    And I press "Add"
    Then I should see "alert(1)"
    And "#region-main table script" "css_element" should not exist

  @javascript
  Scenario: Markup stored in course data is cleaned before the report shows it
    Given the following "courses" exist:
      | fullname | shortname | category | summary                             |
      | Course X | CX        | 0        | <script>alert(2)</script>Hello there |
    And I follow "Manage reports"
    And I follow "Add report"
    And I set the following fields to these values:
      | Name           | Course summaries |
      | Type of report | Courses report   |
    And I press "Add"
    And I set the field "plugin" to "Course field"
    And I set the following fields to these values:
      | Column | summary |
      | Name   | Summary |
    And I press "Add"
    When I follow "View report"
    Then I should see "Hello there"
    And "#reporttable script" "css_element" should not exist
