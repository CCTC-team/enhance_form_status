<?php

namespace CCTC\EnhanceFormStatusModule;

use ExternalModules\AbstractExternalModule;
use REDCap;

class EnhanceFormStatusModule extends AbstractExternalModule {

    const HookFilePath = APP_PATH_DOCROOT . "/Classes/Hooks.php";
    const HookCode =
        '//****** inserted by Enhance Form Status module ******
public static function redcap_save_record_enhance_form_status($result){}
//****** end of insert ******' . PHP_EOL;
    const HookSearchTerm = '		// Call the appropriate method to process the return values, then return anything returned by the custom function
		return call_user_func_array(__CLASS__ . \'::\' . $function_name, array($result));
	}
';

    const DataEntryFilePath = APP_PATH_DOCROOT . "/Classes/DataEntry.php";
    const DataEntryCode =
        '//****** inserted by Enhance Form Status module ******
Hooks::call(\'redcap_save_record_enhance_form_status\', array($field_values_changed, PROJECT_ID, $fetched, $_GET[\'page\'], $_GET[\'event_id\'], $group_id, ($isSurveyPage ? $_GET[\'s\'] : null), $response_id, $_GET[\'instance\']));
//****** end of insert ******' . PHP_EOL;
    const DataEntrySearchTerm = '            if (!is_numeric($group_id)) $group_id = null;
            Hooks::call(\'redcap_save_record\', array(PROJECT_ID, $fetched, $_GET[\'page\'], $_GET[\'event_id\'], $group_id, ($isSurveyPage ? $_GET[\'s\'] : null), $response_id, $_GET[\'instance\']));
        }';

    //adds the $insertCode into the $filePath after the $searchTerm
    function addCodeToFile($filePath, $searchTerm, $insertCode) : void
    {
        $file_contents = file($filePath);
        $found = false;

        $searchArray = explode("\n", $searchTerm);
        $matched = 0;

        foreach ($file_contents as $index => $line) {
            //increment $matched so checks next line on next iteration
            if (str_contains($line, $searchArray[$matched])) {
                $matched++;
            }

            //if all the lines were found then mark as found
            if($matched == count($searchArray) - 1) {
                array_splice($file_contents, $index + 1, 0, $insertCode);
                $found = true;
                break;
            }
        }

        //write it back if was found
        if ($found) {
            file_put_contents($filePath, implode('', $file_contents));
        }
    }

    //removes the $removeCode from the $filePath
    function removeCodeFromFile($filePath, $removeCode) : void
    {
        $file_contents = file_get_contents($filePath);
        if(str_contains($file_contents, $removeCode)) {
            $modified_contents = str_replace($removeCode, "", $file_contents);
            file_put_contents($filePath, $modified_contents);
        }
    }

    function redcap_module_system_enable($version): void
    {
        //adds the code to the files as needed
        self::addCodeToFile(self::HookFilePath, self::HookSearchTerm, self::HookCode);
        self::addCodeToFile(self::DataEntryFilePath, self::DataEntrySearchTerm, self::DataEntryCode);
    }


    function redcap_module_system_disable($version): void
    {
        //removes the previously added code
        self::removeCodeFromFile(self::HookFilePath, self::HookCode);
        self::removeCodeFromFile(self::DataEntryFilePath, self::DataEntryCode);
    }


    public function validateSettings($settings): ?string
    {
        if (array_key_exists("user-roles-can-view", $settings) && array_key_exists("user-roles-can-update", $settings) ) {
            $lastIndexView = array_key_last($settings['user-roles-can-view']);
            $lastIndexUpdate = array_key_last($settings['user-roles-can-update']);
            if(empty($settings['user-roles-can-view'][$lastIndexView]) && empty($settings['user-roles-can-update'][$lastIndexUpdate])) {
                return "Please ensure at least one of the user roles to view or update Enhance Form Status External Module is configured.";
            }
        }
        return null;
    }

    function js($ajaxPath, $project_id, $record, $instrument, $event_id, $repeat_instance): void
    {
        $jsAjaxPath = json_encode($ajaxPath);
        $jsProjectId = json_encode($project_id);
        $jsRecord = json_encode($record);
        $jsInstrument = json_encode($instrument);
        $jsEventId = json_encode($event_id);
        $jsRepeatInstance = json_encode($repeat_instance);

        echo "
            <script type=\"text/javascript\">
                //removes the data query icon for the form status
                //not really required if using the function below
                function removeDataQueryIcon(formName) {
                    let dcIcon = document.getElementById('dc-icon-' + formName + '_complete');                    
                    if(dcIcon) {
                        dcIcon.parentElement.remove();
                    }
                }

                //removes the form status header row and actual field row
                function removeFormStatus(formName) {
                    let formStatus = document.getElementById(formName + '_complete-tr');                    
                    if(formStatus) {
                        formStatus.style.display = 'none';                        
                    }                    
                }
                
                function makeComplete() {                    
                    changeFormStatus(2, true);
                }
                
                function makeIncomplete() {
                    changeFormStatus(0, true);
                }
                
                function makeInProgress() {                    
                    changeFormStatus(1, true);
                }
                
                //shows the form status in line with the form
                //includes the buttons to set a new status as required             
                function showFormStatus(formStatusValue, canUpdate, inProgressText) {
                    let elem = document.querySelector('.formtbody');
                    if(elem) {                    
                        const tr = document.createElement('tr');
                
                        tr.classList.add('labelrc');
                        const td1 = document.createElement('td');
                        td1.setAttribute('style', 'padding-top: 10px; padding-bottom: 10px;');
                        const monSpan = document.createElement('span');
                        monSpan.setAttribute('style', 'padding: 2px; margin-left: 5px; font-weight: normal; margin-top: 5px;');
                       
                        if(canUpdate) {
                            const inProgressButton = document.createElement('button');
                            inProgressButton.setAttribute('type', 'button');
                            inProgressButton.classList.add('btn', 'btn-secondary', 'btn-xs', 'ml-3');
                            inProgressButton.setAttribute('style', 'background-color: #ad9a19');
                            inProgressButton.textContent = 'Set ' + inProgressText.toLowerCase();
                            inProgressButton.addEventListener('click', makeInProgress);
                            inProgressButton.disabled = formStatusValue === 'Unverified';
    
                            const completeButton = document.createElement('button');
                            completeButton.setAttribute('type', 'button');
                            completeButton.classList.add('btn', 'btn-secondary', 'btn-xs', 'ml-3');
                            completeButton.setAttribute('style', 'background-color: green');
                            completeButton.textContent = 'Set complete';
                            completeButton.addEventListener('click', makeComplete);
                            completeButton.disabled = formStatusValue === 'Complete';
                            
                            monSpan.appendChild(inProgressButton);
                            monSpan.appendChild(completeButton);                       
                        }
                        
                        td1.appendChild(monSpan);
                        tr.appendChild(td1);
                        
                        const td2 = document.createElement('td');
                        const monSpan2 = document.createElement('span');                                               
                                                                       
                        //red when incomplete, yellow for 'in progress', green for complete
                        let valCol = '#ad9a19';
                        let circleCol = 'yellow';
                        if(formStatusValue === 'Complete') {
                            valCol = 'green';
                            circleCol = 'green';
                        }
                        if(formStatusValue === 'Incomplete') {
                            valCol = 'red';
                            circleCol = 'red';
                        }
                        
                        const iconImage = document.createElement('img');
                        iconImage.setAttribute('src',  '" . APP_PATH_WEBROOT . "Resources/images/circle_' + circleCol + '.png');
                        iconImage.style.height = '16px';
                        iconImage.style.width = '16px';
                        iconImage.style.marginRight = '8px';
                        iconImage.setAttribute('aria-hidden', 'true');
                                                        
                        const mess2 = document.createElement('span');                        
                        mess2.textContent = formStatusValue === 'Unverified' ? inProgressText : formStatusValue;
                        td2.setAttribute('style', 'color: ' + valCol + '; font-weight: normal');
                        monSpan2.appendChild(iconImage);
                        monSpan2.appendChild(mess2);
                        td2.appendChild(monSpan2);
                        
                        tr.appendChild(td2);
                        
                        //add the form status buttons and status
                        elem.insertAdjacentElement('beforeend', tr);
                    }      
                }
                
            //changes the form status
            //updates the ui and writes to the db
            //reload is required to update ui
            function changeFormStatus(newStatus, showProgressSpinner = false) {    
                            
                let formStatusField = document.querySelector('[name=' + {$jsInstrument} + '_complete]');
                formStatusField.value = newStatus;

                if(showProgressSpinner) {
                    showProgress(1);
                }

                //run the ajax query to update the db
                $.post({$jsAjaxPath},
                {
                    projectId: {$jsProjectId},
                    eventId: {$jsEventId},
                    record: {$jsRecord},
                    statusInt: newStatus,
                    repeatInstance : {$jsRepeatInstance},
                    instrument : {$jsInstrument},
                }, function (data) {
                    console.log('data ' + data.result);
                    //reloads the page to refresh ui
                    window.location.reload();
                });
            }                
            </script>";
    }

    /**
     * @throws \Exception
     */
    function getFormData($project_id, $record, $fields, $event_id, $instrument, $repeat_instance) : ?array
    {

        //get all data limiting as much as possible using the getData function as it doesn't handle repeat_instance
        $params =
            array(
                'project_id' => $project_id,
                'records' => array($record),
                'fields' => $fields,
                'events' => array($event_id)
            );
        $data = REDCap::getData($params);

        //NOTE: if there is an issue with the Status not being updated when the buttons are clicked, it is likely
        //that the issue is here and the forms _complete status is not being picked up from the return array
        //from the built in REDCap::getData() function

        $isRepeatingForm = !empty($data[$record]['repeat_instances']);
        if(!$isRepeatingForm) {
            if(empty($data)) {
                return null;
            }

            $thisFormData = $data[$record][$event_id];
        } else {
            //the structure of the array depends on project settings and existing instances of the form
            if(!empty($data[$record]['repeat_instances'][$event_id])){
                if(!empty($data[$record]['repeat_instances'][$event_id][$instrument])){
                    if(!empty($data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance])) {
                        $thisFormData = $data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance];
                    } else {
                        //set the default new value that is given when new forms shown i.e. 0 for Incomplete
                        $thisFormData["{$instrument}_complete"] = 0;
                    }
                } else {
                    //the form name is not always given when only one form

                    //if a new form, the max repeat instance will be less than the given repeat_instance so
                    //return null to signify to caller that form data not found
                    $instances = array_keys($data[$record]['repeat_instances'][$event_id]['']);
                    $max = max($instances);
                    if($max < $repeat_instance) {
                        return null;
                    }

                    $thisFormData = $data[$record]['repeat_instances'][$event_id][''][$repeat_instance];
                }
            } else {
                $thisFormData = $data[$record][''][$event_id][$instrument][$repeat_instance];
            }
        }

