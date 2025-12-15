<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED. Proprietary and confidential

 Description:
    UpdateTask.php

 Called by:
    Giftology Mobile App / VCService

 Author: Karl Matthew Linao
 Date:   11/28/2025

 History:
    11/28/2025   KML - Created based on UpdateFeedback stub.
    11/28/2025   KML - Reviewed and cleaned up comments.
    12/09/2025   KML - Added proper security hash validation before stub.
 ******************************************************************/

//---------------------------------------------------------------
// Initialization
//---------------------------------------------------------------
require_once('send_output.php');
$debugflag = false;
$suppress_javascript = true;

//---------------------------------------------------------------
// File includes
//---------------------------------------------------------------
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else if (file_exists('../ccu_include/ccu_function.php')) {
    require_once('../ccu_include/ccu_function.php');
} else {
    echo "Cannot find ccu_function.php. Contact programmer.";
    exit;
}

if (file_exists('ccu_include/ccu_password_security.php')) {
    require_once('ccu_include/ccu_password_security.php');
} else if (file_exists('../ccu_include/ccu_password_security.php')) {
    require_once('../ccu_include/ccu_password_security.php');
} else {
    echo "Cannot find ccu_password_security.php. Contact programmer.";
    exit;
}

//---------------------------------------------------------------
// Logging
//---------------------------------------------------------------
debug("UpdateTask called");

$raw_request  = var_export($_REQUEST, true);
$clean_request = str_replace('"', "'", $raw_request);
debug("Web log: " . $clean_request);

//---------------------------------------------------------------
// Retrieve Parameters
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
// Language Setup
//---------------------------------------------------------------
set_language($language);

//---------------------------------------------------------------
// SECURITY HASH CHECK (ADDED)
//---------------------------------------------------------------
$expectedKey = sha1($deviceID . $requestDate . $authorization);

debug("DeviceID: $deviceID");
debug("RequestDate: $requestDate");
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
// *** STUB MODE (Runs only after security passes) ***
//---------------------------------------------------------------
/*
$output = "
<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Task update accepted (stub mode)</Message>
</ResultInfo>";
send_output($output);
exit;
*/

//---------------------------------------------------------------
// Input Validation
//---------------------------------------------------------------
if ($taskID === "" || !is_numeric($taskID)) {
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
    updated = NOW();
";

debug("Update SQL: $update_sql");

$update_result = mysqli_query($mysqli_link, $update_sql);

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
// Success Response
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
