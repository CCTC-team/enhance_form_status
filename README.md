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

The module ships with a CI workflow at [.github/workflows/cypress-tests.yml](.github/workflows/cypress-tests.yml) that runs this module's own Cypress specs end-to-end against a prebuilt all-in-one REDCap image, using a self-contained Cypress runner image. There is no 3-container compose, no host `npm ci`, and no cloning of the harness at runtime — both images are published ahead of time and pulled from GHCR.

**Triggers**
- `push` to `main` (ignoring doc-only changes: `**/*.md`, `LICENSE`, `.gitignore`, `docs/**`)
- Manual `workflow_dispatch`

**What it does** (`cypress-tests` job)
1. Checks out the Enhance Form Status EM (this repo) into `enhance_form_status_em/`.
2. Logs in to GHCR and pulls two prebuilt images: `redcap-aio` (REDCap + MariaDB + MailHog in one container via supervisord) and `cypress-runner-aio` (the suite with `rctf` + `redcap_rsvc` baked in).
3. Stages the EM under test — strips `.git`/`.github` so only the module payload remains.
4. Starts the AIO container (ports `8443`/`8025`, volume `cctc_mariadb_data`), bind-mounting **this commit's** EM over the image's `modules/enhance_form_status_v1.1.1` so REDCap serves the code under test with no rebuild.
5. Waits for REDCap to come up (first boot initialises the DB).
6. Runs the runner image, which copies this module's `automated_tests` out of the container and runs only its `E.126.*` specs (excluding `*REDUNDANT*`), up to 3 attempts per spec, on Chromium. It reaches the DB/files over the mounted Docker socket and the UI over host networking.
7. Uploads the mochawesome reports (and, on failure, screenshots) as artifacts retained for 7 days.

**Follow-on jobs**
- `prune-artifacts` — deletes artifacts from older runs, keeping only the latest 2.
- `publish-report` — merges the run's mochawesome JSON into one combined HTML report and publishes it to GitHub Pages (report named `enhance_form_status_v1.1.1.html`, also served at the Pages root as `index.html`).

**Required repository secrets**
- `CCTC_TEAM_PAT` — PAT with `read:packages` for the private `redcap-aio` / `cypress-runner-aio` GHCR images.

**Version pins** (set as `env` at the top of the workflow)
- `AIO_IMAGE` / `RUNNER_IMAGE` — the GHCR image refs; both must be built for the **same** REDCap version.
- `EM_NAME` / `EM_VERSION` — `enhance_form_status` / `v1.1.1`. `EM_MODULE` (`enhance_form_status_v1.1.1`) is the directory REDCap discovers the module by and the runner uses to locate the specs. Bump `EM_VERSION`/`EM_MODULE` when releasing a new module version so the mount path and spec discovery stay aligned.

---

## Who are we

The Cambridge Cancer Trials Centre (CCTC) is a collaboration between Cambridge University Hospitals NHS Foundation Trust, the University of Cambridge, and Cancer Research UK. Founded in 2007, CCTC designs and conducts clinical trials and studies to improve outcomes for patients with cancer or those at risk of developing it. In 2011, CCTC began hosting the Cambridge Clinical Trials Unit - Cancer Theme (CCTU-CT).

CCTC has two divisions: Cancer Theme, which coordinates trial delivery, and Clinical Operations.