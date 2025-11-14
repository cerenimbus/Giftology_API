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
// File: ResetPassword.php
// Description: Resets a user's password based on authorization code and sends a notification email.
// Called by: Modules or services requiring a password reset for a user
// Author: Alfred louis Carpio
// Created: 10/29/25
// History: 10/29/25 initial version created
//          11/11/25 updated author name, error messages and stub
//***************************************************************

$debugflag = false;
// RKG 10/20/25 allow the debugflag to be switched on in the get method call
if( isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

// this stops the java script from being written because this is a microservice API
$suppress_javascript= true;

// be sure we can find the function file for inclusion
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else {
    if (!file_exists('../ccu_include/ccu_function.php')) {
        echo "Cannot find required file ../ccu_include/ccu_function.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/ccu_function.php');
}

// get the email file
if (file_exists("lib/mailer_sendgrid/send_email.php")) {
    require_once("lib/mailer_sendgrid/send_email.php");
} else {
    if (file_exists("../lib/mailer_sendgrid/send_email.php")) {
        require_once("../lib/mailer_sendgrid/send_email.php");
    } else {
        echo "Cannot find required file class.Sendgrid_mail.php. Please copy this message and email to support@cerenimbus.com.";
        exit;
    }
}

// this function is used to output the result and to store the result in the log
debug("get the send output php");
require_once('send_output.php');

debug("ResetPassword"); 

//-------------------------------------
// Get the values passed in
$device_ID = urldecode($_REQUEST["DeviceID"]); // alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate = $_REQUEST["Date"]; // date/time as a string – alphanumeric up to 20 [format: MM/DD/YYYY-HH:mm]
$authorization_code = $_REQUEST["AC"]; // 40 character authorization code
$key = $_REQUEST["Key"]; // alphanumeric 40, SHA-1 hash of the device ID + date string (MM/DD/YYYY-HH:mm) + AuthorizationCode
$password = $_REQUEST["Password"] ?? ''; // new password, minimum 8 characters

// ALC 10/29/25: Longitude and Latitude removed per boss instruction, not used in Giftology app
// $longitude = $_REQUEST["Longitude"];
// $latitude = $_REQUEST["Latitude"];

// ALC 10/29/25 THIS IS A SAMPLE STUB. The purpose is to always return a successful message, for testing
$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>This is a test response from ResetPassword API (Giftology).</Message>
    <Comp>Cerenimbus Inc.</Comp>
    <Name>Cerenimbus Employee</Name>
</ResultInfo>";

send_output($output);
exit;


$hash = sha1($device_ID . $requestDate . $authorization_code);

// make a log entry for this call to the web service
$text = var_export($_REQUEST, true);
$test = str_replace(chr(34), "'", $text);
$log_sql = 'insert web_log SET method="ResetPassword", text="' . $text . '", created="' . date("Y-m-d H:i:s") . '"'; // ALC 10/29/25 updated method name
debug("Web log: " . $log_sql);

//-------------------------------------
// Security check
if ($hash != $key) {
    debug("hash error. Key / Hash mismatch");
    $output = "<ResultInfo>
<ErrorNumber>102</ErrorNumber>
<Result>Fail</Result>
<<Message>".get_text("rrservice", "_err102")."</Message>
</ResultInfo>";
    send_output($output);
    exit;
}

// ALC 10/29/25: Removed latitude/longitude validation - not used in Giftology app
/*
if ($latitude == 0 or $longitude == 0) {
    $output = "<ResultInfo>
<ErrorNumber>205</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("rrservice", "_err205") . "</Message>
</ResultInfo>";
    send_output($output);
    exit;
}
*/

// Lookup user by email
$sql = 'SELECT * FROM employee JOIN subscriber ON subscriber.subscriber_serial = employee.subscriber_serial WHERE employee_email="' . $email_to . '"';
debug("Check the user record: " . $sql);

$result = mysqli_query($mysqli_link, $sql);
if (mysqli_error($mysqli_link)) {
    debug("SQL error: " . mysqli_error($mysqli_link));
    exit;
}
$rows = mysqli_num_rows($result);
$employee_row = mysqli_fetch_assoc($result);

if ($rows == 1) {
    debug("Employee found");
} else {
    debug("No user found with that info");
    $output = "<ResultInfo>
<ErrorNumber>105</ErrorNumber>
<Result>Fail</Result>
<<Message>".get_text("rrservice", "_err105")."</Message>
</ResultInfo>";
    send_output($output);
    exit;
}

//--------------------------------------------------
// Email sending section (commented out for stub)
// ALC 10/29/25: Commented out actual email-sending per checklist (stub version only)
/*
$setting_array = get_setting_list("system");
$from_email = $setting_array["email_sender_from"] . "@" . $setting_array["email_domain"];
$from_name = $setting_array["email_from_name"];
$to_name = $employee_row["first_name"] . " " . $employee_row["last_name"];
$to_email = $employee_row["employee_email"];
$subject = "Reset Password";
$email_body = "Your username is " . $employee_row["employee_username"] . " and your password is " . $employee_row["employee_password"];
$attachment = null;
$message_serial = 0;
$reply_to_email = $setting_array["email_reply_email"];
$api_key = $setting_array["sendgrid_API_key"];
$email_service_name = $setting_array["email_service_name"];

debug("Giftology email service config loaded"); // changed crewzcontrol -> giftology in comment

// $result = send_email($from_email, $to_email, $subject, $email_body, $attachment, null, null, null, $from_name, $to_name, $message_serial, $reply_to_email, $api_key, $email_service_name);
// if ($result->statusCode() == 202) {
//     debug("Email successfully sent to: " . $to_email);
// }
*/

?>

