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

if (file_exists('send_output.php')) {
    require_once('send_output.php');
} else {
    if (!file_exists('../ccu_include/send_output.php')) {
        echo "Cannot find required file ../ccu_include/send_output.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/send_output.php');
}
debug("RRService GetUserInfo");

//---------------------------------------------------------------
//  Retrieve and validate input parameters per Giftology specification
//---------------------------------------------------------------
$device_ID          = urldecode($_REQUEST["DeviceID"]);// Alphanumeric ≤60, uniquely identifies mobile device
$requestDate        = $_REQUEST["Date"];                       // Alphanumeric ≤20 (MM/DD/YYYY-HH:mm)
$authorization_code = $_REQUEST["AC"];                         // 40-char authorization code
$key                = $_REQUEST["Key"];                        // 40-char SHA1(DeviceID + Date + AC)

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
$log_sql = 'insert web_log SET method="GetUserInfo", text="' . $request_text . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log: " . $log_sql);


//---------------------------------------------------------------
//  STUB MODE — return static XML test data before hash validation
//---------------------------------------------------------------
// JE 11/12/25 The stub executes prior to real validation to allow mobile testing
// even if backend systems or hash validation are not yet active.
$output = '<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Contact retrieved successfully</Message>
<Conctact>
	<Name>Sara Berra</Name>
    <Email>sara@giftologygroup.com</Email>
    <Company>Giftology</Company>
    <Serial>5</Serial>
    <Subscriber>1</Subscriber >
</Contact>
</ResultInfo>';

// JE 11/12/25 immediately return stub data for testing without validation
send_output($output);
exit;

?>