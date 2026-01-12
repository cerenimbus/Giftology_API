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
//          12/11/25 added stub after validation
//          12/15/25 adding authorize validation
//          12/16/25 testing authorize validation
//          12/19/25 remove stub and enable real DB response for GetTask
//          1/05/25  fix api
//***************************************************************

$debugflag = false;

if( isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}
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

// -------------------------
// Retrieve user info from authorization code
$authorization_sql = 'SELECT * FROM authorization_code 
                      JOIN user ON authorization_code.user_serial = user.user_serial 
                      WHERE user.deleted_flag = 0 
                      AND authorization_code.authorization_code = "' . $authorization_code . '"';
debug($authorization_sql);

// Excute and check for success
$authorization_result=mysqli_query($mysqli_link,$authorization_sql);
if ( mysqlerr( $authorization_sql)) {
    exit;
}
$authorization_row= mysqli_fetch_array( $authorization_result);
$authorization_row_count = mysqli_num_rows($authorization_result);

//-------------------------------------
// If no authorization code is returned, give an error code indicating it was not found
debug( "check for code found");
debug($authorization_row['authorization_code']." = ". $authorization_code  );

 
if ( $authorization_row['authorization_code']!= $authorization_code OR  $authorization_row_count==0 ){
    // RKG 12/8/25 return error "invalid authorization code" if not found
    $output = "<ResultInfo>
<ErrorNumber>202</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err202a")."</Message>
</ResultInfo>";
send_output($output);
    exit;
}

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

$user_serial = $authorization_row["user_serial"];

//-------------------------------------
// FETCH A SINGLE TASK BY SERIAL
$sql = 'SELECT *,
               CONCAT(c.first_name, " ", c.last_name) AS contact_name
        FROM event e
        JOIN workflow_detail wd ON e.workflow_detail_serial = wd.workflow_detail_serial
        JOIN workflow w ON wd.workflow_serial = w.workflow_serial
        LEFT JOIN workflow_detail_type wdt ON wd.workflow_detail_type_serial = wdt.workflow_detail_type_serial
        LEFT JOIN contact c ON e.contact_serial = c.contact_serial
        LEFT JOIN user u ON c.user_serial = u.user_serial
        WHERE u.user_serial = ' . intval($user_serial) . '
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
		<Message>" . get_text("vcservice", "_err103a") . " " . $sql . " " . $error . "</Message>
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
    exit;
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
        <Status>' . $task["status_on_completion"] . '</Status>
    </Task>
</ResultInfo>';

send_output($output);
exit;

?>
