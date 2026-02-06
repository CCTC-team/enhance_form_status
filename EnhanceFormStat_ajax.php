<?php

//receives a request to update the db for the form status

header('Content-Type: application/json');

global $module;

try {

    global $Proj;

    //get all the parameters
    $projectId = $_POST['projectId'] ?? throw new Exception("EnhanceFormStat - project id is missing");
    $eventId = $_POST['eventId'] ?? throw new Exception("EnhanceFormStat - event id is missing");
    $record = $_POST['record'] ?? throw new Exception("EnhanceFormStat - record is missing");
    $statusInt = $_POST['statusInt'] ?? throw new Exception("EnhanceFormStat - statusInt is missing");
    $instrument = $_POST['instrument'] ?? throw new Exception("EnhanceFormStat - instrument is missing");
    //not always given
    $repeat_instance = $_POST['repeatInstance'] ?? 1;

    //validate input types
    if (!is_numeric($projectId)) throw new Exception("EnhanceFormStat - project id must be numeric");
    if (!is_numeric($eventId)) throw new Exception("EnhanceFormStat - event id must be numeric");
    $statusInt = (int)$statusInt;
    if (!in_array($statusInt, [0, 1, 2], true)) throw new Exception("EnhanceFormStat - invalid status value");
    $projectId = (int)$projectId;
    $eventId = (int)$eventId;

    //authorize: verify the current user's role is in the user-roles-can-update setting
    $user = $module->getUser();
    $userName = $user->getUserName();
    $rolesCanUpdate = $module->getProjectSetting("user-roles-can-update");
    $authorized = false;
    if (!empty($rolesCanUpdate)) {
        $cleanUpdates = array_filter($rolesCanUpdate, function ($role) { return $role != ""; });
        if (!empty($cleanUpdates)) {
            $authorized = $module->countUserRolesMatching($projectId, $cleanUpdates, $userName) > 0;
        }
    }
    if (!$authorized) {
        http_response_code(403);
        echo json_encode(["result" => "error", "message" => "User is not authorized to update form status"]);
        return;
    }

    //call the update using the module process
    $module->setFormStatus(
        $projectId, $record, $eventId, $repeat_instance, $instrument, $statusInt);

    echo json_encode(["result" => "success"]);

} catch (Exception $e) {
    $module->log("Failed to write saveData in EnhanceFormStat with error",
            [
               "error" => $e->getMessage(),
            ]);
    http_response_code(500);
    echo json_encode(["result" => "error", "message" => $e->getMessage()]);
}
