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
//       12/08/25 JE - Updated API for live server
//       12/09/25 JE - fixed minor issues for testing
//       12/16/25 JE - fixed sql issues for testing
//       12/18/25 JE - Added authorization_sql

//       03/05/26 JLM - Added mysqli_real_escape_string sanitation for all request parameters used in SQL.
//       03/05/26 JLM - Separated RAW request values (used for hashing/validation) from escaped SQL-safe copies.
//       03/05/26 JLM - Escaped web_log insert payload to prevent SQL injection in logging.


$debugflag = false;
if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

// this stops the java scrip from being written because this is a microservice API
$suppress_javascript = true;

// be sure we can find the function file for inclusion

if (file_exists('ccu_include/ccu_function.php')) {
    require_once('ccu_include/ccu_function.php');
} else {
    // if we can't find it, terminate
    if (!file_exists('../ccu_include/ccu_function.php')) {
        echo "Cannot find required file ../ccu_include/ccu_function.php.  Contact programmer.";
        exit;
    }
    require_once('../ccu_include/ccu_function.php');
}

// GENIE 04/22/14 (from DeAuthorizeVoter.php)
// this function is used to output the result and to store the result in the log
debug("get the send output php");
// be sure we can find the function file for inclusion
if (file_exists('send_output.php')) {
    require_once('send_output.php');
} else {
    // if we can't find it, terminate
    if (!file_exists('../ccu_include/send_output.php')) {
        echo "Cannot find required file send_output.php Contact programmer.";
        exit;
    }
    require_once('send_output.php');
}



debug("GetContactList");

//-------------------------------------
// JLM 03-05-26 - Read RAW request params for hashing/logic, then create SQL-safe (escaped) copies for DB usage
$device_ID_raw          = urldecode($_REQUEST["DeviceID"] ?? "");
$requestDate_raw        = (string)($_REQUEST["Date"] ?? "");
$authorization_code_raw = (string)($_REQUEST["AC"] ?? "");
$key_raw                = (string)($_REQUEST["Key"] ?? "");
$language_raw           = (string)($_REQUEST["Language"] ?? "");
$mobile_version_raw     = (string)($_REQUEST["MobileVersion"] ?? "");

// JLM 03-05-26 - Escape request params for safe SQL usage (do NOT use these escaped values for hashing)
$device_ID_sql          = mysqli_real_escape_string($mysqli_link, $device_ID_raw);
$requestDate_sql        = mysqli_real_escape_string($mysqli_link, $requestDate_raw);
$authorization_code_sql = mysqli_real_escape_string($mysqli_link, $authorization_code_raw);
$language_sql           = mysqli_real_escape_string($mysqli_link, $language_raw);
$mobile_version_sql     = mysqli_real_escape_string($mysqli_link, $mobile_version_raw);

$mobile_version = ($mobile_version_raw === "") ? 0 : (int)$mobile_version_raw;

// JLM 03-05-26 - Hash must be computed from RAW values to match the client key generation
$hash = sha1($device_ID_raw . $requestDate_raw . $authorization_code_raw);

// RKG 11/30/2013
// make a log entry for this call to the web service
// compile a string of all of the request values
$text = var_export($_REQUEST, true);

//RKG 3/10/15 clean quote marks
$test = str_replace(chr(34), "'", $text);

// JLM 03-05-26 - Escape the log text before inserting into SQL to prevent log SQL injection/breakage
$log_text_sql = mysqli_real_escape_string($mysqli_link, $text);

$log_sql = 'insert INTO web_log SET method="GetContactList", text="' . $log_text_sql . '", created="' . date("Y-m-d H:i:s") . '"';
debug("Web log:" . $log_sql);

// FOR TESTING ONLY  write the values back out so we can see them
debug(
    "Device ID: " . $device_ID_raw      . "<br>" .
        "Authorization code: " . $authorization_code_raw  . "<br>" .
        $requestDate_raw   . "<br>" .
        'Key: ' . $key_raw               . "<br>" .
        'Hash ' . $hash              . "<br>"
);


// Check the security key
// GENIE 04/22/14 - change: echo xml to call send_output function
if ($hash != $key_raw) {
    debug("hash error " . 'Key / Hash: <br>' . $key_raw . "<br>" .
        $hash . "<br>");

    $output = "<ResultInfo>
	<ErrorNumber>102</ErrorNumber>
	<Result>Fail</Result>
	<Message>" . get_text("vcservice", "_err102b") . "</Message>
	</ResultInfo>";
    //RKG 1/29/2020 New field of $log_comment allows the error message to be written to the web log
    $log_comment = "Hash:" . $hash . "  and Key:" . $key_raw;
    send_output($output);
    exit;
}

