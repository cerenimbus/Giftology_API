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
// Description: Updates the status flag for a user task after validating
//              security hash and verifying authorization code.
//
// Called by: Giftology Mobile App / VCService
//
// Author: Karl Matthew Linao
// Created: 11/28/2025
//
// History:
// 11/28/2025  KML - Created based on UpdateFeedback stub.
// 12/09/2025  KML - Added full security hash validation.
// 12/15/2025  KML - Added authorization code database verification.
// 01/06/2026  KML - Cleaned logic flow and improved stability.
//***************************************************************

//---------------------------------------------------------------
// Initialization
//---------------------------------------------------------------
$debugflag = false;
$suppress_javascript = true;

if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

//---------------------------------------------------------------
// Include required function files
//---------------------------------------------------------------
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else if (file_exists('../ccu_include/ccu_function.php')) {
    require_once('../ccu_include/ccu_function.php');
} else {
    echo "Cannot find required file ccu_function.php. Contact programmer.";
    exit;
}

if (file_exists('send_output.php')) {
    require_once('send_output.php');
} else if (file_exists('../ccu_include/send_output.php')) {
    require_once('../ccu_include/send_output.php');
} else {
    echo "Cannot find required file send_output.php. Contact programmer.";
    exit;
}

//---------------------------------------------------------------
// Logging (for internal debug log only)
//---------------------------------------------------------------
debug("UpdateTask called");
debug("Incoming request: " . var_export($_REQUEST, true));

//---------------------------------------------------------------
// Retrieve parameters (YOUR EXACT REQUIRED LINES)
//---------------------------------------------------------------
$deviceID      = $_REQUEST["DeviceID"] ?? "";
$requestDate   = $_REQUEST["Date"] ?? "";
$key           = $_REQUEST["Key"] ?? "";
$authorization = $_REQUEST["AC"] ?? "";
$language      = $_REQUEST["Language"] ?? "";
$mobileVersion = $_REQUEST["MobileVersion"] ?? "";
$taskID        = $_REQUEST["Task"] ?? "";
$status        = $_REQUEST["Status"] ?? ""; // 0 or 1

//---------------------------------------------------------------
// Setup language for messages
//---------------------------------------------------------------
set_language($language);

//---------------------------------------------------------------
// Security hash validation
// Formula: sha1(DeviceID + Date + AuthorizationCode)
//---------------------------------------------------------------
$expectedKey = sha1($deviceID . $requestDate . $authorization);

debug("ExpectedKey: $expectedKey");
debug("ReceivedKey: $key");

if ($expectedKey !== $key) {

    $output = "<ResultInfo>
    <ErrorNumber>102</ErrorNumber>
    <Result>Fail</Result>
    <Message>" . get_text("vcservice", "_err102a") . "</Message>
</ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Mobile version validation
//---------------------------------------------------------------
$current_mobile_version = get_setting("system", "current_mobile_version");
debug("current_mobile_version = " . $current_mobile_version);

if ($current_mobile_version > $mobileVersion) {

    $output = "<ResultInfo>
    <ErrorNumber>106</ErrorNumber>
    <Result>Fail</Result>
    <Message>" . get_text("vcservice", "_err106") . "</Message>
</ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Validate authorization code against database
//---------------------------------------------------------------
$auth_sql = "
SELECT user_serial 
FROM authorization_code 
WHERE deleted_flag = 0
AND authorization_code = '" . mysqli_real_escape_string($mysqli_link, $authorization) . "'
";

debug("Authorization SQL: $auth_sql");

$auth_result = mysqli_query($mysqli_link, $auth_sql);

if (mysqli_error($mysqli_link)) {

    $err = mysqli_error($mysqli_link);

    $output = "<ResultInfo>
    <ErrorNumber>201</ErrorNumber>
    <Result>Fail</Result>
    <Message>Authorization validation database error: $err</Message>
</ResultInfo>";

    send_output($output);
    exit;
}

if (mysqli_num_rows($auth_result) == 0) {

    $output = "<ResultInfo>
    <ErrorNumber>202</ErrorNumber>
    <Result>Fail</Result>
    <Message>" . get_text("vcservice", "_err202a") . "</Message>
</ResultInfo>";

    send_output($output);
    exit;
}

$auth_row = mysqli_fetch_assoc($auth_result);
$userSerial = intval($auth_row["user_serial"]);

//---------------------------------------------------------------
// Input validation
//---------------------------------------------------------------
if ($taskID === "" || !is_numeric($taskID)) {

    $output = "<ResultInfo>
    <ErrorNumber>104</ErrorNumber>
    <Result>Fail</Result>
    <Message>Invalid Task ID</Message>
</ResultInfo>";

    send_output($output);
    exit;
}

if ($status !== "0" && $status !== "1") {

    $output = "<ResultInfo>
    <ErrorNumber>105</ErrorNumber>
    <Result>Fail</Result>
    <Message>Invalid task status. Must be 0 or 1.</Message>
</ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Insert or update user task status (ORIGINAL INTENT)
//---------------------------------------------------------------
$update_sql = "
INSERT INTO user_tasks
SET
    device_id = '" . mysqli_real_escape_string($mysqli_link, $deviceID) . "',
    task_serial = '" . mysqli_real_escape_string($mysqli_link, $taskID) . "',
    status_flag = '" . mysqli_real_escape_string($mysqli_link, $status) . "',
    mobile_version = '" . mysqli_real_escape_string($mysqli_link, $mobileVersion) . "',
    updated = NOW()
ON DUPLICATE KEY UPDATE
    status_flag = VALUES(status_flag),
    updated = NOW()
";

debug("Update SQL: $update_sql");

mysqli_query($mysqli_link, $update_sql);

if (mysqli_error($mysqli_link)) {

    $err = mysqli_error($mysqli_link);

    $output = "<ResultInfo>
    <ErrorNumber>103</ErrorNumber>
    <Result>Fail</Result>
    <Message>Database error: $err</Message>
</ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Success Response
//---------------------------------------------------------------
$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Task status successfully updated.</Message>
</ResultInfo>";

send_output($output);
exit;

?>
