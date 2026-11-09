### Enhance form status ###

[![Enhance Form Status EM Cypress Tests](https://github.com/CCTC-team/enhance_form_status/actions/workflows/cypress-tests.yml/badge.svg)](https://github.com/CCTC-team/enhance_form_status/actions/workflows/cypress-tests.yml)

The Enhance form status module provides extra configuration for the built-in form status field (the field ending in 
'_complete'). It is used to facilitate a data management workflow by removing access to the field for some role types,
and adding oversight of forms that can automatically invalidate the 'completed' status of form in the event a data entry
user makes subsequent changes to a previously 'complete' form.

This module reacts to record saves via the REDCap core hook `redcap_save_record_changes`, which fires after a record is
saved and passes the old and new value of every changed field. It **requires REDCap 17.5.6 or later**.

> **Note:** Versions up to v1.1.2 patched `Hooks.php` and `DataEntry.php` on enable to inject a custom
> `redcap_save_record_enhance_form_status` hook. The module no longer patches REDCap core.

#### Upgrading to v1.2.0

Admin actions when upgrading an existing installation from v1.1.x:

1. Confirm REDCap is on **17.5.6 or later** (v1.2.0 relies on the core hook `redcap_save_record_changes`)
1. After installing v1.2.0, **disable and then re-enable the module at system level** from the Control Center. This
removes the code v1.1.x inserted into `Hooks.php` and `DataEntry.php`; View Logs shows the result of each removal

#### System set up ####

Enabling or disabling the module at a system level (system hooks `redcap_module_system_enable` and
`redcap_module_system_disable`) removes any code an earlier version inserted into `Hooks.php` and `DataEntry.php`.

When upgrading from v1.1.2 or earlier, disable and then re-enable the module from the Control Center at the system
level so that this code is removed.

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

##### Configuration audit log

Every change to the module's configuration is recorded to the module's **View Logs** page (Control Center →
External Modules → View Logs). One entry is written per changed setting, recording the setting name, its old and
new values, the user who made the change and when. The first save on a freshly configured module logs the values
that were actually set (as `(empty) -> value`); settings left blank are not logged. Old and new values are held as
log parameters, which REDCap shows to super-users via the **Show Parameters** button.

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

#### Automation Testing

The `automated_tests` directory contains the User Requirement Specification (URS) documents, Cypress/Gherkin feature scripts and fixture files for this module. The scripts can also be followed to test the module manually. To run them, see [Setup_Overview.md](https://github.com/CCTC-team/CCTC_REDCap_Docker/blob/redcap_val/Setup_Overview.md).

---

## Who are we

The Cambridge Cancer Trials Centre (CCTC) is a collaboration between Cambridge University Hospitals NHS Foundation Trust, the University of Cambridge, and Cancer Research UK. Founded in 2007, CCTC designs and conducts clinical trials and studies to improve outcomes for patients with cancer or those at risk of developing it. In 2011, CCTC began hosting the Cambridge Clinical Trials Unit - Cancer Theme (CCTU-CT).

CCTC has two divisions: Cancer Theme, which coordinates trial delivery, and Clinical Operations.