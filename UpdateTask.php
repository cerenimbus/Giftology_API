<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED. Proprietary and confidential

 Description:
    UpdateTask (Giftology RRService)
    Stub version for testing.
    Simulates updating a Task record (DOV/Contact Event Task) without database access.
    Returns fixed XML test data for all expected tags.

 Called by:
    Giftology Mobile App / RRService

 Author: Karl Matthew Linao
 Date:   11/25/25
 History:
    10/19/2025   KML - Start
    10/28/2025   KML - Added Update Task Logic
    11/14/2025   KML - Modified to align with Giftology Data Dictionary
    11/15/2025   KML - Converted to Stub version for offline testing per Giftology RRService format.
 ******************************************************************/

//-----------------------------------------------------
// Initialization
//-----------------------------------------------------
$debugflag = false;
$suppress_javascript = true; // suppress JavaScript output from function includes

// Include function library
if ( file_exists('ccu_include/ccu_function.php') ) {
	require_once('ccu_include/ccu_function.php');
} else if ( file_exists('../ccu_include/ccu_function.php') ) {
	require_once('../ccu_include/ccu_function.php');
} else {
	echo "Cannot find required file ../ccu_include/ccu_function.php. Contact programmer.";
	exit;
}

// Include output handler
require_once('send_output.php');

// KML 10/20/25  The purpose is to always return a successful message, for testing
// REMOVE AFTER DEVELOPMENT
$output = "<ResultInfo>
	<ErrorNumber>0</ErrorNumber>
	<Result>Success</Result>
	<Message>Security code accepted</Message>
	<Auth>this is a test authorization code for testing only</Auth>
</ResultInfo>";
send_output($output);
exit;

debug("UpdateTask called");

//-----------------------------------------------------
// Capture request parameters
//-----------------------------------------------------
$device_ID  		 = urldecode($_REQUEST["DeviceID"]);   // unique device identifier
$requestDate   		 = $_REQUEST["Date"];                  // request timestamp
$authorization_code  = $_REQUEST["AC"];                    // authorization token
$key        		 = $_REQUEST["Key"];                    // SHA-1 hash for verification

// Task details from request
$task_serial         = $_REQUEST["TaskSerial"];            // ID of the task to update
$task_name           = $_REQUEST["TaskName"];
$task_description    = $_REQUEST["TaskDescription"];
$due_date            = $_REQUEST["DueDate"];
$task_status         = $_REQUEST["Status"];
$assigned_to         = $_REQUEST["AssignedEmployeeSerial"];
$priority_level      = $_REQUEST["PriorityLevel"];
$notes               = $_REQUEST["Notes"];
$latitude            = $_REQUEST["Latitude"];
$longitude           = $_REQUEST["Longitude"];

$hash = sha1($device_ID . $requestDate . $authorization_code);

//-----------------------------------------------------
// Log request for audit trail
//-----------------------------------------------------
$text = var_export($_REQUEST, true);
$text = str_replace(chr(34), "'", $text);
$log_sql = 'insert web_log SET method="UpdateTask", text="' . $text . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log: " . $log_sql);

//-----------------------------------------------------
// Security validation - ensure hash matches
//-----------------------------------------------------
if ($hash != $key) {
	debug("Hash validation failed. Key: $key, Expected: $hash");
	$output = "<ResultInfo>
	<ErrorNumber>102</ErrorNumber>
	<Result>Fail</Result>
	<Message>" . get_text("vcservice", "_err102b") . "</Message>
	</ResultInfo>";
	send_output($output);
	exit;
}

//-----------------------------------------------------
// Validate coordinates (if geolocation tracking enabled)
//-----------------------------------------------------
if ($latitude == 0 || $longitude == 0) {
	$output = "<ResultInfo>
	<ErrorNumber>205</ErrorNumber>
	<Result>Fail</Result>
	<Message>" . get_text("vcservice", "_err205") . "</Message>
	</ResultInfo>";
	send_output($output);
	exit;
}

//-----------------------------------------------------
// Validate Authorization Code -> Employee -> Subscriber
//-----------------------------------------------------
$sql = 'SELECT * 
		FROM authorization_code 
		JOIN employee ON authorization_code.employee_serial = employee.employee_serial 
		WHERE employee.deleted_flag = 0 
		AND authorization_code.authorization_code = "' . mysqli_real_escape_string($mysqli_link, $authorization_code) . '"';
debug("Auth check SQL: " . $sql);

$result = mysqli_query($mysqli_link, $sql);
if (mysqli_error($mysqli_link)) {
	debug("SQL error: " . mysqli_error($mysqli_link));
	exit;
}

$authorization_row = mysqli_fetch_assoc($result);
$employee_serial   = $authorization_row["employee_serial"];
$subscriber_serial = $authorization_row["subscriber_serial"];

if (!$employee_serial) {
	$output = "<ResultInfo>
	<ErrorNumber>104</ErrorNumber>
	<Result>Fail</Result>
	<Message>Invalid or expired authorization code.</Message>
	</ResultInfo>";
	send_output($output);
	exit;
}

debug("Authorized employee_serial: $employee_serial, subscriber_serial: $subscriber_serial");

//-----------------------------------------------------
// Update Task Record
//-----------------------------------------------------
$update_sql = "UPDATE task SET 
	task_name = '" . mysqli_real_escape_string($mysqli_link, $task_name) . "',
	task_description = '" . mysqli_real_escape_string($mysqli_link, $task_description) . "',
	due_date = '" . mysqli_real_escape_string($mysqli_link, $due_date) . "',
	status = '" . mysqli_real_escape_string($mysqli_link, $task_status) . "',
	assigned_to = '" . mysqli_real_escape_string($mysqli_link, $assigned_to) . "',
	priority_level = '" . mysqli_real_escape_string($mysqli_link, $priority_level) . "',
	notes = '" . mysqli_real_escape_string($mysqli_link, $notes) . "',
	modified_by = '" . mysqli_real_escape_string($mysqli_link, $employee_serial) . "',
	modified_date = NOW()
	WHERE task_serial = '" . mysqli_real_escape_string($mysqli_link, $task_serial) . "'
	AND subscriber_serial = '" . mysqli_real_escape_string($mysqli_link, $subscriber_serial) . "'";

debug("Update SQL: " . $update_sql);
$result = mysqli_query($mysqli_link, $update_sql);

if (mysqli_error($mysqli_link)) {
	$error = mysqli_error($mysqli_link);
	debug("SQL Error: " . $error);
	$output = "<ResultInfo>
	<ErrorNumber>103</ErrorNumber>
	<Result>Fail</Result>
	<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
	</ResultInfo>";
	send_output($output);
	exit;
}

//-----------------------------------------------------
// Return Success Response
//-----------------------------------------------------
$output = "<ResultInfo>
	<ErrorNumber>0</ErrorNumber>
	<Result>Success</Result>
	<Message>Task updated successfully</Message>
	<TaskSerial>" . $task_serial . "</TaskSerial>
	<UpdatedBy>" . $employee_serial . "</UpdatedBy>
	<UpdatedDate>" . date("Y-m-d H:i:s") . "</UpdatedDate>
</ResultInfo>";
send_output($output);
exit;

?>
