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
    $repeat_instance = $_POST['repeatInstance'];

    //call the update using the module process
    \CCTC\EnhanceFormStatusModule\EnhanceFormStatusModule::setFormStatus(
        $projectId, $record, $eventId, $repeat_instance, $instrument, $statusInt);

} catch (Exception $e) {
    $module->log("Failed to write saveData in EnhanceFormStat with error",
            [
               "error" => $e->getMessage(),
            ]);
}