        //if the $thisFormData array doesn't have a value for {$instrument}_complete field then
        //there is an error so raise it
        if(!isset($thisFormData["{$instrument}_complete"])) {
            $mess = "EnhanceFormStatus.getFormData: the _complete field for this form should always be found. If it isn't
            most likely an issue with getting the repeat value";
            $this->log($mess);
            throw new \Exception($mess);
        }

        return $thisFormData;
    }

    //used to work out whether the project settings for determining roles who can edit of update the status
    //are appropriate to current user
    function countUserRolesMatching($projId, $roleIds, $userName) : int
    {
        $placeholders = implode(",", array_fill(0, count($roleIds), "?"));

        $query = "
            select count(*) as count
            from
                redcap_user_roles a
                inner join redcap_user_rights b
                on
                    a.project_id = b.project_id
                    and a.role_id = b.role_id
            where
                a.project_id = ?
                and a.role_id in (" . $placeholders . ")
                and b.username = ?
            ;
            ";

        $params = array_merge([$projId], $roleIds, [$userName]);
        $result = db_query($query, $params);
        $row = db_fetch_assoc($result);
        return $row['count'];
    }

    //sets the form status directly
    //can be called in php or via ajax
    //NOTE: uses same logic as in Monitoring QR module to determine repeat params so if this needs to change
    //due to an issue then so does that
    public function setFormStatus($project_id, $record, $event_id, $repeat_instance, $instrument, $newStatus) : void
    {
        global $Proj;

        //use the project specific 'record id' - not to be confused with the record number, i.e. the first record
        //number is 1. This specifically refers to the variable name of the very first field in the first form that
        //is used to identify each record or patient. i.e. can change to something else if the user decides to
        //defaults to 'record_id', but often is 'patient_id'
        $record_id_field =  \REDCap::getRecordIdField();
        $form_status_field = "{$instrument}_complete";

        $json =
            [
                "$record_id_field" => $record,
                "$form_status_field" => $newStatus,
            ];

        //find out some initial facts
        $hasRepeatingFormsEvents = $Proj->hasRepeatingFormsEvents();
        $isRepeatingForm = $Proj->isRepeatingForm($event_id, $instrument);
        $isRepeatingFormOrEvent = $Proj->isRepeatingFormOrEvent($event_id, $instrument);

        //get the event name taking account of whether longitudinal or not
        //mimics Records.php line 5000
        if ($Proj->longitudinal) {
            $eventName =
                $event_id != null
                    ? \Event::getEventNameById($project_id, $event_id)
                    : $Proj->getUniqueEventNames($Proj->firstEventId);
        } else {
            $eventName = $Proj->getUniqueEventNames($Proj->firstEventId);
        }

        //redcap_event_name
        $json["redcap_event_name"] = $eventName;

        //redcap_repeat_instance
        if($isRepeatingFormOrEvent) {
            $json["redcap_repeat_instance"] = $repeat_instance;
        }

        //the instrument uses events and this form repeats so need this param too
        if ($hasRepeatingFormsEvents && $isRepeatingForm) {
            $json["redcap_repeat_instrument"] = $instrument;
        }

        $resp = \REDCap::saveData(
            $project_id,
            'json',
            json_encode(array($json)),
            'normal'
        );

        //raise a log entry if errors or warnings
        if(!empty($resp["errors"]) || !empty($resp["warnings"])){
            $data = [];

            if(!empty($resp["errors"])){
                $data["errors"] = json_encode($resp["errors"]);
            }

            if(!empty($resp["warnings"])){
                $data["warnings"] = json_encode($resp["warnings"]);
            }

            $this->log("setFormStatus failed to write saveData with errors or warnings", $data);
            $this->log("setFormStatus failed - sent data", $json);
        }
    }

    public function redcap_data_entry_form($project_id, ?string $record, $instrument, $event_id, $group_id, $repeat_instance): void
    {
        //NOTE: for new records, $record is null!

        if (empty($project_id)) return;
        //get the form data. if a new form, then the getFormData returns null
        //so bomb out to prevent errors

        //check whether the current user role has been set to view this
        $rolesCanView = $this->getProjectSetting("user-roles-can-view");
        $rolesCanUpdate = $this->getProjectSetting("user-roles-can-update");

        if (empty($rolesCanView[0]) && empty($rolesCanUpdate[0])) {
            echo "<script type='text/javascript'>
                    alert('Please ensure at least one of the user roles to view or update Enhance Form Status External Module is configured.');
                </script>";
            return;
        }

        $formStatusField = "{$instrument}_complete";
        $fields = [ $formStatusField ];
        $thisFormData = $this->getFormData($project_id, $record, $fields, $event_id, $instrument, $repeat_instance);

        global $Proj;

        $super = $this->isSuperUser();
        $user = $this->getUser();
        $userName = $user->getUserName();
        $ajaxPath = $this->getUrl("EnhanceFormStat_ajax.php");

        //text meaning in progress
        $inProgressText = $this->getProjectSetting("text-representing-in-progress");
        //if no value is given, just use unverified
        if(empty($inProgressText)){
            $inProgressText = "Unverified";
        }

        //add the javascript required
        $this->js($ajaxPath, $project_id, $record, $instrument, $event_id, $repeat_instance);

        $jsInstrumentSafe = json_encode($instrument);
        if(!$super) {
            echo "<script type=\"text/javascript\">
removeFormStatus({$jsInstrumentSafe});
</script>";
        }

        if(!$thisFormData) {
            return;
        }

        //for new records, $record is null so default to correct value
        $formStatusValue = "Incomplete";
        foreach (parseEnum($Proj->metadata[$formStatusField]['element_enum']) as $code=>$label) {
            if($code == $thisFormData[$formStatusField]) {
                $formStatusValue = $label;
            }
        }

        $userCanView = false;
        $userCanUpdate = false;

        if(!empty($rolesCanView)) {
            $cleanViews = array_filter($rolesCanView, function ($role) { return $role != "";});
            if(!empty($cleanViews)) {
                $userCanView = self::countUserRolesMatching($project_id, $cleanViews, $userName) > 0;
            }
        }

        if(!empty($rolesCanUpdate)) {
            $cleanUpdates = array_filter($rolesCanUpdate, function ($role) { return $role != "";});
            if(!empty($cleanUpdates)) {
                $userCanUpdate = self::countUserRolesMatching($project_id, $cleanUpdates, $userName) > 0;
            }
        }

        //this setting allows an override so can always show or never show the form status
        //when unset i.e. isset is false, the provided role settings are applicable
        $showStatusSetting = $this->getProjectSetting("show-form-status-inline");

        //if a user can update, then implicitly they can view
        if($showStatusSetting != "never" &&
            ($userCanView || $userCanUpdate || $showStatusSetting == "always")) {
            $jsFormStatusValue = json_encode($formStatusValue);
            $jsUserCanUpdate = json_encode($userCanUpdate);
            $jsInProgressText = json_encode($inProgressText);
            echo "<script type=\"text/javascript\">
showFormStatus({$jsFormStatusValue}, {$jsUserCanUpdate}, {$jsInProgressText});
</script>";
        }
    }

    function redcap_save_record_enhance_form_status($changedFields, $project_id, $record, $instrument, $event_id,
                                                    $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        //changedFields contains an array of changed field names from the new hook
        //excluding any that are ignored resulting from the ignore action tag, any remaining should immediately
        //trigger the form status update if the current status is Complete - should revert to In Progress

        global $Proj;

        //check if the current project has this module enabled. If not, just return as shouldn't be checking
        //for dirty status or updating the form status
        $formStatusModEnabled = $this->isModuleEnabled('enhance_form_status', $project_id);
        if(!$formStatusModEnabled) {
            return;
        }

        //check the current form status
        $formStatusField = "{$instrument}_complete";
        $thisFormData = $this->getFormData($project_id, $record, [$formStatusField], $event_id,
            $instrument, $repeat_instance);

        //if the current value of the form status field is not 2 (Complete) then return
        if((int)$thisFormData[$formStatusField] !== 2) {
            return;
        }

        //if any of the edited fields do not include the ignore action tag, then trigger the status update
        $triggered = false;
        $ignoreActionTag = $this->getProjectSetting("ignore-for-form-status-check");

        //removing __GROUPID__ as it causes issues when updating imported data
        $changedFields = array_filter($changedFields, function ($value) {
            return $value !== "__GROUPID__";
        });

        foreach ($changedFields as $field) {
            if(isset($ignoreActionTag)) {
                if(!str_contains($Proj->metadata[$field]["misc"], $ignoreActionTag)) {
                    $triggered = true;
                    break;
                }
            } else {
                $triggered = true;
                break;
            }
        }

        if($triggered) {
            $this->setFormStatus($project_id, $record, $event_id, $repeat_instance, $instrument, 1);
        }
    }
}












