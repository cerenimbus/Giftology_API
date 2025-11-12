<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED.  Proprietary and confidential

 Description:
     GetContact (Giftology RRService)
     Retrieves a list of contacts for a user based on the authorization code.
     This STUB version returns static test XML data for all required tags.
     The stub block executes before hash validation for testing convenience.

 Called by:
     Giftology Mobile App / RRService

 Author: James Embudo
 Date:   11/12/25
 History:
     11/12/25 JE - Created new Giftology GetContact API (converted from CrewzControl)
     11/12/25 JE - Updated argument names and comments per Giftology API spec.
 ******************************************************************/

$debugflag = false;

// RKG 10/20/25 allow the debugflag to be switched on in the GET method call
if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

// This stops JavaScript from being written because this is a microservice API
$suppress_javascript = true;

//---------------------------------------------------------------
//  Include required files with error checking
//---------------------------------------------------------------
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else {
    if (!file_exists('../ccu_include/ccu_function.php')) {
        echo "Cannot find required file ../ccu_include/ccu_function.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/ccu_function.php');
}

// JE 11/12/25 include send_output.php and validate it exists correctly
require_once('send_output.php');
if (!function_exists('send_output')) {
    echo "send_output.php is missing or invalid.";
    exit;
}

debug("RRService GetContact");

//---------------------------------------------------------------
//  Retrieve and validate input parameters per Giftology specification
//---------------------------------------------------------------
$device_ID          = urldecode($_REQUEST["DeviceID"]);// Alphanumeric ≤60, uniquely identifies mobile device
$requestDate        = $_REQUEST["Date"];                       // Alphanumeric ≤20 (MM/DD/YYYY-HH:mm)
$authorization_code = $_REQUEST["AC"];                         // 40-char authorization code
$key                = $_REQUEST["Key"];                        // 40-char SHA1(DeviceID + Date + AC)
$language           = $_REQUEST["Language"];                   // Standard language code (e.g., EN)
$mobile_version     = $_REQUEST["MobileVersion"];               // Hard-coded numeric version in mobile app

// JE 11/12/25 Removed longitude/latitude – Giftology does not use location
// $longitude = $_REQUEST["Longitude"];
// $latitude  = $_REQUEST["Latitude"];

// Optional input validation (commented out for stub testing)
// if (strlen($authorization_code) != 40 or strlen($key) != 40) {
//     debug("Invalid AC or Key length");
// }

//---------------------------------------------------------------
//  STUB MODE — return static XML test data before hash validation
//---------------------------------------------------------------
// JE 11/12/25 The stub executes prior to real validation to allow mobile testing
// even if backend systems or hash validation are not yet active.
$output = '<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Contact retrieved successfully</Message>
<Contacts>
    <Contact>
        <Name>James E</Name>
        <Serial>1001</Serial>
        <Phone>+1 801-555-1001</Phone>
        <Email>james.e@example.com</Email>
        <Company>Acme Corp</Company>
    </Contact>
    <Contact>
        <Name>Alfred C</Name>
        <Serial>1002</Serial>
        <Phone>+1 801-555-1002</Phone>
        <Email>alfred.c@example.com</Email>
        <Company>Bluewave Marketing</Company>
    </Contact>
    <Contact>
        <Name>Janvel A</Name>
        <Serial>1003</Serial>
        <Phone>+1 801-555-1003</Phone>
        <Email>janvel.a@example.com</Email>
        <Company>NextGen Logistics</Company>
    </Contact>
</Contacts>
</ResultInfo>';

// JE 11/12/25 immediately return stub data for testing without validation
send_output($output);
exit;

//---------------------------------------------------------------
//  (Below code remains for live version once stub is removed)
//---------------------------------------------------------------

// Compute hash for security validation
$hash = sha1($device_ID . $requestDate . $authorization_code);

debug("Device ID: $device_ID");
debug("Authorization Code: $authorization_code");
debug("Date: $requestDate");
debug("Key: $key");
debug("Hash: $hash");

//---------------------------------------------------------------
//  Log this API call to the web_log for audit
//---------------------------------------------------------------
$request_text = var_export($_REQUEST, true);
$request_text = str_replace(chr(34), "'", $request_text);
$log_sql = 'insert web_log SET method="GetContact", text="' . $request_text . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log: " . $log_sql);

//---------------------------------------------------------------
//  (Rest of live validation logic will go here after stub phase)
//---------------------------------------------------------------
?>