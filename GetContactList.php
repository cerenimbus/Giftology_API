<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED. Proprietary and confidential

 Description:
     GetContactList (Giftology RRService)
     Stub version for testing.
     Simulates retrieval of a user’s contact list without database access.
     Returns fixed XML test data for all expected tags.

 Called by:
     Giftology Mobile App / RRService

 Author: James Embudo
 Date:   11/12/25
 History:
     11/12/25 JE - Created stub version for offline testing.
     11/12/25 JE - Applied Cerenimbus checklist updates per Giftology API migration.
     11/12/25 JE - Updated parameter set and validation per Giftology spec.
 ******************************************************************/

$debugflag = false;

// RKG 10/20/25 allow the debugflag to be switched on in the GET method call
if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

// This stops JavaScript output since this is an API microservice
$suppress_javascript = true;

//---------------------------------------------------------------
//  Include required support files
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

//---------------------------------------------------------------
//  Include send_output.php with validation (per AuthorizeEmployee.php)
//---------------------------------------------------------------
if (file_exists('send_output.php')) {
    require_once('send_output.php');
    if (!function_exists('send_output')) {
        echo "send_output.php is missing or invalid.";
        exit;
    }
} else {
    echo "Required file send_output.php not found. Contact programmer.";
    exit;
}

// JE 11/12/25 Log API start
debug("RRService GetContactList (Stub) initialized");

//---------------------------------------------------------------
//  Retrieve and validate input parameters per Giftology spec
//---------------------------------------------------------------
// JE 11/12/25 Each parameter validated per API documentation
$device_ID          = urldecode($_REQUEST["DeviceID"]);// Alphanumeric up to 60 chars, uniquely identifies the mobile device
$requestDate        = $_REQUEST["Date"];                      // Alphanumeric up to 20 chars [MM/DD/YYYY-HH:mm]
$authorization_code = $_REQUEST["AC"];                        // 40-char authorization code
$key                = $_REQUEST["Key"];                       // 40-char SHA1(device_ID + date + AC)
$language           = $_REQUEST["Language"];                  // Standard language code (e.g., EN)
$mobile_version     = $_REQUEST["MobileVersion"];             // Hardcoded numeric version from mobile app

// JE 11/12/25 Removed longitude/latitude — not used in Giftology RRService
// $longitude = $_REQUEST["Longitude"];
// $latitude  = $_REQUEST["Latitude"];

//---------------------------------------------------------------
//  STUB BLOCK – Executes before hash validation
//---------------------------------------------------------------
// JE 11/12/25 Stub Mode: Always returns static XML test data for integration testing.
// This block executes BEFORE hash validation to simplify client testing.
if (true) { // Always active in stub mode
    debug("RRService GetContactList (Stub) executing before hash validation.");

    $output = '<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Contact list found</Message>
    <Contacts>
        <Contact>
            <Name>James E</Name>
            <Serial>1001</Serial>
        </Contact>
        <Contact>
            <Name>Alfred C</Name>
            <Serial>1002</Serial>
        </Contact>
        <Contact>
            <Name>Janvel A</Name>
            <Serial>1003</Serial>
        </Contact>
    </Contacts>
    </ResultInfo>';

    send_output($output);
    exit;
}

//---------------------------------------------------------------
//  Security Hash Validation (not executed in stub mode)
//---------------------------------------------------------------
$hash = sha1($device_ID . $requestDate . $authorization_code);
debug("Hash validation placeholder (not executed in stub mode). Computed Hash: " . $hash);

//---------------------------------------------------------------
//  Version validation (stubbed success)
//---------------------------------------------------------------
debug("Stub Mode - Skipping version validation.");

//---------------------------------------------------------------
//  Log Request for Testing
//---------------------------------------------------------------
$request_text = var_export($_REQUEST, true);
$request_text = str_replace(chr(34), "'", $request_text);
$log_sql = 'insert web_log SET method="GetContactListStub", text="' . $request_text . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log (stub): " . $log_sql);
?>