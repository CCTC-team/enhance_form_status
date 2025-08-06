### Enhance form status ###

The Enhance form status module provides extra configuration for the built-in form status field (the field ending in 
'_complete'). It is used to facilitate a data management workflow by removing access to the field for some role types,
and adding oversight of forms that can automatically invalidate the 'completed' status of form in the event a data entry
user makes subsequent changes to a previously 'complete' form.

This module inserts code into the `Hooks.php` and `DataEntry.php` REDCap files when the module is enabled at a system
level. The code is removed when the module is disabled.

#### System set up ####

Enabling the module at a system level will AUTOMATICALLY do the following via the system hook
`redcap_module_system_enable`;

1. Insert code in the `Hooks.php` file - the following is inserted after the first `call` function
    ```php
    //****** inserted by Enhance Form Status module ******
    public static function redcap_save_record_enhance_form_status($result){}
    //****** end of insert ******
    ```
   This makes the new hook `redcap_save_record_enhance_form_status`available to the module

1. Insert code in the `DataEntry.php` file - the following is inserted after the existing
   `Hooks::call('redcap_save_record'...` call around line 5909
    ```php
    //****** inserted by Enhance Form Status module ******
    Hooks::call('redcap_save_record_enhance_form_status', array($field_values_changed, PROJECT_ID, $fetched, $_GET['page'], $_GET['event_id'], $group_id, ($isSurveyPage ? $_GET['s'] : null), $response_id, $_GET['instance']));
    //****** end of insert ******
    ```
   This executes the call to the hook `redcap_save_record_enhance_form_status` that is handled in the module

Disabling the module at a system level will AUTOMATICALLY do the following via the system hook
`redcap_module_system_disable`.
1. Remove the code inserted into `Hooks.php`
1. Remove the code inserted into `DataEntry.php`

When a new version of the module becomes available, it should be disabled and then re-enabled from the Control Center. Failure to do so may result in the module to malfunction.

#### Set up and configuration by project

Settings are enabled at a project level and are as follows;

- `show-form-status-inline` - determines which roles can see the form status - this is appended to the foot of the table
  as a label. This setting is used alongside the `user-roles-can-update` and `user-roles-can-view` settings; setting
  a value of 'never' or 'always' will never or always show the appended form status, regardless of the settings in the
  related two settings. When left unset, the related settings are used.
    - `never` - never show inline
    - `always` - always show inline 
- `user-roles-can-update` - a list of roles that have permission to change the form status
- `user-roles-can-view` - a list of roles that have permission to view the form status. If a user can update, they 
  can also implicitly view
- `ignore-for-form-status-check` - an action tag that marks a field as 'ignored' when automatically checking for
  invalidated completed forms
- `text-representing-in-progress` - a form status option that should be used to indicate the form is being worked on in
  order to progress from an 'incomplete' to a 'complete' state

#### Preamble

The built-in form status field (with the field name following the convention of [form name]_complete) is updatable by 
any user with edit rights on the form (without resorting to the use of action tags). The field is restricted to allowing
the following values; 'Incomplete', 'Unverified' and 'Complete'. As the status is updated, the record dashboards update
the colours of the icons from grey (no data entered), to red (incomplete), to yellow (unverified) and finally to green
(complete). This gives users a useful indication of the status of every form in a record. 

#### Objectives

This module is designed to limit the interaction with the field to Data Managers to allow central oversight of form
status. To achieve this it;

- keeps the form status field strictly the preserve of data managers; data entry users will not be permitted to
  update the field
- allows administrators to select the roles that represent users who can update and view the form status
- changes the 'unverified' option text to something equivalent to 'in progress' (see `in-progress-status-text` setting)
  to more accurately reflect the required workflow 
- provides an automated oversight function that will automatically revert the form status to 'in progress' when a form
  previously reported as 'completed' is updated. Administrators can optionally flag any fields as ignored for the 
  purpose of this function

#### Workflow

The workflow is as follows;

- a data entry user enters data into a new form - the form status is updated to Incomplete by the built-in REDCap 
  feature 
- a data manager reviews the form. Their objective is to ensure the form achieves a complete state. However, the form
  may require further intervention first because;
  - there are missing data for fields that are required
  - data entered may require querying e.g. as a result of an edit check of data quality rules
  - another reason
- if the data is considered 'complete', the form status is updated accordingly
- if the data requires further intervention, the data manager will undertake the necessary steps to progress the form
  e.g. raising data queries, and the form status is updated to 'in progress'
- there is an iterative process until the form is complete and the form status updated accordingly by the data manager.
  If no further interaction is made with the form, then the status remains as complete
- If a data entry user subsequently interacts with the form, and the following conditions are true, the form
  status will automatically revert to 'in progress' on the successful saving of the form;
  - a previously entered value is updated OR
  - a field previously left blank has data entered for the first time OR
  - a field with previous data is updated to have no data
  
  AND

  - the field is not flagged with an action tag meaning it should be ignored
  - the form's status was previously 'completed'

To simplify administration of projects and roles, data managers will be able to set the status of the form via
new buttons. This will negate the need to give data managers write access to every form and then set every other
field as readonly. Data managers do not require anything more than read access to a form to use this module.