// RKG 11/20/2015 make sure they have the currnet software version.
$current_mobile_version = get_setting("system", "current_mobile_version");
debug("current_mobile_version = " . $current_mobile_version);
if ($current_mobile_version > $mobile_version) {
    $output = "<ResultInfo>
    <ErrorNumber>106</ErrorNumber>
    <Result>Fail</Result>
    <Message>" . get_text("vcservice", "_err106") . "</Message>
    </ResultInfo>";
    send_output($output);
    exit;
}

// Retrieve user info from authorization code
// JLM 03-05-26 - Use escaped authorization code in SQL to prevent SQL injection
$authorization_sql = 'select * from authorization_code
                      join user on authorization_code.user_serial = user.user_serial
                      where user.deleted_flag=0
                      and authorization_code.authorization_code="' . $authorization_code_sql . '"';

debug("127 get authorization " . $authorization_sql);

// Excute and check for success
$authorization_result = mysqli_query($mysqli_link, $authorization_sql);

debug("141 got the query");
if (mysqli_error($mysqli_link)) {
    debug("143 error found");
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
<ErrorNumber>104</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("vcservice", "_err104a") . " " . $authorization_sql . " " . $error . "</Message>
</ResultInfo>";
    debug("Mysql error: " . $error . " -- " . $authorization_sql);
    $log_comment = mysqli_error($mysqli_link);
    send_output($output);
    exit;
}


$authorization_row = mysqli_fetch_array($authorization_result);
$authorization_row_count = mysqli_num_rows($authorization_result);
debug("160 got the rows " . $authorization_row_count);

//-------------------------------------

// If no authorization code is returned, give an error code indicating it was not found
debug("165 check for code found");

debug($authorization_row['authorization_code'] . " = " . $authorization_code_raw);

// JLM 03-05-26 - Compare RAW values for correctness (escaped versions may differ due to added slashes)
if ($authorization_row['authorization_code'] != $authorization_code_raw or  $authorization_row_count == 0) {
    debug("170 authorization code not found");
    // RKG 12/8/25 return error "invalid authorization code" if not found
    $output = "<ResultInfo>
<ErrorNumber>202</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("vcservice", "_err202a") . "</Message>
</ResultInfo>";
    send_output($output);
    exit;
}
debug("180 authorization code FOUND");
$user_serial = $authorization_row["user_serial"];
debug("182 got the user_serial " . $user_serial);

// (No request inputs used in this SQL; $user_serial comes from DB)
$sql = 'SELECT *
    FROM contact
    WHERE user_serial ="' . $user_serial . '"
    AND deleted_flag = 0
    ORDER BY first_name';

debug("190Contact list SQL: " . $sql);

$result = mysqli_query($mysqli_link, $sql);

// Rkg if error, write out API response.
//if ( mysqlerr( $update_sql)) {
if (mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);

    // GENIE 04/22/14 - change: echo xml to call send_output function
    $output = "<ResultInfo>
		<ErrorNumber>103</ErrorNumber>
		<Result>Fail</Result>
		<Message>" . get_text("vcservice", "_err103a") . " " . $update_sql . " " . $error . "</Message>
		</ResultInfo>";
    debug("Mysql error: " . $error . " -- " . $sql);
    $log_comment = $error;
    send_output($output);
    exit;
}

//-------------------------------------
// Build output
if (mysqli_num_rows($result) == 0) {
    $output = "<ResultInfo>
                   <ErrorNumber>0</ErrorNumber>
                   <Result>Success</Result>
                   <Message>No Contact found.</Message>
                   <Selections></Selections>
               </ResultInfo>";
    send_output($output);
    exit;
}

$output = '<ResultInfo>
  <ErrorNumber>0</ErrorNumber>
  <Result>Success</Result>
  <Message>Contact retrieved successfully</Message>
  <Contacts>';

// Check if any contacts were found
while ($row = mysqli_fetch_assoc($result)) {
    $contact_name = trim($row["first_name"] . " " . $row["last_name"]);
    $serial = $row["contact_serial"];
    $status = $row["status"];

    $output .= '
    <Contact>
      <Name>' . $contact_name . '</Name>
      <Serial>' . $serial . '</Serial>
      <Status>' . $status . '</Status>
    </Contact>';
}

$output .= '
  </Contacts>
</ResultInfo>';

send_output($output);
exit;
