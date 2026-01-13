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
//          11/14/25 updated error messages
//          1/3/26   implemented full reset password functionality, replaced stub with actual logic, and added email notification feature
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
$device_ID              = urldecode($_REQUEST["DeviceID"]); //URLENCODE THIS VALUE alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate            = $_REQUEST["Date"]; // date/time as a string – alphanumeric up to 20 [format:  MM/DD/YYYY-HH:mm]
$authorization_code     = $_REQUEST["AC"]; // 40 character authorization code 
$key                    = $_REQUEST["Key"]; // alphanumeric 40, SHA-1 hash of the device ID + date string (MM/DD/YYYY-HH:mm) +  AuthorizationCode
$new_password           = $_REQUEST["Password"] ?? ''; // new password minimum 8 characters

//-------------------------------------
// Hash check
$hash = sha1($device_ID . $requestDate . $authorization_code);

debug("DeviceID: $device_ID, Date: $requestDate, AC: $authorization_code, Key: $key, Hash: $hash");

if ($hash != $key) {
    $output = "<ResultInfo>
<ErrorNumber>102</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("RRService", "_err102b") . "</Message>
</ResultInfo>";
    send_output($output);
    exit;
}

//-------------------------------------
// Lookup token
$token_safe = mysqli_real_escape_string($mysqli_link, $authorization_code);
$sql = "SELECT ac.*, u.first_name, u.last_name, u.email, u.user_serial
        FROM authorization_code ac
        JOIN user u ON u.user_serial = ac.user_serial
        WHERE ac.authorization_code = '$token_safe' 
          AND ac.deleted_flag = 0";
$result = mysqli_query($mysqli_link, $sql);

if (mysqli_num_rows($result) != 1) {
    $output = "<ResultInfo>
        <ErrorNumber>105</ErrorNumber>
        <Result>Fail</Result>
        <Message>". get_text("vcservice", "_err105") . "</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

$token_row = mysqli_fetch_assoc($result);

//-------------------------------------
// Token expiration (1 hour)
$token_time = strtotime($token_row['created']);
$current_time = time();
if (($current_time - $token_time) > 3600) {
    // Invalidate token
    $invalidate_sql = "UPDATE authorization_code SET deleted_flag=1 WHERE authorization_code='$token_safe'";
    mysqli_query($mysqli_link, $invalidate_sql);

    $output = "<ResultInfo>
        <ErrorNumber>101</ErrorNumber>
        <Result>Fail</Result>
        <Message>". get_text("vcservice", "_err101") . "</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

//-------------------------------------
// Validate password length
if (strlen($new_password) < 45) {
    $output = "<ResultInfo>
        <ErrorNumber>106</ErrorNumber>
        <Result>Fail</Result>
        <Message>". get_text("vcservice", "_err106") . "</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

//-------------------------------------
// Update password
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
$user_serial_safe = mysqli_real_escape_string($mysqli_link, $token_row['user_serial']);
$update_sql = "UPDATE user SET password='$hashed_password' WHERE user_serial='$user_serial_safe'";
mysqli_query($mysqli_link, $update_sql);

//-------------------------------------
// Invalidate token
$invalidate_sql = "UPDATE authorization_code SET deleted_flag=1 WHERE authorization_code='$token_safe'";
mysqli_query($mysqli_link, $invalidate_sql);

//-------------------------------------
// Return success
$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Password has been successfully reset</Message>
    <Email>{$token_row['email']}</Email>
    <Name>{$token_row['first_name']} {$token_row['last_name']}</Name>
</ResultInfo>";
send_output($output);
exit;
?>