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
    11/28/2025   KML - Created based on UpdateFeedback stub and 
                       modified for Giftology VCService Task Update spec.
    11/28/2025   KML - Reviewed and cleaned up comments. change from rrservice to vcservice.

 ******************************************************************/

//---------------------------------------------------------------
// Initialization and configuration
//---------------------------------------------------------------
require_once('send_output.php');
$debugflag = false;
$suppress_javascript = true;

// Include function file
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else {
    if (!file_exists('../ccu_include/ccu_function.php')) {
        echo "Cannot find required file ../ccu_include/ccu_function.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/ccu_function.php');
}

// Include security hashing
if (file_exists('ccu_include/ccu_password_security.php')) {
    require_once('ccu_include/ccu_password_security.php');
} else {
    if (!file_exists('../ccu_include/ccu_password_security.php')) {
        echo "Cannot find required file ../ccu_include/ccu_password_security.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/ccu_password_security.php');
}

//---------------------------------------------------------------
// Logging setup
//---------------------------------------------------------------
debug("UpdateTask called");

$text = var_export($_REQUEST, true);
$test = str_replace(chr(34), "'", $text);
$log_sql = 'INSERT web_log SET method="UpdateTask", text="' . $test . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log: " . $log_sql);

//---------------------------------------------------------------
// Retrieve parameters
//---------------------------------------------------------------
$deviceID        = $_REQUEST["DeviceID"] ?? "";
$requestDate     = $_REQUEST["Date"] ?? "";
$key             = $_REQUEST["Key"] ?? "";
$authorization   = $_REQUEST["AC"] ?? "";
$language        = $_REQUEST["Language"] ?? "";
$mobileVersion   = $_REQUEST["MobileVersion"] ?? "";
$taskID          = $_REQUEST["Task"] ?? "";
$status          = $_REQUEST["Status"] ?? "";   // 1 or 0

//---------------------------------------------------------------
// *** STUB SECTION ***
// Always return success for offline testing
//---------------------------------------------------------------
/*
$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Task update accepted (stub mode)</Message>
</ResultInfo>";
send_output($output);
exit;
*/

//---------------------------------------------------------------
// Language Setup
//---------------------------------------------------------------
set_language($language);

//---------------------------------------------------------------
// Security Hash Calculation
//---------------------------------------------------------------
$expectedKey = sha1($deviceID . $requestDate . $authorization);

debug("DeviceID: $deviceID");
debug("RequestDate: $requestDate");
debug("Key Received: $key");
debug("ExpectedKey: $expectedKey");

//---------------------------------------------------------------
// Security validation
//---------------------------------------------------------------
if ($expectedKey !== $key) {
    debug("Hash security mismatch");

    $output = "<ResultInfo>
<ErrorNumber>102</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("vcservice", "_err102a") . "</Message>
</ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Validate task input
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
// Insert or Update Task Status in Database
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

    $output = "<ResultInfo>
<ErrorNumber>103</ErrorNumber>
<Result>Fail</Result>
<Message>Database error: $err</Message>
</ResultInfo>";
    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Success Output
//---------------------------------------------------------------
$output = "<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Task status successfully updated.</Message>
</ResultInfo>";

send_output($output);
exit;

?>
