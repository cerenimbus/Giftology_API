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
// File: UpdateTask.php
// Description: Updates the status of a specific task for a user based on authorization code validation.
//
// Called by: Giftology Mobile App / VCService
//
// Author: Karl Matthew Linao
// Created: 11/28/2025
//
// History:
// 11/28/2025  KML - Created based on UpdateFeedback stub.
// 11/28/2025  KML - Reviewed and cleaned up comments.
// 12/09/2025  KML - Added proper security hash validation before stub.
// 12/15/2025  KML - Added authorization code database validation.
// 12/16/2025  KML - Testing authorization validation logic.
// 12/19/2025  KML - Removed stub mode and enabled real DB response.
// 01/05/2026  KML - Fixed ambiguous status handling and input checks.
// 01/06/2026  KML - Improved parameter validation and stability.
//***************************************************************

//---------------------------------------------------------------
// Initialization
//---------------------------------------------------------------
$debugflag = false;
$suppress_javascript = true;

if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

// this stops javascript output since this is a microservice API
$suppress_javascript = true;

//---------------------------------------------------------------
// Required includes
//---------------------------------------------------------------
if (file_exists('send_output.php')) {
    require_once('send_output.php');
} else if (file_exists('../ccu_include/send_output.php')) {
    require_once('../ccu_include/send_output.php');
} else {
    echo "Cannot find required file send_output.php. Contact programmer.";
    exit;
}

if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else if (file_exists('../ccu_include/ccu_function.php')) {
    require_once('../ccu_include/ccu_function.php');
} else {
    echo "Cannot find required file ccu_function.php. Contact programmer.";
    exit;
}

//---------------------------------------------------------------
// Logging (debug only)
//---------------------------------------------------------------
debug("UpdateTask called");
debug("Incoming request: " . var_export($_REQUEST, true));

//---------------------------------------------------------------
// Required parameter validation
//---------------------------------------------------------------
$required = ["DeviceID", "Date", "Key", "AC", "Language", "MobileVersion", "Task", "Status"];

foreach ($required as $param) {
    if (!isset($_REQUEST[$param]) || trim($_REQUEST[$param]) === "") {
        $output = "
        <ResultInfo>
            <ErrorNumber>101</ErrorNumber>
            <Result>Fail</Result>
            <Message>Missing parameter: $param</Message>
        </ResultInfo>";
        send_output($output);
        exit;
    }
}

//---------------------------------------------------------------
// Retrieve parameters
//---------------------------------------------------------------
$deviceID      = urldecode($_REQUEST["DeviceID"]);
$requestDate   = $_REQUEST["Date"];
$key           = $_REQUEST["Key"];
$authorization = $_REQUEST["AC"];
$language      = $_REQUEST["Language"];
$mobileVersion = $_REQUEST["MobileVersion"];
$taskID        = $_REQUEST["Task"];
$status        = $_REQUEST["Status"];   // must be "0" or "1"

//---------------------------------------------------------------
// Language Setup
//---------------------------------------------------------------
set_language($language);

//---------------------------------------------------------------
// SECURITY HASH VALIDATION
//---------------------------------------------------------------
$expectedKey = sha1($deviceID . $requestDate . $authorization);

debug("Received Key: $key");
debug("Expected Key: $expectedKey");

if ($expectedKey !== $key) {
    debug("Security hash mismatch");

    $output = "
    <ResultInfo>
        <ErrorNumber>102</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err102a") . "</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Mobile version check
//---------------------------------------------------------------
$current_mobile_version = get_setting("system", "current_mobile_version");
debug("current_mobile_version = " . $current_mobile_version);

if ($current_mobile_version > $mobileVersion) {
    $output = "
    <ResultInfo>
        <ErrorNumber>106</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err106") . "</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// AUTHORIZATION CODE VALIDATION
//---------------------------------------------------------------
$auth_sql = "
SELECT * FROM authorization_code 
JOIN user ON authorization_code.user_serial = user.user_serial
WHERE user.deleted_flag = 0
AND authorization_code.authorization_code = '" . mysqli_real_escape_string($mysqli_link, $authorization) . "'
";

debug("Authorization SQL: " . $auth_sql);

$auth_result = mysqli_query($mysqli_link, $auth_sql);

if (mysqli_error($mysqli_link)) {
    $err = mysqli_error($mysqli_link);

    $output = "
    <ResultInfo>
        <ErrorNumber>201</ErrorNumber>
        <Result>Fail</Result>
        <Message>Authorization validation DB error: $err</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

$auth_count = mysqli_num_rows($auth_result);

if ($auth_count == 0) {
    $output = "
    <ResultInfo>
        <ErrorNumber>202</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err202a") . "</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Input validation
//---------------------------------------------------------------
if (!is_numeric($taskID)) {
    $output = "
    <ResultInfo>
        <ErrorNumber>104</ErrorNumber>
        <Result>Fail</Result>
        <Message>Invalid Task ID</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

if ($status !== "0" && $status !== "1") {
    $output = "
    <ResultInfo>
        <ErrorNumber>105</ErrorNumber>
        <Result>Fail</Result>
        <Message>Invalid task status. Must be 0 or 1.</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Insert / Update Task Status
//---------------------------------------------------------------
$safeDevice = mysqli_real_escape_string($mysqli_link, $deviceID);
$safeTask   = mysqli_real_escape_string($mysqli_link, $taskID);
$safeStatus = mysqli_real_escape_string($mysqli_link, $status);

$update_sql = "
INSERT INTO user_tasks
SET
    device_id   = '$safeDevice',
    task_serial = '$safeTask',
    status_flag = '$safeStatus',
    mobile_version = '" . mysqli_real_escape_string($mysqli_link, $mobileVersion) . "',
    updated = NOW()
ON DUPLICATE KEY UPDATE
    status_flag = VALUES(status_flag),
    updated = NOW();
";

debug("Update SQL: " . $update_sql);

mysqli_query($mysqli_link, $update_sql);

if (mysqli_error($mysqli_link)) {
    $err = mysqli_error($mysqli_link);

    $output = "
    <ResultInfo>
        <ErrorNumber>103</ErrorNumber>
        <Result>Fail</Result>
        <Message>Database error: $err</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// SUCCESS RESPONSE
//---------------------------------------------------------------
$output = "
<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Task status successfully updated.</Message>
</ResultInfo>";

send_output($output);
exit;

?>
