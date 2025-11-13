<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED. Proprietary and confidential

 Description:
    UpdateFeedback.php

 Called by:
    Giftology Mobile App / RRService

 Author: Karl Matthew Linao
 Date:   11/15/25
 History:
    10/19/2025   KML - Start
    10/28/2025   KML - Stubs
    11/14/2025   KML - Modified logic to update feedback based on Giftology DD.
    11/15/2025   KML - Converted to Stub version for offline testing per Giftology RRService format.
 ******************************************************************/

//---------------------------------------------------------------
// Initialization and configuration
//---------------------------------------------------------------
require_once('send_output.php');
$debugflag = false;
$suppress_javascript = true; // Suppress JS since this is an API endpoint

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

// Include password security file
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
debug("UpdateFeedback called");

// Make a log entry for this web service call
$text = var_export($_REQUEST, true);
$test = str_replace(chr(34), "'", $text);
$log_sql = 'INSERT web_log SET method="UpdateFeedback", text="' . $test . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log: " . $log_sql);

//---------------------------------------------------------------
// Retrieve parameters
//---------------------------------------------------------------
$deviceID = $_REQUEST["DeviceID"] ?? "";
$requestDate = $_REQUEST["Date"] ?? "";
$key = $_REQUEST["Key"] ?? "";
$name = $_REQUEST["Name"] ?? "";
$email = $_REQUEST["Email"] ?? "";
$phone = $_REQUEST["Phone"] ?? "";
$reply_requested = $_REQUEST["ReplyRequested"] ?? "";
$opt_in = $_REQUEST["OptIn"] ?? "";
$comment = $_REQUEST["Comment"] ?? "";
$language = $_REQUEST["Language"] ?? "";

//---------------------------------------------------------------
// Stub Section (for testing without DB access)
//---------------------------------------------------------------
/*
	RKG 10/20/25 THIS IS A SAMPLE STUB.
	The purpose is to always return a successful message, for testing.
	REMOVE AFTER DEVELOPMENT.
*/
$output = "<ResultInfo>
	<ErrorNumber>0</ErrorNumber>
	<Result>Success</Result>
	<Message>Security code accepted</Message>
	<Auth>this is a test authorization code for testing only</Auth>
</ResultInfo>";
send_output($output);
exit;

//---------------------------------------------------------------
// Language Setup
//---------------------------------------------------------------
set_language($language);

//---------------------------------------------------------------
// Security Hash Calculation
//---------------------------------------------------------------
$secret_string = "V0t3rCL!ck2013";
$hash = sha1($deviceID . $requestDate . $secret_string);

// Debug information
debug("DeviceID: $deviceID");
debug("RequestDate: $requestDate");
debug("Key: $key");
debug("Hash: $hash");

//---------------------------------------------------------------
// Security Key Validation
//---------------------------------------------------------------
if ($hash != $key) {
	debug("Hash key does not match");
	$output = "<ResultInfo>
<ErrorNumber>102</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("vcservice", "_err102a") . "</Message>
</ResultInfo>";
	send_output($output);
	exit;
}

//---------------------------------------------------------------
// Update feedback record (based on Giftology data dictionary)
//---------------------------------------------------------------
$update_sql = "INSERT INTO feedback SET " .
	"feedback_device_id='" . mysqli_real_escape_string($mysqli_link, $deviceID) . "', " .
	"feedback_name='" . mysqli_real_escape_string($mysqli_link, $name) . "', " .
	"feedback_email='" . mysqli_real_escape_string($mysqli_link, $email) . "', " .
	"feedback_phone='" . mysqli_real_escape_string($mysqli_link, $phone) . "', " .
	"feedback_source='Mobile', " .
	"reply_requested_flag='" . mysqli_real_escape_string($mysqli_link, $reply_requested) . "', " .
	"opt_in_flag='" . mysqli_real_escape_string($mysqli_link, $opt_in) . "', " .
	"comment='" . mysqli_real_escape_string($mysqli_link, $comment) . "'";

debug("Update SQL: " . $update_sql);

// Execute the query
$update_result = mysqli_query($mysqli_link, $update_sql);
if (mysqli_error($mysqli_link)) {
	$error = mysqli_error($mysqli_link);
	$output = "<ResultInfo>
<ErrorNumber>103</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
</ResultInfo>";
	send_output($output);
	exit;
}

//---------------------------------------------------------------
// Output success result
//---------------------------------------------------------------
$output = "<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Your feedback has been successfully recorded.</Message>
</ResultInfo>";
send_output($output);
exit;
?>
