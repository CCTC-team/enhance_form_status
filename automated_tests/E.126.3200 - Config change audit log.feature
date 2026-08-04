Feature: E.126.3200 - The system shall record configuration changes for the Enhance form status external module (who, when, old->new) to the module's View Logs page.

  As a REDCap administrator
  I want every configuration change to be written to the module's External Module Logs
  So that there is an audit trail of who changed which setting, when, and from what value to what.

  Scenario: Enable external module from Control Center
    Given I login to REDCap with the user "Test_Admin"
    When I click on the link labeled "Control Center"
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Module Manager"
    And I should NOT see "Enhance form status - v1.1.1"
    When I click on the button labeled "Enable a module"
    And I wait for 2 seconds
    Then I should see "Available Modules"
    And I click on the button labeled "Enable" in the row labeled "Enhance form status"
    And I wait for 1 second
    And I click on the button labeled "Enable"
    Then I should see "Enhance form status - v1.1.1"

  Scenario: A repeatable left blank is not logged as a change
    # Regression guard for the phantom-diff bug: REDCap returns a repeatable the
    # admin never filled as an array of empty entries ([null]), not as null, so a
    # naive comparison logged "(empty) -> [null]" for a setting nobody touched.
    # Both role lists here are repeatables, so they can reach the save
    # handler blank if left empty -- this module is directly exposed.
    Given I create a new project named "E.126.3200" by clicking on "New Project" in the menu bar, selecting "Practice / Just for fun" from the dropdown, choosing file "fixtures/cdisc_files/Project_redcap_val_nodata.xml", and clicking the "Create Project" button
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Project Module Manager"
    When I click on the button labeled "Enable a module"
    And I click on the button labeled "Enable" in the row labeled "Enhance form status - v1.1.1"
    Then I should see "Enhance form status - v1.1.1"

    Given I click on the button labeled "Configure"
    Then I should see "Configure Module"
    When I select "DataEntry" on the dropdown field labeled "1. Roles that can view the form status"
    Then I click on the button labeled "Save"
    And I should see "Enhance form status - v1.1.1"

    #VERIFY - the audit trail on the module's own View Logs page
    When I click on the link labeled "View Logs"
    Then I should see "External Module Logs"
    # No null phantom entry is logged for the update repeatable role
    And I should see 1 row in the external modules logs table
    And I should see a table header and row containing the following values in a table:
      | Module              | Message                         | UserName   |
      | enhance_form_status | Configuration changed (project) | Test_Admin |

    When I click on the first button labeled "Show Parameters"
    Then I should see "Log Entry Parameters"
    And I should see a table header and row containing the following values in a table:
      | Name      | Value               |
      | setting   | user-roles-can-view |
      | old_value | (empty)             |
      | new_value | ["1"]               |
    And I click on the button labeled "Close"
    Then I should see "External Module Logs"

    When I click on the link labeled "Manage"
    And I click on the button labeled "Configure"
    Then I should see "Configure Module"
    And I select "Monitor" on the dropdown field labeled "1. Roles that can view the form status"
    Then I click on the button labeled "Save"
    And I should see "Enhance form status - v1.1.1"

    #VERIFY - the audit trail on the module's own View Logs page
    When I click on the link labeled "View Logs"
    Then I should see "External Module Logs"
    And I should see a table header and row containing the following values in a table:
      | Module              | Message                         | UserName   |
      | enhance_form_status | Configuration changed (project) | Test_Admin |

    When I click on the first button labeled "Show Parameters"
    Then I should see "Log Entry Parameters"
    And I should see a table header and row containing the following values in a table:
      | Name      | Value               |
      | setting   | user-roles-can-view |
      | old_value | ["1"]               |
      | new_value | ["3"]               |
    And I click on the button labeled "Close"
    Then I should see "External Module Logs"

    # Disable the external module from the Control Center
    When I click on the link labeled "Control Center"
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Module Manager"
    And I click on the button labeled "Disable"
    Then I should see "Disable module?"
    When I click on the button labeled "Disable module"
    Then I should NOT see "Enhance form status - v1.1.1"

  Scenario: No hook exceptions are raised
    Given I open Email
    Then I should NOT see an email with subject "REDCap External Module Hook Exception - enhance_form_status"