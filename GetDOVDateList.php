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
    11/14/2025   KML - Modified logic to fetch Event Dates (DOV Dates)
    11/15/2025   KML - Converted to Stub version for offline testing
    11/21/2025   KML - Cleanup and alignment
    12/09/2025   KML - Stub moved AFTER security check
    01/06/2026   KML - Corrected to fully match API specification
    01/10/2026   updated api
******************************************************************/

//---------------------------------------------------------------
// Initialization
//---------------------------------------------------------------
$debugflag = false;
$suppress_javascript = true;

//---------------------------------------------------------------
// Include required files
//---------------------------------------------------------------
if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else if (file_exists('../ccu_include/ccu_function.php')) {
    require_once('../ccu_include/ccu_function.php');
} else {
    echo "Cannot find required file ccu_function.php. Contact programmer.";
    exit;
}

if (file_exists('send_output.php')) {
    require_once('send_output.php');
} else if (file_exists('../ccu_include/send_output.php')) {
    require_once('../ccu_include/send_output.php');
} else {
    echo "Cannot find required file send_output.php. Contact programmer.";
    exit;
}

//---------------------------------------------------------------
// Logging
//---------------------------------------------------------------
debug("GetDOVDateList called");
debug("Incoming request: " . var_export($_REQUEST, true));

//---------------------------------------------------------------
// Retrieve parameters (SPEC EXACT)
//---------------------------------------------------------------
$deviceID      = urldecode($_REQUEST["DeviceID"] ?? "");
$requestDate   = $_REQUEST["Date"] ?? "";
$key           = $_REQUEST["Key"] ?? "";
$authorization = $_REQUEST["AC"] ?? "";
$language      = $_REQUEST["Language"] ?? "EN";
$mobileVersion = $_REQUEST["MobileVersion"] ?? "";

//---------------------------------------------------------------
// Setup language
//---------------------------------------------------------------
set_language($language);

//---------------------------------------------------------------
// Security hash validation
// Formula: sha1(DeviceID + Date + AuthorizationCode)
//---------------------------------------------------------------
$expectedKey = sha1($deviceID . $requestDate . $authorization);

debug("ExpectedKey: $expectedKey");
debug("ReceivedKey: $key");

if ($expectedKey !== $key) {

    $output = "<ResultInfo>
        <ErrorNumber>102</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err102b") . "</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// Validate authorization code
//---------------------------------------------------------------
$auth_sql = "
SELECT authorization_code
FROM authorization_code
WHERE deleted_flag = 0
AND authorization_code = '" . mysqli_real_escape_string($mysqli_link, $authorization) . "'
";

debug("Authorization SQL: $auth_sql");

$auth_result = mysqli_query($mysqli_link, $auth_sql);

if (mysqli_error($mysqli_link)) {

    $err = mysqli_error($mysqli_link);

    $output = "<ResultInfo>
        <ErrorNumber>201</ErrorNumber>
        <Result>Fail</Result>
        <Message>Authorization validation database error: $err</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

if (mysqli_num_rows($auth_result) == 0) {

    $output = "<ResultInfo>
        <ErrorNumber>202</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err202a") . "</Message>
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// STUB MODE (Offline / QA Testing)
// Trigger: AC = TEST-STUB
//---------------------------------------------------------------
if ($authorization === "TEST-STUB") {

    debug("Running GetDOVDateList in STUB MODE");

    $output = "<ResultInfo>
        <ErrorNumber>0</ErrorNumber>
        <Result>Success</Result>
        <Message>Stub DOV Date list</Message>

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
    </ResultInfo>";

    send_output($output);
    exit;
}

//---------------------------------------------------------------
// PRODUCTION LOGIC (NOT IMPLEMENTED YET)
// Must classify results into Harmless / Greenlight / Clarity
//---------------------------------------------------------------
$output = "<ResultInfo>
    <ErrorNumber>0</ErrorNumber>
    <Result>Success</Result>
    <Message>No DOV Date data available.</Message>
    <Selections></Selections>
</ResultInfo>";

send_output($output);
exit;

?>
