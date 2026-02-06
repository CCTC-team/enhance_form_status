# Enhance Form Status Module - Documentation

## Overview

The **Enhance Form Status** module is a REDCap External Module that provides enhanced configuration and control over the built-in form status field (`[instrument]_complete`). It facilitates data management workflows by controlling which user roles can view and update form status, and provides automatic status invalidation when completed forms are modified.

## Module Information

| Property | Value |
|----------|-------|
| Namespace | `CCTC\EnhanceFormStatusModule` |
| Framework Version | 14 |
| PHP Version | 8.0.27 - 8.2.29 |
| REDCap Version | 13.8.1 - 15.9.1 |

## Authors

- **Richard Hardy** - University of Cambridge, Cambridge Cancer Trials Centre (rmh54@cam.ac.uk)
- **Mintoo Xavier** - Cambridge University Hospital, Cambridge Cancer Trials Centre (mintoo.xavier1@nhs.net)

## Files Structure

| File | Description |
|------|-------------|
| `EnhanceFormStatusModule.php` | Main module class containing all core functionality |
| `EnhanceFormStat_ajax.php` | AJAX endpoint for handling form status updates (with authorization, input validation, and JSON responses) |
| `config.json` | Module configuration and settings definitions |
| `README.md` | General documentation |

## Core Features

### 1. Role-Based Form Status Access Control

The module restricts who can view and modify the form status field based on user roles:

- **View Access**: Configure which roles can see the form status displayed inline at the bottom of forms
- **Update Access**: Configure which roles can change the form status using the provided buttons
- Super users always retain full access

### 2. Inline Form Status Display

The module displays the current form status at the bottom of data entry forms with:

- Color-coded status indicator (red/yellow/green circles)
- Status text (Incomplete/In Progress/Complete)
- Action buttons for users with update permissions:
  - "Set in progress" button (yellow)
  - "Set complete" button (green)

### 3. Automatic Status Invalidation

When a form previously marked as "Complete" is modified, the module automatically reverts the status to "In Progress" (Unverified). This ensures data managers are alerted to review changes.

**Triggering conditions:**
- A previously entered value is updated
- A blank field receives new data
- A field with data is cleared

**Exceptions:**
- Fields tagged with the configured ignore action tag are excluded from this check

### 4. Custom "In Progress" Text

The default REDCap status "Unverified" can be renamed to custom text (e.g., "In Progress", "Under Review") to better reflect organizational workflows.

## Installation

### System-Level Enable

When the module is enabled at the system level, it automatically:

1. **Modifies `Hooks.php`** - Inserts a new hook method:
   ```php
   //****** inserted by Enhance Form Status module ******
   public static function redcap_save_record_enhance_form_status($result){}
   //****** end of insert ******
   ```

2. **Modifies `DataEntry.php`** - Inserts a hook call after the existing `redcap_save_record` call:
   ```php
   //****** inserted by Enhance Form Status module ******
   Hooks::call('redcap_save_record_enhance_form_status', array($field_values_changed, PROJECT_ID, $fetched, $_GET['page'], $_GET['event_id'], $group_id, ($isSurveyPage ? $_GET['s'] : null), $response_id, $_GET['instance']));
   //****** end of insert ******
   ```

### System-Level Disable

When disabled, the module automatically removes all injected code from `Hooks.php` and `DataEntry.php`.

## Configuration Settings

All settings are **project-level** and **super-users-only**.

### `show-form-status-inline`

Controls the visibility of the inline form status display.

| Value | Behavior |
|-------|----------|
| (unset) | Uses role-based settings (`user-roles-can-view` and `user-roles-can-update`) |
| `never` | Never shows the inline form status |
| `always` | Always shows the inline form status to all users |

### `user-roles-can-update`

- **Type**: User Role List (repeatable)
- **Description**: Roles that can change the form status via the action buttons
- Users with update access implicitly have view access

### `user-roles-can-view`

- **Type**: User Role List (repeatable)
- **Description**: Roles that can see the form status display (without edit buttons)

