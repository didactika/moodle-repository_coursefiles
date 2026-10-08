@repository @repository_coursebridge @javascript @accessibility
Feature: Reuse published teaching material through the native file picker
  In order to reuse an earlier edition without losing existing material
  As a teacher in the destination course
  I need to search published sources and save a native copy

  Background:
    Given the following "repository_coursebridge > picker fixtures" exist:
      | name              |
      | Picker acceptance |
    And I am on the "Target materials" "folder activity" page logged in as "cbteacher"
    And I press "Edit"
    And I click on "Add..." "button" in the "Files" "form_row"
    And I select "Materials from related courses" repository in file picker
    And the ".coursebridge-search" "css_element" should meet accessibility standards

  Scenario: Group repeated content and identify the available editions
    Then I should see "lesson.txt (2)" in the ".file-picker" "css_element"
    And I should not see "hidden.txt" in the ".file-picker" "css_element"
    When I click on "lesson.txt (2)" "link" in the ".file-picker" "css_element"
    Then I should see "CB2024: Published materials/lesson.txt" in the ".file-picker" "css_element"
    And I should see "CB2025: Published materials/lesson.txt" in the ".file-picker" "css_element"

  Scenario: Apply filters and save a copy alongside existing destination files
    When I set the following fields to these values:
      | Filename         | lesson     |
      | Edition          | 2025       |
      | Course shortname | CB2025     |
      | Source module    | Folder     |
      | File type        | Plain text |
    And I click on "Search" "button" in the ".file-picker" "css_element"
    Then I should see "CB2025: Published materials/lesson.txt" in the ".file-picker" "css_element"
    And I should not see "lesson.txt (2)" in the ".file-picker" "css_element"
    And I should not see "resource.txt" in the ".file-picker" "css_element"
    When I click on "CB2025: Published materials/lesson.txt" "link" in the ".file-picker" "css_element"
    And I click on "Select this file" "button"
    Then I should see "2" elements in "Files" filemanager
    When I press "Save changes"
    Then I should see "lesson.txt"
    And I should see "existing.txt"

  Scenario: Reach search controls in order and submit with the keyboard
    When I click on "Relationship profile" "select"
    And I press tab
    Then the focused element is "Filename" "field"
    When I set the field "Filename" to "lesson"
    And I press tab
    Then the focused element is "Edition" "field"
    When I set the field "Edition" to "2025"
    And I press tab
    Then the focused element is "Course shortname" "field"
    When I press tab
    Then the focused element is "Source module" "select"
    When I press tab
    Then the focused element is "File type" "select"
    When I press tab
    Then the focused element is "Search" "button" in the ".file-picker" "css_element"
    When I press enter
    Then I should see "CB2025: Published materials/lesson.txt" in the ".file-picker" "css_element"
    And I should not see "lesson.txt (2)" in the ".file-picker" "css_element"
