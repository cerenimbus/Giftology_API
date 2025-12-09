<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED. Proprietary and confidential

 Description:
    UpdateFeedback.php

 Called by:
    Giftology Mobile App / VCService

 Author: Karl Matthew Linao
 Date:   11/15/25
 History:
    10/19/2025   KML - Start
    10/28/2025   KML - Stubs
    11/14/2025   KML - Modified logic to update feedback based on Giftology DD.
    11/15/2025   KML - Converted to Stub version for offline testing per Giftology VCService format.
    11/28/2025   KML - Reviewed and cleaned up comments. change from rrservice to vcservice.
    12/09/2025   KML - Added full security hash validation before stub output.
 ******************************************************************/

//---------------------------------------------------------------
// Initialization & configuration
//---------------------------------------------------------------
require_once('send_output.php');
$debugflag = false;
$suppress_javascript = true;

//---------------------------------------------------------------
// File includes
//---------------------------------------------------------------
if (file_exists('ccu_include/ccu_function.php'))
    require_once('ccu_include/ccu_function.php');
else if (file_exists('../ccu_include/ccu_function.php'))
    require_once('../ccu_include/ccu_function.php');
else {
    echo "Missing ccu_function.php. Contact programmer.";
    exit;
}

if (file_exists('ccu_include/ccu_password_security.php'))
    require_once('ccu_include/ccu_password_security.php');
else if (file_exists('../ccu_include/ccu_password_security.php'))
    require_once('../ccu_include/ccu_password_security.php');
else {
    echo "Missing ccu_password_security.php. Contact programmer.";
    exit;
}

//---------------------------------------------------------------
// Logging
//---------------------------------------------------------------
debug("UpdateFeedback called");

$text = var_export($_REQUEST, true);
$log_text = str_replace('"', "'", $text);
debug("Web log: " . $log_text);

//---------------------------------------------------------------
// Retrieve parameters
//---------------------------------------------------------------
$deviceID = $_REQUEST["DeviceID"] ?? "";
$requestDate = $_REQUEST["Date"] ?? "";
$key = $_REQUEST["Key"] ?? "";
$name = $_REQUEST["Name"] ?? "";
$email = $_REQUEST["Email"] ?? "";
$phone = $_REQUEST["Phone"] ?? "";
$response = $_REQUEST["Response"] ?? "0";
$update = $_REQUEST["Update"] ?? "0";
$comment = $_REQUEST["Comment"] ?? "";
$language = $_REQUEST["Language"] ?? "";
$authorization_code = $_REQUEST["AC"] ?? "";

//---------------------------------------------------------------
// Language Setup
//---------------------------------------------------------------
set_language($language);

//---------------------------------------------------------------
// SECURITY CHECK (ADDED)
// Expected formula: sha1(DeviceID + Date + Authorization Code)
//---------------------------------------------------------------
$expectedKey = sha1($deviceID . $requestDate . $authorization_code);

debug("DeviceID: $deviceID");
debug("Date: $requestDate");
debug("Received Key: $key");
debug("Expected Key: $expectedKey");

// If key mismatched → FAIL immediately
if ($expectedKey !== $key) {
    debug("Security Hash mismatch - Request Rejected");

    $output = "<ResultInfo>
        <ErrorNumber>102</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err102a") . "</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// STUB MODE (Always returns success after security check)
//---------------------------------------------------------------
$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Security code accepted</Message>
    <Auth>this is a test authorization code for testing only</Auth>
</ResultInfo>";

send_output($output);
exit;

//---------------------------------------------------------------
// REAL MODE (Not used in stub but retained for future activation)
//---------------------------------------------------------------
$update_sql = "INSERT INTO feedback SET 
    feedback_device_id='" . mysqli_real_escape_string($mysqli_link, $deviceID) . "',
    feedback_name='" . mysqli_real_escape_string($mysqli_link, $name) . "',
    feedback_email='" . mysqli_real_escape_string($mysqli_link, $email) . "',
    feedback_phone='" . mysqli_real_escape_string($mysqli_link, $phone) . "',
    feedback_source='Mobile',
    reply_requested_flag='" . mysqli_real_escape_string($mysqli_link, $response) . "',
    opt_in_flag='" . mysqli_real_escape_string($mysqli_link, $update) . "',
    comment='" . mysqli_real_escape_string($mysqli_link, $comment) . "',
    created=NOW()";

debug("Update SQL: $update_sql");

$update_result = mysqli_query($mysqli_link, $update_sql);
if (mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
        <ErrorNumber>103</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err103a") . " $error</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Your feedback has been successfully recorded.</Message>
</ResultInfo>";
send_output($output);
exit;

?>
