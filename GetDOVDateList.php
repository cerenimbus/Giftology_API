<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED. Proprietary and confidential

 Description:
    GetDOVDateList.php for Giftology API 

 Called by:
    Giftology Mobile App 

 Author: Karl Matthew Linao
 Date:   11/25/25
 History:
    10/19/2025   KML - Start
    10/28/2025   KML - Stubs
    11/14/2025   KML - Modified logic to fetch Event Dates (DOV Dates) based on Giftology DD.
    11/15/2025   KML - Converted to Stub version for offline testing per Giftology 
    11/18/2025   ALC - Updated arguments and stub
 ******************************************************************/

//---------------------------------------------------------------
//  Initialization and configuration
//---------------------------------------------------------------
$debugflag = false;
$suppress_javascript = true; // Suppress JS since this is an API endpoint

// Attempt to include the main function file (required for logging, DB connection, etc.)
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else {
    if (!file_exists('../ccu_include/ccu_function.php')) {
        echo "Cannot find required file ../ccu_include/ccu_function.php. Contact programmer.";
        exit;
    }
    require_once('../ccu_include/ccu_function.php');
}

// Include output handler
require_once('send_output.php');

debug("GetDOVDateList called");

//---------------------------------------------------------------
//  GET PARAMETERS
//---------------------------------------------------------------
// Device information and request metadata
$device_ID          = urldecode($_REQUEST["DeviceID"] ?? ''); //-alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate        = $_REQUEST["Date"] ?? ''; //- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$authorization_code = $_REQUEST["AC"] ?? '';// 40 character authorization code 
$key                = $_REQUEST["Key"] ?? ''; // alphanumeric 40, SHA-1 hash of the device ID + date string (MM/DD/YYYY-HH:mm) + AuthorizationCode
$language           = $_REQUEST["Language"] ?? 'EN'; // Standard Language code from mobile [e.g EN for English]
$mobile_version     = $_REQUEST["MobileVersion"] ?? '1'; //hardcoded value in software

//---------------------------------------------------------------
//  STUB SECTION (Return static XML before any validation)
//---------------------------------------------------------------

// ALC 11/18/25 THIS IS A SAMPLE STUB. The purpose is to always return a successful message, for testing
$output = '<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Stub DOV Date list (sample data)</Message>
    <Selections>
        <Harmless>
            <Name>John Doe</Name>
            <ContactSerial>101</ContactSerial>
            <Date>11/18/2025</Date>
        </Harmless>
        <Harmless>
            <Name>Emily Stone</Name>
            <ContactSerial>102</ContactSerial>
            <Date>11/18/2025</Date>
        </Harmless>
        <Greenlight>
            <Name>Jane Smith</Name>
            <ContactSerial>201</ContactSerial>
            <Date>11/19/2025</Date>
        </Greenlight>
        <Clarity>
            <Name>Mark Johnson</Name>
            <ContactSerial>301</ContactSerial>
            <Date>11/20/2025</Date>
        </Clarity>
    </Selections>
</ResultInfo>';

send_output($output);
exit;

//---------------------------------------------------------------
//  NORMAL EXECUTION BELOW (This section will only run after stub removal)
//---------------------------------------------------------------

// Compute SHA1 hash for security validation
$hash = sha1($device_ID . $requestDate . $authorization_code);

// Log incoming request (for diagnostics and traceability)
$text = var_export($_REQUEST, true);
$test = str_replace(chr(34), "'", $text);
$log_sql = 'INSERT web_log SET method="GetDOVDateList", text="' . $test . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log: " . $log_sql);

//---------------------------------------------------------------
//  SECURITY VALIDATION
//---------------------------------------------------------------
if ($hash != $key) {
    $output = "<ResultInfo>
<ErrorNumber>102</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("rrservice", "_err102b") . "</Message>
</ResultInfo>";
    $log_comment = "Hash:" . $hash . "  and Key:" . $key;
    send_output($output);
    exit;
}

//---------------------------------------------------------------
//  AUTHORIZATION CODE VALIDATION (retrieve employee info)
//---------------------------------------------------------------
$sql = 'SELECT * FROM authorization_code 
        JOIN employee ON authorization_code.employee_serial = employee.employee_serial 
        WHERE employee.deleted_flag=0 
        AND authorization_code.authorization_code="' . $authorization_code . '"';
$result = mysqli_query($mysqli_link, $sql);
if (mysqli_error($mysqli_link)) {
    debug("SQL Error: " . mysqli_error($mysqli_link));
    exit;
}
$authorization_row = mysqli_fetch_assoc($result);
$employee_serial = $authorization_row["employee_serial"];
$subscriber_serial = $authorization_row["subscriber_serial"];

//---------------------------------------------------------------
//  MAIN QUERY SECTION
//---------------------------------------------------------------
/*
    Retrieves DOV (Date of Visit) or Contact Events list
    for the subscriber based on the Giftology Data Dictionary.
*/
$sql = 'SELECT * FROM contact_event WHERE subscriber_serial ="' . $subscriber_serial . '" ORDER BY event_date DESC';
debug("Get DOV Date list: " . $sql);

$result = mysqli_query($mysqli_link, $sql);
if (mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
<ErrorNumber>103</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("rrservice", "_err103a") . " " . $error . "</Message>
</ResultInfo>";
    send_output($output);
    exit;
}

//---------------------------------------------------------------
//  XML OUTPUT CONSTRUCTION
//---------------------------------------------------------------
$output = '<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>DOV Date list found</Message>
<Selections>';

while ($event_row = mysqli_fetch_assoc($result)) {
    $output .= '
    <DOVDate>
        <EventID>' . $event_row["contact_event_serial"] . '</EventID>
        <EventName>' . htmlspecialchars($event_row["event_name"]) . '</EventName>
        <EventDate>' . htmlspecialchars($event_row["event_date"]) . '</EventDate>
        <Location>' . htmlspecialchars($event_row["event_location"]) . '</Location>
    </DOVDate>';
}

$output .= '</Selections>
</ResultInfo>';

//---------------------------------------------------------------
//  SEND FINAL OUTPUT
//---------------------------------------------------------------
send_output($output);
exit;

?>
