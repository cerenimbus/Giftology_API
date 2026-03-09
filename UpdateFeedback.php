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
// File: UpdateFeedback.php
// Description: Stores feedback submitted by a user after validating
//              security hash and authorization code.
//
// Called by: Giftology Mobile App / VCService
//
// Author: Karl Matthew Linao
// Created: 11/28/2025
//
// History:
// 11/28/2025  KML - Created UpdateFeedback stub.
// 01/06/2026  KML - Corrected implementation to match specification.
// 01/10/2026  KML - updated api
// 03/09/2026  KEMG - SQL Injection Prevention: added mysqli_real_escape_string to all $_REQUEST inputs
// 03/09/2026  KEMG - Escape the log text to prevent SQL injection in the logging statement
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
// Logging
//---------------------------------------------------------------
debug("UpdateFeedback called");
debug("Incoming request: " . var_export($_REQUEST, true));

// KEMG 03/09/26 - Escape the log text to prevent SQL injection in the logging statement
$text = var_export($_REQUEST, true);
$test = str_replace(chr(34), "'", $text);
$safe_text = mysqli_real_escape_string($mysqli_link, $text);
$log_sql = 'insert web_log SET method="UpdateFeedback", text="' . $safe_text . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log:" . $log_sql);

//---------------------------------------------------------------
// Retrieve parameters (SPEC-ALIGNED)
//---------------------------------------------------------------
$deviceID      = $_REQUEST["DeviceID"] ?? "";
$requestDate   = $_REQUEST["Date"] ?? "";
$key           = $_REQUEST["Key"] ?? "";
$authorization = $_REQUEST["AC"] ?? "";
$language      = $_REQUEST["Language"] ?? "";

$name          = $_REQUEST["Name"] ?? "";
$email         = $_REQUEST["Email"] ?? "";
$phone         = $_REQUEST["Phone"] ?? "";
$responseFlag  = $_REQUEST["Response"] ?? "";
$updateFlag    = $_REQUEST["Update"] ?? "";
$comment       = $_REQUEST["Comment"] ?? "";

//---------------------------------------------------------------
// Setup language
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

//-------------------------------------
// KEMG 03/09/26 - SQL Injection Prevention
// Now that hash verification has passed, escape ALL $_REQUEST input variables
// before they are used in any SQL queries.
//-------------------------------------
$deviceID      = mysqli_real_escape_string($mysqli_link, $deviceID);      // from $_REQUEST["DeviceID"]
$requestDate   = mysqli_real_escape_string($mysqli_link, $requestDate);   // from $_REQUEST["Date"]
$key           = mysqli_real_escape_string($mysqli_link, $key);           // from $_REQUEST["Key"]
$authorization = mysqli_real_escape_string($mysqli_link, $authorization); // from $_REQUEST["AC"]
$language      = mysqli_real_escape_string($mysqli_link, $language);      // from $_REQUEST["Language"]
$name          = mysqli_real_escape_string($mysqli_link, $name);          // from $_REQUEST["Name"]
$email         = mysqli_real_escape_string($mysqli_link, $email);         // from $_REQUEST["Email"]
$phone         = mysqli_real_escape_string($mysqli_link, $phone);         // from $_REQUEST["Phone"]
$responseFlag  = mysqli_real_escape_string($mysqli_link, $responseFlag);  // from $_REQUEST["Response"]
$updateFlag    = mysqli_real_escape_string($mysqli_link, $updateFlag);    // from $_REQUEST["Update"]
$comment       = mysqli_real_escape_string($mysqli_link, $comment);       // from $_REQUEST["Comment"]
//-------------------------------------

//---------------------------------------------------------------
// Validate authorization code
//---------------------------------------------------------------
$auth_sql = "
SELECT user_serial
FROM authorization_code
WHERE deleted_flag = 0
AND authorization_code = '" . $authorization . "'
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
if ($responseFlag !== "0" && $responseFlag !== "1") {

    $output = "<ResultInfo>
        <ErrorNumber>104</ErrorNumber>
        <Result>Fail</Result>
        <Message>Invalid Response flag. Must be 0 or 1.</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

if ($updateFlag !== "0" && $updateFlag !== "1") {

    $output = "<ResultInfo>
        <ErrorNumber>105</ErrorNumber>
        <Result>Fail</Result>
        <Message>Invalid Update flag. Must be 0 or 1.</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Insert feedback (SPEC INTENT)
//---------------------------------------------------------------
$insert_sql = "
INSERT INTO user_feedback
SET
    user_serial = '" . intval($userSerial) . "',
    device_id = '" . $deviceID . "',
    name = '" . $name . "',
    email = '" . $email . "',
    phone = '" . $phone . "',
    response_wanted = '" . $responseFlag . "',
    update_requested = '" . $updateFlag . "',
    comment = '" . $comment . "',
    created = NOW()
";

debug("Insert SQL: $insert_sql");

mysqli_query($mysqli_link, $insert_sql);

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
    <Message>Feedback successfully submitted.</Message>
</ResultInfo>";

send_output($output);
exit;

?>
