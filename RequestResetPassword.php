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
// Description: Allows the user to request a password reset. Generates an authorization code and sends a reset-password link to the user’s registered email address.
// Called by: Modules or services requiring a password reset for a user
// Author: Alfred louis Carpio
// Created: 1/13/26
// History: 1/13/26 initial version created
//***************************************************************

$debugflag = false;
if (isset($_REQUEST["debugflag"])) $debugflag = true;

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


debug("RequestResetPassword");

//-------------------------------------
// Get the values passed in
$device_ID              = urldecode($_REQUEST["DeviceID"]); //URLENCODE THIS VALUE; alphanumeric up to 60 characters, uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate            = $_REQUEST["Date"]; //alphanumeric up to 20 [format:  MM/DD/YYYY-HH:mm]
$key                    = $_REQUEST["Key"]; //alphanumeric 40; SHA-1 hash of DeviceID + Date + Email + AuthorizationCode
$email                  = $_REQUEST["Email"]; // user’s registered email address

$email_safe = mysqli_real_escape_string($mysqli_link, $email);

//-------------------------------------
// Lookup user by email
$sql = "SELECT * FROM user WHERE email = '$email_safe' AND deleted_flag=0";
$result = mysqli_query($mysqli_link, $sql);
if (mysqli_num_rows($result) != 1) {
    $output = "<ResultInfo>
        <ErrorNumber>105</ErrorNumber>
        <Result>Fail</Result>
        <Message>". get_text("vcservice", "_err105") ."</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

$user_row = mysqli_fetch_assoc($result);

//-------------------------------------
// Generate secure authorization token
$token = bin2hex(random_bytes(20)); // 40 chars

// Hash check includes token
$hash = sha1($device_ID . $requestDate . $token);
debug("DeviceID: $device_ID, Date: $requestDate, Token: $token, Key: $key, Hash: $hash");

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
// Token expiration check
// Invalidate any old tokens for this user
$expire_sql = "UPDATE authorization_code 
               SET deleted_flag=1 
               WHERE user_serial='{$user_row['user_serial']}' 
                 AND created < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
mysqli_query($mysqli_link, $expire_sql);

//-------------------------------------
// Store new token in authorization_code table
$insert_sql = "INSERT INTO authorization_code (user_serial, authorization_code, created, deleted_flag) 
               VALUES ('{$user_row['user_serial']}', '$token', NOW(), 0)";
mysqli_query($mysqli_link, $insert_sql);

//-------------------------------------
// Generate the reset link
// This is just an example link for now, as there is no screen for the reset password.
$reset_link = "https://Radar.Giftologygroup.com/reset-password.html?AC=$token&DeviceID=$device_ID&Date=$requestDate";

//-------------------------------------
// Send email with expiration warning
$from_email = "noreply@example.com";
$from_name  = "Cerenimbus Inc.";
$to_email   = $user_row['email'];
$to_name    = $user_row['first_name'] . " " . $user_row['last_name'];
$subject    = "Password Reset Request";
$email_body = "Hello $to_name,\n\n"
            . "We received a request to reset your password. "
            . "Click the link below to reset your password. This link will expire in 1 hour.\n\n"
            . "$reset_link\n\n"
            . "If you did not request a password reset, please ignore this email.";

send_email($from_email, $to_email, $subject, $email_body, null, null, null, null, $from_name, $to_name);

//-------------------------------------
// Return API response
$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Password reset link sent to your email. This link will expire in 1 hour.</Message>
    <Email>{$user_row['email']}</Email>
    <Name>{$user_row['first_name']} {$user_row['last_name']}</Name>
</ResultInfo>";
send_output($output);
exit;
?>