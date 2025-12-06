<?php
//  Copyright Cerenimbus Inc
//  ALL RIGHTS RESERVED. Proprietary and confidential

//  Description:
//       GetContact (Giftology RRService)
//       Retrieves a list of contacts (formerly employees) for a user based on the authorization code.
//       This STUB version returns static test XML data for all required tags.
//       The stub block executes before hash validation for testing convenience.

//  Called by:
//       Giftology Mobile App / RRService

//  Author: James Embudo
//  Date:   11/28/25
//  History:
//       11/28/25 JE - Revise legacy GetEmployeeList to Giftology GetContact spec.
//       11/28/25 JE - Implemented Stub mode and cleaned up SQL logic for live implementation.
//       12/06/25 JE - Revise sql query to retrieve a single contact of the user.

// ===============================================================
    // CONFIGURATION SWITCH
    // Set to TRUE for live server
    // Set to False for local testing
    // ===============================================================
    $LIVE_MODE = false; 
    // ===============================================================

    $debugflag = isset($_REQUEST["debugflag"]);
    $suppress_javascript = true;

    // -----------------------------------------------------------
    // 1. INPUT HANDLING
    // -----------------------------------------------------------
    $device_ID          = urldecode($_REQUEST["DeviceID"] ?? "");
    $requestDate        = $_REQUEST["Date"] ?? "";
    $authorization_code = $_REQUEST["AC"] ?? "";
    $key                = $_REQUEST["Key"] ?? "";
    $language           = $_REQUEST["Language"] ?? "en";
    $mobile_version     = $_REQUEST["MobileVersion"] ?? "1.0";
    $target_contact_serial = $_REQUEST["ContactSerial"] ?? ""; 

    // -----------------------------------------------------------
    // 2. STUB MODE (Local Testing)
    // -----------------------------------------------------------
    if ($LIVE_MODE === false) {

        if (!function_exists('send_output')) {
            function send_output($output) {
                if (strpos($output, '<?xml') === false) {
                    $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $output;
}
header('Content-Type: application/xml');
echo $output;
exit;
}
}

// STUB: Return only ONE contact to simulate the specific query
$output = '<ResultInfo>
  <ErrorNumber>0</ErrorNumber>
  <Result>Success</Result>
  <Message>Single Contact retrieved (STUB)</Message>
  <Contacts>
    <Contact>
      <Name>James E</Name>
      <Serial>' . ($target_contact_serial ? $target_contact_serial : "1001") . '</Serial>
      <Phone>+1 801-555-1001</Phone>
      <Email>james.e@example.com</Email>
      <Company>Acme Corp</Company>
    </Contact>
  </Contacts>
</ResultInfo>';

send_output($output);
exit;
}

// -----------------------------------------------------------
// 3. LIVE MODE (Server/Database)
// -----------------------------------------------------------

// A. Include Database Files
if (file_exists('ccu_include/ccu_function.php')) {
require_once('ccu_include/ccu_function.php');
} elseif (file_exists('../ccu_include/ccu_function.php')) {
require_once('../ccu_include/ccu_function.php');
} else {
die("Error: ccu_function.php not found.");
}

if (file_exists('send_output.php')) {
require_once('send_output.php');
}

if (!isset($mysqli_link) || !$mysqli_link) {
die("Error: Database connection failed.");
}

// B. Verify Hash
$hash = sha1($device_ID . $requestDate . $authorization_code);

if ($hash != $key) {
$output = "<ResultInfo>
  <ErrorNumber>102</ErrorNumber>
  <Result>Fail</Result>
  <Message>" . (function_exists('get_text') ? get_text("vcservice", "_err102b") : "Hash Error") . "</Message>
</ResultInfo>";
send_output($output);
exit;
}

// C. Get Subscriber ID from Auth Code
$sql_auth = 'SELECT subscriber_serial FROM authorization_code
JOIN employee ON authorization_code.employee_serial = employee.employee_serial
WHERE employee.deleted_flag=0
AND authorization_code.authorization_code="' . mysqli_real_escape_string($mysqli_link, $authorization_code) . '"';

$result_auth = mysqli_query($mysqli_link, $sql_auth);
$auth_row = mysqli_fetch_assoc($result_auth);

if (!$auth_row) {
$output = "<ResultInfo>
  <ErrorNumber>103</ErrorNumber>
  <Result>Fail</Result>
  <Message>Invalid Authorization Code</Message>
</ResultInfo>";
send_output($output);
exit;
}

$subscriber_serial = $auth_row["subscriber_serial"];

// -----------------------------------------------------------
// D. RETRIEVE CONTACT (MODIFIED QUERY)
// -----------------------------------------------------------

// Start the query
$sql = "SELECT * FROM contact
WHERE subscriber_serial = '$subscriber_serial'
AND deleted_flag = 0";

// IF a specific serial was requested, append the filter
if (!empty($target_contact_serial)) {
$safe_serial = mysqli_real_escape_string($mysqli_link, $target_contact_serial);
$sql .= " AND contact_serial = '$safe_serial'";
}

// Order by name
$sql .= " ORDER BY first_name";

$result = mysqli_query($mysqli_link, $sql);

if (!$result) {
$output = "<ResultInfo>
  <ErrorNumber>103</ErrorNumber>
  <Result>Fail</Result>
  <Message>Database Error: " . mysqli_error($mysqli_link) . "</Message>
</ResultInfo>";
send_output($output);
exit;
}

// E. Build Output
$output = '<ResultInfo>
  <ErrorNumber>0</ErrorNumber>
  <Result>Success</Result>
  <Message>Contact retrieved successfully</Message>
  <Contacts>';

    // Check if any contacts were found
    if (mysqli_num_rows($result) == 0) {
    // Optional: You could change the message if no contact found
    // but typically we just return an empty <Contacts> list
      }

      while ($row = mysqli_fetch_assoc($result)) {
      $contact_name = trim($row["first_name"] . " " . $row["last_name"]);

      // Handle Nulls
      $serial = $row["contact_serial"];
      $phone = $row["mobile_phone"] ?? "";
      $email = $row["email"] ?? "";
      $company = $row["company_name"] ?? "";

      $output .= '
      <Contact>
        <Name>' . $contact_name . '</Name>
        <Serial>' . $serial . '</Serial>
        <Phone>' . $phone . '</Phone>
        <Email>' . $email . '</Email>
        <Company>' . $company . '</Company>
      </Contact>';
      }

      $output .= '
    </Contacts>
</ResultInfo>';

send_output($output);
exit;
?>