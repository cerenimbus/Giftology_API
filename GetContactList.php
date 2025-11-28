<?php
/*****************************************************************
 Copyright Cerenimbus Inc
 ALL RIGHTS RESERVED. Proprietary and confidential

 Description:
      GetContactList (Giftology RRService)
      Retrieves a list of contacts for a user based on the authorization code.
      This STUB version returns static test XML data for all required tags.
      The stub block executes before hash validation for testing convenience.

 Called by:
      Giftology Mobile App / RRService

 Author: James Embudo
 Date:   11/28/25
 History:
      11/28/25 JE - Created stub version for offline testing.
      11/28/25 JE - Applied table schema fixes (contact table, mobile_phone) to live logic.
 ******************************************************************/

$debugflag = false;

// Allow debugflag via request
if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

// Suppress JavaScript (Microservice API)
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

// Validate send_output exists
require_once('send_output.php');
if (!function_exists('send_output')) {
    echo "send_output.php is missing or invalid.";
    exit;
}

debug("RRService GetContactList");

//uncomment for local testing
// function send_output($output) {
//     header('Content-Type: application/xml');
//     echo $output;
// }


//---------------------------------------------------------------
//  Retrieve and validate input parameters
//---------------------------------------------------------------
$device_ID          = urldecode($_REQUEST["DeviceID"]); // Alphanumeric up to 60 chars
$requestDate        = $_REQUEST["Date"];                // MM/DD/YYYY-HH:mm
$authorization_code = $_REQUEST["AC"];                  // 40-char authorization code
$key                = $_REQUEST["Key"];                 // SHA1 Hash
$language           = $_REQUEST["Language"];            // Standard language code
$mobile_version     = $_REQUEST["MobileVersion"];       // Numeric version

//---------------------------------------------------------------
//  STUB MODE — Return static XML test data
//---------------------------------------------------------------
// This block allows mobile testing before backend integration is complete.
$output = '<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Contact list retrieved successfully</Message>
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

send_output($output);
exit;

//===============================================================
//  LIVE IMPLEMENTATION LOGIC (Executes if stub above is removed)
//===============================================================

// 1. Calculate and Verify Hash
$hash = sha1($device_ID . $requestDate . $authorization_code);

// Log the request
$request_text = var_export($_REQUEST, true);
$request_text = str_replace(chr(34), "'", $request_text);
$request_text = mysqli_real_escape_string($mysqli_link, $request_text);
$log_sql = 'insert web_log SET method="GetContactList", text="' . $request_text . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log: " . $log_sql);

debug("Calculated Hash: $hash | Received Key: $key");

if ($hash != $key) {
    $output = "<ResultInfo>
        <ErrorNumber>102</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err102b") . "</Message>
    </ResultInfo>";
    $log_comment = "Hash Error: " . $hash . " != " . $key;
    send_output($output);
    exit;
}

// 2. Check Software Version
$current_mobile_version = get_setting("system", "current_giftology_version"); 
if ($current_mobile_version > $mobile_version) {
    $output = "<ResultInfo>
        <ErrorNumber>106</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err106") . "</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

// 3. Validate Authorization Code & Get Subscriber
// Logic: Find the employee associated with the Auth Code to determine the Subscriber (Company) ID
$sql = 'select * from authorization_code 
        join employee on authorization_code.employee_serial = employee.employee_serial 
        where employee.deleted_flag=0 
        and authorization_code.authorization_code="' . $authorization_code . '"';

$result = mysqli_query($mysqli_link, $sql);

if (mysqli_error($mysqli_link)) {
    debug("Auth Code SQL Error: " . mysqli_error($mysqli_link));
    exit;
}

$authorization_row = mysqli_fetch_assoc($result);

if (!$authorization_row) {
    $output = "<ResultInfo>
        <ErrorNumber>103</ErrorNumber>
        <Result>Fail</Result>
        <Message>Invalid Authorization Code</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

$subscriber_serial = $authorization_row["subscriber_serial"];
debug("Subscriber Serial: " . $subscriber_serial);

// 4. Retrieve Contacts
// REVISED: Query the 'contact' table based on schema (mobile_phone, deleted_flag, no preferred_name)
$sql = 'SELECT * FROM contact 
        WHERE subscriber_serial ="' . $subscriber_serial . '" 
        AND deleted_flag = 0 
        ORDER BY first_name';

$result = mysqli_query($mysqli_link, $sql);

if (mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
        <ErrorNumber>103</ErrorNumber>
        <Result>Fail</Result>
        <Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

// 5. Build XML Output
$output = '<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Contact list retrieved successfully</Message>
<Contacts>';

while ($row = mysqli_fetch_assoc($result)) {
    
    // Combine First and Last Name (Schema has no Preferred Name)
    $contact_name = trim($row["first_name"] . " " . $row["last_name"]);

    // Map fields from 'contact' table
    $serial  = $row["contact_serial"];    // Primary Key
    $phone   = $row["mobile_phone"];      // Specific column name from schema
    $email   = $row["email"];             
    $company = $row["company_name"];      

    // Handle nulls
    if(is_null($phone)) { $phone = ""; }
    if(is_null($email)) { $email = ""; }
    if(is_null($company)) { $company = ""; }

    $output .= '
    <Contact>
        <Name>' . $contact_name . '</Name>
        <Serial>' . $serial . '</Serial>
        <Phone>' . $phone . '</Phone>
        <Email>' . $email . '</Email>
        <Company>' . $company . '</Company>
    </Contact>';
}

$output .= '</Contacts>
</ResultInfo>';

send_output($output);
exit;
?>