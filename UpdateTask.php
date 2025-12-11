<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED. Proprietary and confidential

 Description:
    UpdateTask.php (LIVE MODE – performs DB write)

 Called by:
    Giftology Mobile App / VCService

 Author: Karl Matthew Linao
 Date:   11/28/2025
 History:
    11/28/2025   KML - Initial creation
    12/09/2025   KML - Added security hash validation
    12/11/2025   KML - error fix, full DB update
 ******************************************************************/

//---------------------------------------------------------------
// Initialization
//---------------------------------------------------------------
require_once('send_output.php');
$debugflag = false;
$suppress_javascript = true;

//---------------------------------------------------------------
// Includes
//---------------------------------------------------------------
if (file_exists('ccu_include/ccu_function.php'))
    require_once('ccu_include/ccu_function.php');
else if (file_exists('../ccu_include/ccu_function.php'))
    require_once('../ccu_include/ccu_function.php');
else { echo "Missing ccu_function.php"; exit; }

if (file_exists('ccu_include/ccu_password_security.php'))
    require_once('ccu_include/ccu_password_security.php');
else if (file_exists('../ccu_include/ccu_password_security.php'))
    require_once('../ccu_include/ccu_password_security.php');
else { echo "Missing ccu_password_security.php"; exit; }

//---------------------------------------------------------------
// Logging
//---------------------------------------------------------------
debug("UpdateTask called");
$clean_request = str_replace('"', "'", var_export($_REQUEST, true));
debug("Web log parameters: " . $clean_request);

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
$status        = $_REQUEST["Status"] ?? "";

//---------------------------------------------------------------
// Language
//---------------------------------------------------------------
set_language($language);

//---------------------------------------------------------------
// SECURITY HASH CHECK
//---------------------------------------------------------------
$expectedKey = sha1($deviceID . $requestDate . $authorization);

debug("HASH CHECK");
debug("DeviceID: $deviceID");
debug("Date: $requestDate");
debug("Authorization: $authorization");
debug("Key Received: $key");
debug("Key Expected: $expectedKey");

if ($expectedKey !== $key) {
    debug("Hash mismatch — returning error 102");

    $output = "<ResultInfo>
        <ErrorNumber>102</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err102a") . "</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Input Validation (LIVE MODE)
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
// LIVE MODE — Insert / Update Task Status (SAFE PREPARED QUERY)
//---------------------------------------------------------------
$update_sql = "
    INSERT INTO user_tasks (
        device_id,
        task_serial,
        status_flag,
        mobile_version,
        updated
    ) VALUES (?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        status_flag = VALUES(status_flag),
        updated = NOW();
";

$stmt = mysqli_prepare($mysqli_link, $update_sql);

if (!$stmt) {
    $err = mysqli_error($mysqli_link);
    debug("SQL Prepare Error: $err");

    $output = "<ResultInfo>
        <ErrorNumber>103</ErrorNumber>
        <Result>Fail</Result>
        <Message>Database prepare error: $err</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

// bind parameters safely
mysqli_stmt_bind_param(
    $stmt,
    "siis",  // string, int, int, string
    $deviceID,
    $taskID,
    $status,
    $mobileVersion
);

// execute
if (!mysqli_stmt_execute($stmt)) {
    $err = mysqli_stmt_error($stmt);
    debug("SQL Exec Error: $err");

    $output = "<ResultInfo>
        <ErrorNumber>103</ErrorNumber>
        <Result>Fail</Result>
        <Message>Database execute error: $err</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

mysqli_stmt_close($stmt);

//---------------------------------------------------------------
// SUCCESS RESPONSE
//---------------------------------------------------------------
$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Task status successfully updated.</Message>
</ResultInfo>";

send_output($output);
exit;

?>
