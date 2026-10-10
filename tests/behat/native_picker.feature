@repository @repository_filebank @javascript
Feature: Search related material in the native embedded picker
  As a destination course teacher
  I need compact filters, unique file results and current source access checks

  Background:
    Given the following "repository_filebank > picker fixtures" exist:
      | name             |
      | Picker acceptance |
    And I am on the "Target materials" "folder activity" page logged in as "cbteacher"
    And I press "Edit"
    And I open the native FileBank

  Scenario: Identical files appear once and hidden material is excluded
    When I set FileBank filter "q" to "lesson"
    Then I should see "1" FileBank file cards named "lesson.txt"
    And the FileBank browser should not include "Hidden materials"
    And the FileBank browser should not include "hidden.txt"

  Scenario: Edition and native file filters select the correct published declaration
    When I set FileBank filter "q" to "resource"
    And I set FileBank filter "captures[edition]" to "2025"
    And I set FileBank filter "shortname" to "CB2025"
    And I set FileBank filter "module" to "resource"
    And I set FileBank filter "mimetype" to "text/plain"
    Then I should see "1" FileBank file cards named "resource.txt"
    And the FileBank browser should not include "CB2024"
    When I select FileBank material "resource.txt" from "CB2025"
    And I press FileBank action "Review selection"
    Then the FileBank review should include "Original teacher"
    When I press FileBank action "Import materials"
    And I press "Save changes"
    Then I should see "resource.txt"
    And I should see "existing.txt"

  Scenario: A filtered search retains an already selected source folder
    When I set FileBank filter "module" to ""
    And I select FileBank material "Published materials" from "CB2025"
    And I set FileBank filter "q" to "resource"
    And I select FileBank material "resource.txt" from "CB2025"
    And I press FileBank action "Review selection"
    Then the FileBank review should include "lesson.txt"
    And the FileBank review should include "resource.txt"
