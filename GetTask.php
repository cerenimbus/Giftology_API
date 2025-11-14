<?php
//***************************************************************
// Cerenimbus Inc.
// 1175 N 910 E, Orem UT 84097
// THIS IS NOT OPEN SOURCE. DO NOT USE WITHOUT PERMISSION
//***************************************************************
// Copyright Cerenimbus
// ALL RIGHTS RESERVED. Proprietary and confidential
//***************************************************************
//
// File: GetTask.php
// Description: Retrieves a single task based on the provided task serial.
// Called by: Modules or services that need to fetch a specific task for a user based on task serial
// Author: Alfred louis Carpio
// Date: 10/27/25
// History: 10/27/25 initial version created
//          11/11/25 updated author name, error messages and stub
//***************************************************************

$debugflag = false;
// RKG 10/20/25 allow the debugflag to be switched on in the get method call
if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}
// this stops the java script from being written because this is a microservice API
$suppress_javascript = true;

//-------------------------------------
// Include necessary function files with error checking
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else {
    if (!file_exists('../ccu_include/ccu_function.php')) {
        echo "Cannot find required file ../ccu_include/ccu_function.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/ccu_function.php');
}

// Include send_output.php with error checking
if (file_exists('send_output.php')) {
    require_once('send_output.php');
} else {
    if (!file_exists('../ccu_include/send_output.php')) {
        echo "Cannot find required file ../ccu_include/send_output.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/send_output.php');
}

debug("GetTask");

//-------------------------------------
// Get the values passed in
$device_ID          = urldecode($_REQUEST["DeviceID"] ?? ''); //-alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate        = $_REQUEST["Date"] ?? ''; //- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$authorization_code = $_REQUEST["AC"] ?? '';// 40 character authorization code 
$key                = $_REQUEST["Key"] ?? ''; // alphanumeric 40, SHA-1 hash of the device ID + date string (MM/DD/YYYY-HH:mm) + AuthorizationCode
$language           = $_REQUEST["Language"] ?? 'EN'; // Standard Language code from mobile [e.g EN for English]
$mobile_version     = $_REQUEST["MobileVersion"] ?? '1'; //hardcoded value in software
$task_serial        = $_REQUEST["Task"] ?? ''; //integer task serial number

//-------------------------------------
// VALIDATE REQUIRED PARAMETERS
if (empty($device_ID) || empty($authorization_code) || empty($key) || empty($task_serial)) {
    $output = "<ResultInfo>
        <ErrorNumber>101</ErrorNumber>
        <Result>Fail</Result>
        <Message>Request not recognized</Message>
    </ResultInfo>";
    send_output($output);
}


// ALC 10/29/25 THIS IS A SAMPLE STUB. The purpose is to always return a successful message, for testing
    $output = '<ResultInfo>
        <ErrorNumber>0</ErrorNumber>
        <Result>Success</Result>
        <Message>Stub single task (sample data)</Message>
        <Task>
            <Name>System Maintenance</Name>
            <Serial>1003</Serial>
            <Contact>IT Department</Contact>
            <Date>10/30/2025</Date>
            <Status>0</Status>
        </Task>
    </ResultInfo>';

    send_output($output);
    exit;

//-------------------------------------
// COMPUTE AND VERIFY HASH
$hash = sha1($device_ID . $requestDate . $authorization_code);
if ($hash != $key) {
    debug("Hash mismatch: expected $hash but got $key");
    $output = "<ResultInfo>
        <ErrorNumber>102</ErrorNumber>
        <Result>Fail</Result>
        <Message>Security Failure- incorrect hash key</Message>
    </ResultInfo>";
    send_output($output);
}

//-------------------------------------
// VERSION CHECK
$current_system_version = get_setting("system", "current_giftology_version");
debug("Current system version: " . $current_system_version);
if ($current_system_version > (int)$mobile_version) {
    $output = "<ResultInfo>
        <ErrorNumber>106</ErrorNumber>
        <Result>Fail</Result>
        <Message>Giftology version not current</Message>
    </ResultInfo>";
    send_output($output);
}

//-------------------------------------
// GET EMPLOYEE BASED ON AUTHORIZATION CODE
$sql = 'SELECT * FROM authorization_code 
        JOIN employee ON authorization_code.employee_serial = employee.employee_serial 
        WHERE employee.deleted_flag=0 
        AND authorization_code.authorization_code="' . mysqli_real_escape_string($mysqli_link, $authorization_code) . '"';
debug("Authorization SQL: $sql");

$result = mysqli_query($mysqli_link, $sql);
if (!$result || mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
        <ErrorNumber>103</ErrorNumber>
        <Result>Fail</Result>
        <Message>MySQL programming error</Message>
    </ResultInfo>";
    send_output($output);
}

$auth_row = mysqli_fetch_assoc($result);
if (!$auth_row) {
    $output = "<ResultInfo>
        <ErrorNumber>105</ErrorNumber>
        <Result>Fail</Result>
        <Message>Username and password does not match Employee records.</Message>
    </ResultInfo>";
    send_output($output);
}

$employee_serial = $auth_row["employee_serial"];

//-------------------------------------
// FETCH A SINGLE TASK BY SERIAL
$sql = 'SELECT e.event_serial, e.event_date, e.status,
               wd.workflow_detail_name,
               w.workflow_name AS contact_name
        FROM event e
        JOIN workflow_detail wd ON e.workflow_detail_serial = wd.workflow_detail_serial
        JOIN workflow w ON wd.workflow_serial = w.workflow_serial
        WHERE e.event_serial = ' . intval($task_serial) . '
        AND e.deleted_flag = 0
        LIMIT 1';
debug("Task SQL: $sql");

$result = mysqli_query($mysqli_link, $sql);
if (!$result || mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
        <ErrorNumber>103</ErrorNumber>
        <Result>Fail</Result>
        <Message>MySQL programming error</Message>
    </ResultInfo>";
    send_output($output);
}

$task = mysqli_fetch_assoc($result);
if (!$task) {
    $output = "<ResultInfo>
        <ErrorNumber>0</ErrorNumber>
        <Result>Fail</Result>
        <Message>No task found</Message>
    </ResultInfo>";
    send_output($output);
}

//-------------------------------------
// BUILD XML OUTPUT
$output = '<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Task found</Message>
    <Task>
        <Name>' . htmlspecialchars($task["workflow_detail_name"]) . '</Name>
        <Serial>' . intval($task["event_serial"]) . '</Serial>
        <Contact>' . htmlspecialchars($task["contact_name"]) . '</Contact>
        <Date>' . $task["event_date"]. '</Date>
        <Status>' .$task["status"] . '</Status>
    </Task>
</ResultInfo>';

send_output($output);
exit;

?>