### `text-representing-in-progress`

- **Type**: Text
- **Default**: "Unverified"
- **Description**: Custom label to replace "Unverified" status text
- Common values: "In Progress", "Under Review", "Pending"

### `ignore-for-form-status-check`

- **Type**: Text
- **Description**: An action tag name (e.g., `@IGNORE_STATUS_CHECK`)
- Fields with this action tag will not trigger automatic status invalidation when modified

## Technical Implementation

### REDCap Hooks Used

| Hook | Purpose |
|------|---------|
| `redcap_module_system_enable` | Injects code into core REDCap files on module enable |
| `redcap_module_system_disable` | Removes injected code on module disable |
| `redcap_data_entry_form` | Renders the inline status display and JavaScript |
| `redcap_save_record_enhance_form_status` | Custom hook for detecting field changes and auto-invalidating status |

### Key Methods

#### `setFormStatus($project_id, $record, $event_id, $repeat_instance, $instrument, $newStatus)`

Instance method that updates the form status. Handles:
- Standard forms
- Longitudinal projects
- Repeating forms and events

#### `getFormData($project_id, $record, $fields, $event_id, $instrument, $repeat_instance)`

Retrieves form data using `REDCap::getData()`, handling various project configurations:
- Non-repeating forms
- Repeating forms
- Repeating events

#### `countUserRolesMatching($projId, $roleIds, $userName)`

Checks if a user belongs to any of the specified roles for permission validation.

### JavaScript Functions

The module injects these client-side functions:

| Function | Description |
|----------|-------------|
| `removeFormStatus(formName)` | Hides the default form status field |
| `showFormStatus(formStatusValue, canUpdate, inProgressText)` | Renders the inline status display |
| `changeFormStatus(newStatus, showProgressSpinner)` | Updates status via AJAX and reloads page on success |
| `makeComplete()` | Sets status to Complete (2) |
| `makeIncomplete()` | Sets status to Incomplete (0) |
| `makeInProgress()` | Sets status to Unverified/In Progress (1) |

### AJAX Endpoint (`EnhanceFormStat_ajax.php`)

The AJAX endpoint handles form status update requests with the following safeguards:

- **Authorization**: Verifies the requesting user's role is listed in the `user-roles-can-update` project setting before processing the request. Returns HTTP 403 if unauthorized.
- **Input Validation**: Validates that `projectId` and `eventId` are numeric, `statusInt` is a valid form status value (0, 1, or 2), and all required fields are present. Falls back to instance 1 when `repeatInstance` is not provided.
- **JSON Responses**: Returns structured JSON responses (`{"result": "success"}` or `{"result": "error", "message": "..."}`) with appropriate HTTP status codes (200, 403, or 500).

### Security Measures

| Measure | Description |
|---------|-------------|
| SQL Parameterization | All SQL queries use parameterized placeholders (`?`) to prevent injection |
| XSS Protection | All PHP values interpolated into JavaScript use `json_encode()` for safe escaping |
| Authorization | AJAX endpoint verifies user role permissions before processing status changes |
| Input Validation | AJAX endpoint validates all input types and values before use |

## Workflow Example

1. **Data Entry User** enters data into a new form
   - Form status automatically set to "Incomplete" by REDCap

2. **Data Manager** reviews the form
   - If complete: Clicks "Set complete" button
   - If needs work: Clicks "Set in progress" and raises data queries

3. **Subsequent data entry changes** on a completed form
   - Module automatically reverts status to "In Progress"
   - Data Manager is alerted to re-review

4. **Data Manager** reviews changes and marks complete again

## Important Notes

- **Module Updates**: When upgrading to a new version, disable and re-enable the module at the system level to ensure the injected code is updated correctly
- **Validation**: At least one user role must be configured for either view or update access
- The `__GROUPID__` field is automatically excluded from triggering status invalidation (to handle data imports)
- Super users can always see the form status display regardless of role settings

## Compatibility

- Supports standard single-event projects
- Supports longitudinal projects with multiple events
- Supports repeating instruments
- Supports repeating events
