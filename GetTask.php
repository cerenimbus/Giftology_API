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
//          11/14/25 updated error messages and query
//          11/28/25 proper parameters, commented out stub, updated queries
//          12/08/25 fixed api
//          12/09/25 fixing sql statement
//***************************************************************

$debugflag = false;
// this stops the java scrip from being written because this is a microservice API
$suppress_javascript = true;

// be sure we can find the function file for inclusion
if (file_exists('ccu_include/ccu_function.php')) {
	require_once('ccu_include/ccu_function.php');
} else {
	// if we can't find it, terminate
	if (!file_exists('../ccu_include/ccu_function.php')) {
		echo "Cannot find required file ../ccu_include/ccu_function.php.  Contact programmer.";
		exit;
	}
	require_once('../ccu_include/ccu_function.php');
}


// GENIE 04/22/14 (from DeAuthorizeVoter.php)
// this function is used to output the result and to store the result in the log
debug( "get the send output php");
// be sure we can find the function file for inclusion
if ( file_exists( 'send_output.php')) {
    require_once( 'send_output.php');
} else {
    // if we can't find it, terminate
    if ( !file_exists('../ccu_include/send_output.php')){
        echo "Cannot find required file send_output.php Contact programmer.";
        exit;
    }
    require_once('send_output.php');
}


debug("GetTask");

//-------------------------------------
// Get the values passed in
$device_ID          = urldecode($_REQUEST["DeviceID"]); //-alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate        = $_REQUEST["Date"]; //- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$key                = $_REQUEST["Key"]; // alphanumeric 40, SHA-1 hash of the device ID + date string (MM/DD/YYYY-HH:mm) + AuthorizationCode
$authorization_code = $_REQUEST["AC"];// 40 character authorization code 
$language           = $_REQUEST["Language"]; // Standard Language code from mobile [e.g EN for English]
$mobile_version     = $_REQUEST["MobileVersion"]; //hardcoded value in software

$hash = sha1($device_ID . $requestDate.$authorization_code  );

$task_serial        = $_REQUEST["Task"]; //added task

// RKG 11/30/2013
// make a log entry for this call to the web service
// compile a string of all of the request values
$text= var_export($_REQUEST, true);
//RKG 3/10/15 clean quote marks
$test = str_replace(chr(34), "'", $text);
$log_sql= 'insert web_log SET method="GetTask", text="'. $text. '", created="' . date("Y-m-d H:i:s") .'"';
debug("Web log:" .$log_sql);

// FOR TESTING ONLY  write the values back out so we can see them
debug(
"Device ID: ".$device_ID  	."<br>".
"Authorization code: ". $authorization_code  ."<br>". 
$requestDate   ."<br>".
'Key: '. $key   			."<br>".
'Hash '. $hash  			."<br>"
);

// // ALC 10/29/25 THIS IS A SAMPLE STUB. The purpose is to always return a successful message, for testing
// $output = '<ResultInfo>
//     <ErrorNumber>0</ErrorNumber>
//     <Result>Success</Result>
//     <Message>Stub single task (sample data)</Message>
//     <Task>
//         <Name>System Maintenance</Name>
//         <Serial>1003</Serial>
//         <Contact>IT Department</Contact>
//         <Date>10/30/2025</Date>
//         <Status>0</Status>
//     </Task>
// </ResultInfo>';
// send_output($output);
// exit;

// Check the security key
// GENIE 04/22/14 - change: echo xml to call send_output function
if( $hash != $key){
	debug( "hash error ". 'Key / Hash: <br>'. $key ."<br>".
	$hash."<br>");

	$output = "<ResultInfo>
	<ErrorNumber>102</ErrorNumber>
	<Result>Fail</Result>
	<Message>". get_text("vcservice", "_err102b")."</Message>
	</ResultInfo>";
	//RKG 1/29/2020 New field of $log_comment allows the error message to be written to the web log
	$log_comment= "Hash:".$hash."  and Key:". $key;
	send_output($output);
	exit;
}

// RKG 11/20/2015 make sure they have the currnet software version. 
$current_mobile_version = get_setting("system","current_mobile_version");
    debug("current_mobile_version = " . $current_mobile_version );
    if ( $current_mobile_version > $mobile_version){
        $output = "<ResultInfo>
    <ErrorNumber>106</ErrorNumber>
    <Result>Fail</Result>
    <Message>".get_text("vcservice", "_err106")."</Message>
    </ResultInfo>";
	send_output($output);
	exit;
}

// Retrieve user info from authorization code
$sql = 'select * from authorization_code join user on authorization_code.user_serial = user.user_serial where user.deleted_flag=0 and authorization_code.authorization_code="' . $authorization_code . '"';
debug("get the code: " . $sql);

// Execute the insert and check for success
$result = mysqli_query($mysqli_link, $sql);
if (mysqli_error($mysqli_link)) {
    $error =  mysqli_error($mysqli_link);
    // GENIE 04/22/14 - change: echo xml to call send_output function
    $output = "<ResultInfo>
		<ErrorNumber>103</ErrorNumber>
		<Result>Fail</Result>
		<Message>" . get_text("vcservice", "_err103a") . " " . $update_sql . " " .  $error . "</Message>
		</ResultInfo>";
    debug("Mysql error: " . $error . "  ", $sql);
    $log_comment =  $error;
    send_output($output);
    exit;
}

$authorization_row = mysqli_fetch_assoc($result);

$user_serial = $authorization_row["user_serial"];

//-------------------------------------
// FETCH A SINGLE TASK BY SERIAL
$sql = 'SELECT e.*, CONCAT(contact.first_name, " ", contact.last_name) AS contact_name
        FROM event e
        JOIN workflow_detail wd ON e.workflow_detail_serial = wd.workflow_detail_serial
        JOIN workflow w ON wd.workflow_serial = w.workflow_serial
        LEFT JOIN contact ON e.contact_serial = contact.contact_serial
        WHERE e.event_serial = ' . intval($task_serial) . '
        AND e.contact_serial IN (
            SELECT contact_serial 
            FROM contact_to_user 
            WHERE user_serial = ' . intval($user_serial) . '
        )
        AND e.deleted_flag = 0
        LIMIT 1';
debug("Task SQL: $sql");

$result = mysqli_query($mysqli_link, $sql);
// Rkg if error, write out API response.
//if ( mysqlerr( $update_sql)) {
if (mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
	// GENIE 04/22/14 - change: echo xml to call send_output function
	$output = "<ResultInfo>
		<ErrorNumber>103</ErrorNumber>
		<Result>Fail</Result>
		<Message>" . get_text("vcservice", "_err103a") . " " . $update_sql . " " . $error . "</Message>
		</ResultInfo>";
	debug("Mysql error: " . $error . " -- " . $sql);
	$log_comment = $error;
	send_output($output);
	exit;
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
        <Serial>' . $task["event_serial"] . '</Serial>
        <Contact>' . htmlspecialchars($task["contact_name"]) . '</Contact>
        <Date>' . $task["event_target_date"]. '</Date>
        <Status>' . $task["status"] . '</Status>
    </Task>
</ResultInfo>';

send_output($output);
exit;

?>
