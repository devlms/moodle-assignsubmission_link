@mod @mod_assign @assignsubmission @assignsubmission_link
Feature: Students hand in links
  In order to hand in work that lives online
  As a student
  I need to submit links to the sites my teacher allows

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
      | student1 | Student   | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: A student submits a link to an allowed site and the teacher sees it
    Given the following "activity" exists:
      | activity                            | assign     |
      | course                              | C1         |
      | name                                | Portfolio  |
      | assignsubmission_onlinetext_enabled | 0          |
      | assignsubmission_file_enabled       | 0          |
      | assignsubmission_link_enabled       | 1          |
      | assignsubmission_link_maxlinks      | 2          |
      | assignsubmission_link_sites         | github.com |
      | submissiondrafts                    | 0          |
    When I am on the "Portfolio" Activity page logged in as student1
    And I press "Add submission"
    Then I should see "Links to: github.com"
    And I set the field "Link 1" to "https://pastebin.example/abc"
    And I press "Save changes"
    And I should see "Links must go to github.com"
    And I set the field "Link 1" to "https://github.com/student1/portfolio"
    And I set the field "Link 2" to "https://gist.github.com/student1/42"
    And I press "Save changes"
    And I should see "github.com/student1/portfolio"
    And I should see "gist.github.com/student1/42"
    And I am on the "Portfolio" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I should see "github.com/student1/portfolio" in the "Student 1" "table_row"

  Scenario: A teacher sets how many links and which sites
    Given the following "activity" exists:
      | activity                            | assign    |
      | course                              | C1        |
      | name                                | Portfolio |
      | assignsubmission_onlinetext_enabled | 0         |
      | assignsubmission_file_enabled       | 0         |
    When I am on the "Portfolio" "assign activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | assignsubmission_link_enabled | 1                                    |
      | Links per submission          | 3                                    |
      | Allowed sites                 | GitHub.com, https://docs.google.com/ |
    And I press "Save and display"
    And I am on the "Portfolio" Activity page logged in as student1
    And I press "Add submission"
    Then I should see "Link 3"
    And I should see "Links to: github.com, docs.google.com"

  Scenario: Links made by the generator show on the submission status page
    Given the following "activity" exists:
      | activity                      | assign    |
      | course                        | C1        |
      | name                          | Portfolio |
      | assignsubmission_link_enabled | 1         |
      | assignsubmission_link_maxlinks | 2        |
    And the following "mod_assign > submissions" exist:
      | assign    | user     | link                                                       |
      | Portfolio | student1 | https://figma.com/file/abc https://example.org/demo-site   |
    When I am on the "Portfolio" Activity page logged in as student1
    Then I should see "figma.com/file/abc"
    And I should see "example.org/demo-site"
