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
// File: GetTaskList.php
// Description: Retrieves a list of tasks for a user based on the provided authorization code.
// Called by: Modules/services requiring retrieval of all tasks for a user based on authorization code
// Author: Alfred louis Carpio
// Created: 10/23/25
// History: 10/23/25 initial version created
//          11/11/25 updated author name, error messages and stub
//          11/14/25 updated error messages and query
//          11/28/25 proper parameters, commented out stub, updated queries
//***************************************************************

$debugflag = false;

// RKG 10/20/25 allow the debugflag to be switched on in the GET method call
if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

// this stops the java script from being written because this is a microservice API
$suppress_javascript = true;

//-------------------------------------
// Include necessary function files with error checking
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else {
    if (!file_exists('../ccu_include/ccu_function.php')) {
        echo "Cannot find required file ../ccu_include/ccu_function.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/ccu_function.php');
}

// Include send_output.php with error checking
if (file_exists('send_output.php')) {
    require_once('send_output.php');
} else {
    if (!file_exists('../ccu_include/send_output.php')) {
        echo "Cannot find required file ../ccu_include/send_output.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/send_output.php');
}

debug("GetTaskList");

//-------------------------------------
// Get the values passed in
$device_ID          = urldecode($_REQUEST["DeviceID"]); //-alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate        = $_REQUEST["Date"]; //- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$key                = $_REQUEST["Key"]; // alphanumeric 40, SHA-1 hash of the device ID + date string (MM/DD/YYYY-HH:mm) + AuthorizationCode
$authorization_code = $_REQUEST["AC"]; // 40 character authorization code 
$language           = $_REQUEST["Language"]; // Standard Language code from mobile [e.g EN for English]
$mobile_version     = $_REQUEST["MobileVersion"]; //## ( hard coded value in software)

//-------------------------------------
// Validate required parameters
if (empty($device_ID) || empty($authorization_code) || empty($key)) {
    $output = "<ResultInfo>
                   <ErrorNumber>101</ErrorNumber>
                   <Result>Fail</Result>
                   <Message>".get_text("vcservice", "_err101")."</Message>
               </ResultInfo>";
    send_output($output);
    exit;
}

// ALC 10/29/25 THIS IS A SAMPLE STUB. The purpose is to always return a successful message, for testing
//  $output = '<ResultInfo>
//        <ErrorNumber>0</ErrorNumber>
//       <Result>Success</Result>
//        <Message>Stub Task list (sample data)</Message>
//        <Selections>
//            <Task>
//                <Name>Inventory Check</Name>
//                <Serial>1001</Serial>
//                <Contact>Warehouse A</Contact>
//                <Date>10/29/2025</Date>
//                <Status>0</Status>
//            </Task>
//            <Task>
//                <Name>Delivery Dispatch</Name>
//                <Serial>1002</Serial>
//                <Contact>Stub Employee Name</Contact>
//                <Date>10/29/2025</Date>
//                <Status>1</Status>
//            </Task>
//            <Task>
//                <Name>System Maintenance</Name>
//                <Serial>1003</Serial>
//                <Contact>IT Department</Contact>
//                <Date>10/30/2025</Date>
//                <Status>0</Status>
//            </Task>
//        </Selections>
//    </ResultInfo>';
//    send_output($output);
//    exit;

//-------------------------------------
// Compute and verify security hash
$hash = sha1($device_ID . $requestDate . $authorization_code);
if ($hash != $key) {
    debug("Hash mismatch: expected $hash but received $key");
    $output = "<ResultInfo>
                   <ErrorNumber>102</ErrorNumber>
                   <Result>Fail</Result>
                   <Message>".get_text("vcservice", "_err102")."</Message>
               </ResultInfo>";
    send_output($output);
    exit;
}

//-------------------------------------
// Log request
$log_text = var_export($_REQUEST, true);
$log_text = str_replace(chr(34), "'", $log_text);
$log_text_safe = mysqli_real_escape_string($mysqli_link, $log_text);
$log_sql = 'INSERT INTO web_log SET method="GetTaskList", text="' . $log_text_safe . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log entry: " . $log_sql);
mysqli_query($mysqli_link, $log_sql);

//-------------------------------------
// Ensure mobile version is current
$current_mobile_version = get_setting("system", "current_giftology_version");
if ($current_mobile_version > $mobile_version) {
    $output = "<ResultInfo>
                   <ErrorNumber>106</ErrorNumber>
                   <Result>Fail</Result>
                   <Message>".get_text("vcservice", "_err106")."</Message>
               </ResultInfo>";
    send_output($output);
    exit;
}

//-------------------------------------
// Retrieve user info from authorization code
$auth_code_safe = mysqli_real_escape_string($mysqli_link, $authorization_code);
$sql = 'SELECT * FROM authorization_code 
        JOIN user ON authorization_code.user_serial = user.user_serial 
        WHERE user.deleted_flag=0 
        AND authorization_code.authorization_code="' . $auth_code_safe . '" 
        LIMIT 1';
debug("Authorization lookup SQL: " . $sql);

$result = mysqli_query($mysqli_link, $sql);
if (!$result || mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
                   <ErrorNumber>103</ErrorNumber>
                   <Result>Fail</Result>
                   <Message>".get_text("vcservice", "_err103")." ". $error ."</Message>
               </ResultInfo>";
    send_output($output);
    exit;
}

$authorization_row = mysqli_fetch_assoc($result);
if (!$authorization_row) {
    $output = "<ResultInfo>
                   <ErrorNumber>105</ErrorNumber>
                   <Result>Fail</Result>
                   <Message>".get_text("vcservice", "_err105")."</Message>
               </ResultInfo>";
    send_output($output);
    exit;
}

$user_serial = $authorization_row["user_serial"];

$sql = 'SELECT *, CONCAT(contact.first_name, " ", contact.last_name) AS contact_name
        FROM event e
        JOIN workflow_detail wd ON e.workflow_detail_serial = wd.workflow_detail_serial
        JOIN workflow w ON wd.workflow_serial = w.workflow_serial
        LEFT JOIN workflow_detail_type wdt ON wd.workflow_detail_type_serial = wdt.workflow_detail_type_serial
        LEFT JOIN contact ON e.contact_serial = contact.contact_serial
        WHERE e.contact_serial IN (
            SELECT contact_serial FROM contact_to_user WHERE user_serial = ' . intval($user_serial) . '
        )
        AND e.deleted_flag = 0
        ORDER BY e.event_target_date ASC';

debug("Task list SQL: " . $sql);

$result = mysqli_query($mysqli_link, $sql);
if (!$result || mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
                   <ErrorNumber>103</ErrorNumber>
                   <Result>Fail</Result>
                   <Message>".get_text("vcservice", "_err103")." ". $error ."</Message>
               </ResultInfo>";
    send_output($output);
    exit;
}

//-------------------------------------
// Build output
if (mysqli_num_rows($result) == 0) {
    $output = "<ResultInfo>
                   <ErrorNumber>0</ErrorNumber>
                   <Result>Success</Result>
                   <Message>No tasks found.</Message>
                   <Selections></Selections>
               </ResultInfo>";
    send_output($output);
    exit;
}

$output = '<ResultInfo>
               <ErrorNumber>0</ErrorNumber>
               <Result>Success</Result>
               <Message>Task list found</Message>
               <Selections>';

while ($task_row = mysqli_fetch_assoc($result)) {

    $output .= '
        <Task>
            <Name>' . htmlspecialchars($task_row["workflow_detail_name"]) . '</Name>
            <Serial>' . $task_row["event_serial"] . '</Serial>
            <Contact>' . htmlspecialchars($task_row["contact_name"]) . '</Contact>
            <Date>' . $task_row["event_target_date"] . '</Date>
            <Status>' . $task_row["status"] . '</Status>
        </Task>';
}

$output .= '</Selections>
           </ResultInfo>';

//-------------------------------------
// Send final XML output
send_output($output);
exit;

?>
