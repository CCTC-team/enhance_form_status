### Enhance form status ###

[![Enhance Form Status EM Cypress Tests](https://github.com/CCTC-team/enhance_form_status/actions/workflows/cypress-tests.yml/badge.svg)](https://github.com/CCTC-team/enhance_form_status/actions/workflows/cypress-tests.yml)

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

When a new version of the module becomes available, the module should be disabled and then re-enabled from the Control Center at the system level. Failure to do so may cause the module to malfunction.

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

#### Automation Testing

The module includes comprehensive **Cypress automated** tests using the **Cucumber/Gherkin framework**. To set up Cypress, refer to [Setup_Overview.md](https://github.com/CCTC-team/CCTC_REDCap_Docker/blob/redcap_val/Setup_Overview.md).

All automated test scripts are located in the `automated_tests` directory. The test suite automatically picks up the scripts from this folder. These scripts can also be used to manually test the external module. The directory contains:
- Custom step definitions created by our team
- Fixture files
- User Requirement Specification (URS) documents
- Feature test scripts

**Step Definition Locations:**

Step definitions are organized across multiple locations in the `redcap_cypress` repo under `redcap_cypress/cypress/support/step_definitions/`:

- **Non-core feature step definitions** are in `redcap_cypress/cypress/support/step_definitions/noncore.js`
- **Shared EM step definitions** (used by more than one external module) are in `redcap_cypress/cypress/support/step_definitions/external_module.js`
- **EM-specific step definitions** (used only by this module) are in `automated_tests/step_definitions/external_module.js` within this module's repo

#### GitHub Actions Workflow

The module ships with a CI workflow at [.github/workflows/cypress-tests.yml](.github/workflows/cypress-tests.yml) that runs the Cypress suite end-to-end against a freshly built REDCap stack.

**Triggers**
- `push` to `main`
- Manual `workflow_dispatch`

**What it does**
1. Checks out the Enhance Form Status EM (this repo) into `enhance_form_status_em/`.
2. Clones the `redcap_val` branch of [`CCTC-team/redcap_cypress`](https://github.com/CCTC-team/redcap_cypress) and [`CCTC-team/CCTC_REDCap_Docker`](https://github.com/CCTC-team/CCTC_REDCap_Docker), and the matching REDCap version branch of [`CCTC-team/redcap_source`](https://github.com/CCTC-team/redcap_source).
3. Reads `redcap_version`, `mysql.docker_container`, `mysql.host`, and `mysql.port` from `cypress.env.json.example` so the rest of the job stays in sync with the Cypress config.
4. Injects this EM into `CCTC_REDCap_Docker/redcap_source/modules/enhance_form_status_v1.0.1` and brings the Docker stack up (`app`, `db`, `mailhog`).
5. Configures `cypress.env.json`, points `package.json` at the CCTC-team forks of `rctf` / `redcap_rsvc`, installs Cypress, and patches an `rctf` after-run handler bug.
6. Builds the spec list from `automated_tests/E.126.*.feature` (excluding `*REDUNDANT*`) and runs them via `npm run test:retry-failed` (up to 3 attempts per spec, Chrome).
7. Merges mochawesome JSON reports and uploads test results, videos, and (on failure) screenshots as artifacts retained for 30 days.

**Required repository secrets**
- `CCTC_TEAM_PAT` — PAT with read access to the CCTC-team repos, including `redcap_source`.
- `PROJECT_ID` — Cypress Cloud project ID substituted into `cypress.config.js`.
- `CYPRESS_RECORD_KEY` — Cypress Cloud record key (recording is gated by `CYPRESS_DISABLE_RECORDING`, currently set to `1`).

**Branch / version pins** (set as `env` at the top of the workflow)
- `CCTC_DOCKER_BRANCH`, `CYPRESS_BRANCH`, `RSVC_BRANCH`, `RCTF_BRANCH` — all default to `redcap_val`.
- `EM_NAME` / `EM_VERSION` — `enhance_form_status` / `v1.0.1`. Bump `EM_VERSION` when releasing a new module version so the spec glob and inject path stay aligned.

---

## Who are we

The Cambridge Cancer Trials Centre (CCTC) is a collaboration between Cambridge University Hospitals NHS Foundation Trust, the University of Cambridge, and Cancer Research UK. Founded in 2007, CCTC designs and conducts clinical trials and studies to improve outcomes for patients with cancer or those at risk of developing it. In 2011, CCTC began hosting the Cambridge Clinical Trials Unit - Cancer Theme (CCTU-CT).

CCTC has two divisions: Cancer Theme, which coordinates trial delivery, and Clinical Operations.