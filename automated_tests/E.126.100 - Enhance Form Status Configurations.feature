Feature: E.126.100 - The system shall support the ability to enable/disable Enhance form status external module.

  As a REDCap end user
  I want to see that Enhance form status is functioning as expected

  Scenario: E.126.100 - Enable external module - Default settings
    Given I login to REDCap with the user "Test_Admin"
    When I click on the link labeled "Control Center"
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Module Manager"
    And I should NOT see "Enhance form status - v1.0.1"
    When I click on the button labeled "Enable a module"
    And I wait for 2 seconds
    Then I should see "Available Modules"
    And I click on the button labeled "Enable" in the row labeled "Enhance form status"
    And I wait for 1 second
    And I click on the button labeled "Enable"
    Then I should see "Enhance form status - v1.0.1"

    When I click on the link labeled "View Logs"
    Then I should see "External Module Logs"
    And I should see a table header and row containing the following values in a table:
      | Module                | Message                                  | UserName   |
      | enhance_form_status   | DataEntry.php code inserted successfully | Test_Admin |
      | enhance_form_status   | Hooks.php code inserted successfully     | Test_Admin |
      | enhance_form_status   | Module system enable initiated           | Test_Admin |

    And I logout
    
    Given I login to REDCap with the user "Test_User1"
    When I create a new project named "E.126.100" by clicking on "New Project" in the menu bar, selecting "Practice / Just for fun" from the dropdown, choosing file "fixtures/cdisc_files/Project_redcap_val_nodata.xml", and clicking the "Create Project" button
    # And I should NOT see a link labeled "Manage"
    And I logout

    # Disable external module in Control Center
    Given I login to REDCap with the user "Test_Admin"
    When I click on the link labeled "Control Center"
    And I click on the link labeled "Manage"
    And I click on the button labeled "Disable"
    Then I should see "Disable module?"
    When I click on the button labeled "Disable module"
    Then I should NOT see "Enhance form status - v1.0.1"

    When I click on the link labeled "View Logs"
    Then I should see "External Module Logs"
    And I should see a table header and row containing the following values in a table:
      | Module                | Message                                  | UserName   |
      | enhance_form_status   | DataEntry.php code removed successfully  | Test_Admin |
      | enhance_form_status   | Hooks.php code removed successfully      | Test_Admin |
      | enhance_form_status   | Module system disable initiated          | Test_Admin |
      | enhance_form_status   | DataEntry.php code inserted successfully | Test_Admin |
      | enhance_form_status   | Hooks.php code inserted successfully     | Test_Admin |
      | enhance_form_status   | Module system enable initiated           | Test_Admin |

    And I logout

    # Verify no exceptions are thrown in the system
    Given I open Email
    Then I should NOT see an email with subject "REDCap External Module Hook Exception - enhance_form_status"