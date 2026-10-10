@repository @repository_coursefiles @javascript
Feature: Search related material in the native embedded picker
  As a destination course teacher
  I need compact filters, unique file results and current source access checks

  Background:
    Given the following "repository_coursefiles > picker fixtures" exist:
      | name             |
      | Picker acceptance |
    And I am on the "Target materials" "folder activity" page logged in as "cbteacher"
    And I press "Edit"
    And I open the native CourseFiles

  Scenario: Identical files appear once and hidden material is excluded
    When I set CourseFiles filter "q" to "lesson"
    Then I should see "1" CourseFiles file cards named "lesson.txt"
    And the CourseFiles browser should not include "Hidden materials"
    And the CourseFiles browser should not include "hidden.txt"

  Scenario: Edition and native file filters select the correct published declaration
    When I set CourseFiles filter "q" to "resource"
    And I set CourseFiles filter "captures[edition]" to "2025"
    And I set CourseFiles filter "shortname" to "CB2025"
    And I set CourseFiles filter "module" to "resource"
    And I set CourseFiles filter "mimetype" to "text/plain"
    Then I should see "1" CourseFiles file cards named "resource.txt"
    And the CourseFiles browser should not include "CB2024"
    When I select CourseFiles material "resource.txt" from "CB2025"
    And I press CourseFiles action "Review selection"
    Then the CourseFiles review should include "Original teacher"
    When I press CourseFiles action "Import materials"
    And I press "Save changes"
    Then I should see "resource.txt"
    And I should see "existing.txt"

  Scenario: A filtered search retains an already selected source folder
    When I set CourseFiles filter "module" to ""
    And I select CourseFiles material "Published materials" from "CB2025"
    And I set CourseFiles filter "q" to "resource"
    And I select CourseFiles material "resource.txt" from "CB2025"
    And I press CourseFiles action "Review selection"
    Then the CourseFiles review should include "lesson.txt"
    And the CourseFiles review should include "resource.txt"
