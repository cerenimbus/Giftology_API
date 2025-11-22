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
    11/21/2025   KML - Added dovChartData & revenueChartData fields for dashboard graphs
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
$device_ID          = urldecode($_REQUEST["DeviceID"] ?? '');
$requestDate        = $_REQUEST["Date"] ?? '';
$authorization_code = $_REQUEST["AC"] ?? '';
$key                = $_REQUEST["Key"] ?? '';
$language           = $_REQUEST["Language"] ?? 'EN';
$mobile_version     = $_REQUEST["MobileVersion"] ?? '1';

//---------------------------------------------------------------
//  STUB SECTION (Return static XML before any validation)
//---------------------------------------------------------------
// 11/21/2025 — Added chart data fields needed by the dashboard API

$output = '<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>Stub DOV Date list (sample data)</Message>

    <dovChartData>
        <Point>40</Point>
        <Point>80</Point>
        <Point>160</Point>
        <Point>120</Point>
        <Point>200</Point>
    </dovChartData>

    <revenueChartData>
        <Point>40</Point>
        <Point>80</Point>
        <Point>120</Point>
        <Point>60</Point>
        <Point>160</Point>
        <Point>100</Point>
    </revenueChartData>

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
//  NORMAL EXECUTION BELOW (only runs after removing stub)
//---------------------------------------------------------------

// Compute SHA1 hash
$hash = sha1($device_ID . $requestDate . $authorization_code);

// Log request
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
    send_output($output);
    exit;
}

//---------------------------------------------------------------
//  AUTHORIZATION CODE LOOKUP
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
$employee_serial   = $authorization_row["employee_serial"];
$subscriber_serial = $authorization_row["subscriber_serial"];

//---------------------------------------------------------------
//  MAIN QUERY SECTION
//---------------------------------------------------------------
$sql = 'SELECT * FROM contact_event 
        WHERE subscriber_serial ="' . $subscriber_serial . '" 
        ORDER BY event_date DESC';

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

send_output($output);
exit;

?>
