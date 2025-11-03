<?php
/*****************************************************************
Copyright  Cerenimbus
ALL RIGHTS RESERVED.  Proprietary and confidential

description:
  RRService – GetEmployeeList
  Get a list of open quotes (not converted into a job). If a search term
  is included, it will be used to filter results. If there is no search
  term, a list will be returned with the first one being the quote closest
  to the mobile user, followed by the 5 most recently viewed quotes.

called by:
  Mobile API client (Giftology mobile, per comments)

author: JE
date:   10/27/25
history:
  10/27/25 JE – CCService->RRService, comments, logs, debugflag, remove lat/long, stub
******************************************************************/

//=============================
//  INITIALIZATION
//=============================
$script_name = basename(__FILE__);          // JE 10/27/25 for accurate logging
$service_name = 'RRService.GetEmployeeList';// JE 10/27/25 service label for logs

$debugflag = false;
$suppress_javascript = true; // this stops the java script from being written because this is a microservice API

// JE 10/27/25 allow the debugflag to be switched on in the get method call
if (isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}
// Fallback for debug if not defined
if (!function_exists('debug')) {
    function debug($message) {
        global $debugflag;
        if ($debugflag) {
            echo "<pre>DEBUG: " . htmlspecialchars($message) . "</pre>";
        }
    }
}

//-----------------------------
// Include core function file
//-----------------------------
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

//-----------------------------
// Include send_output + guard
//-----------------------------
// GENIE 04/22/14 (from DeAuthorizeVoter.php)
// this function is used to output the result and to store the result in the log
debug("{$service_name}: include send_output.php");
require_once('send_output.php');

// JE 10/27/25 – Defensive checks similar to AuthorizeEmployee.php pattern.
// Ensure send_output exists; if not, provide a safe fallback with error info.
if (!function_exists('send_output')) {
    // JE 10/27/25 fallback to prevent fatal error if include fails silently.
    function send_output($xml_payload) {
        header('Content-Type: text/xml; charset=UTF-8');
        echo "<ResultInfo>
  <ErrorNumber>999</ErrorNumber>
  <Result>Fail</Result>
  <Message>send_output function missing. Check send_output.php include.</Message>
</ResultInfo>";
    }
    debug("{$service_name}: WARNING send_output() missing; fallback engaged");
}

// Fallback for get_setting if not defined
if (!function_exists('get_setting')) {
    function get_setting($category, $name, $default = '') {
        global $mysqli_link;
        if (isset($mysqli_link)) {
            $sql = "SELECT value FROM setting WHERE category='" . addslashes($category) . "' AND name='" . addslashes($name) . "'";
            $result = mysqli_query($mysqli_link, $sql);
            if ($row = mysqli_fetch_assoc($result)) {
                return $row['value'];
            }
        }
        return $default;
    }
}

// Fallback for get_text if not defined
if (!function_exists('get_text')) {
    function get_text($category, $name, $default = 'Error') {
        global $mysqli_link;
        if (isset($mysqli_link)) {
            $sql = "SELECT text FROM text_table WHERE category='" . addslashes($category) . "' AND name='" . addslashes($name) . "'";
            $result = mysqli_query($mysqli_link, $sql);
            if ($row = mysqli_fetch_assoc($result)) {
                return $row['text'];
            }
        }
        return $default;
    }
}

debug("{$service_name}: start");

//=============================
//  INPUTS
//=============================
$device_ID              = isset($_REQUEST["DeviceID"]) ? urldecode($_REQUEST["DeviceID"]) : ''; // alphanumeric up to 60
$requestDate            = isset($_REQUEST["Date"]) ? $_REQUEST["Date"] : '';                     // string up to 20 [MM/DD/YYYY HH:mm]
$crewzcontrol_version   = isset($_REQUEST["CrewzControlVersion"]) ? $_REQUEST["CrewzControlVersion"] : ''; // legacy field name
$authorization_code     = isset($_REQUEST["AC"]) ? $_REQUEST["AC"] : '';                         // auth code
$key                    = isset($_REQUEST["Key"]) ? $_REQUEST["Key"] : '';                       // SHA-1(DeviceID+Date+AC)

// JE 10/27/25 – Location not used: comment out and keep for history per standards
// $longitude = $_REQUEST["Longitude"];   // JE 10/27/25 removed – location not used in this app
// $latitude  = $_REQUEST["Latitude"];    // JE 10/27/25 removed – location not used in this app

$hash = sha1($device_ID . $requestDate . $authorization_code);

//=============================
//  LOG RAW REQUEST (DEBUG)
//=============================
// JE 11/30/2013 make a log entry for this call to the web service
$text = var_export($_REQUEST, true);
// JE 3/10/15 clean quote marks
$test = str_replace(chr(34), "'", $text);

// JE 10/27/25 update service name in log ‘method’
$log_sql = 'insert web_log SET method="' . $service_name . '", text="' . $test . '", created="' . date("Y-m-d H:i:s") . '"';
debug("{$service_name} Web log: " . $log_sql);

// FOR TESTING ONLY  write the values back out so we can see them
if ($debugflag) {
    debug(
        "Device ID: " . $device_ID . "<br>" .
        "Authorization code: " . $authorization_code . "<br>" .
        $requestDate . "<br>" .
        'Key: ' . $key . "<br>" .
        // 'quote serial: ' . $quote_serial . "<br>" .                   // JE 10/27/25 quote_serial not defined in this script
        'Hash ' . $hash . "<br>"
    );
}

//=============================
//  DUPLICATE LOG (LEGACY)
//=============================
// JE 11/30/2013 (kept for history) – corrected method capitalization
$text = var_export($_REQUEST, true);
$test = str_replace(chr(34), "'", $text);
$log_sql_legacy = 'insert web_log SET method="' . $service_name . '", text="' . $test . '", created="' . date("Y-m-d H:i:s") . '"';
debug("{$service_name} Web log (legacy block): " . $log_sql_legacy);

//=============================
//  STUB MODE (TEST DATA)
//=============================
// JE 10/27/25 Provide a stub to return test data for all XML tags
if (isset($_REQUEST['stub'])) {
    $output = '<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Quote list found (STUB)</Message>
<Selections>
  <employee>
    <Name>Jane Doe</Name>
    <Serial>1001</Serial>
  </employee>
  <employee>
    <Name>John Smith</Name>
    <Serial>1002</Serial>
  </employee>
</Selections>
</ResultInfo>';
    send_output($output);
    exit;
}

//=============================
//  SECURITY: KEY CHECK
//=============================
if ($hash != $key) {
    debug("{$service_name}: hash error Key/Hash:<br>{$key}<br>{$hash}<br>");

    $output = "<ResultInfo>
<ErrorNumber>102</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("rrservice", "_err102b") . "</Message>
</ResultInfo>";
    // JE 1/29/2020 New field of \$log_comment allows the error message to be written to the web log
    $log_comment = "Hash:" . $hash . "  and Key:" . $key;
    send_output($output);
    exit;
}

//=============================
//  VERSION CHECK
//=============================
// JE 11/20/2015 make sure they have the current software version.
// NOTE (Giftology): variable name kept for backward compatibility.
// Only the comments refer to “giftology” per checklist.
$current_crewzcontrol_version = get_setting("system", "current_crewzcontrol_version");
debug("{$service_name}: current_crewzcontrol_version = " . $current_crewzcontrol_version);
if ($current_crewzcontrol_version > $crewzcontrol_version) {
    $output = "<ResultInfo>
<ErrorNumber>106</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("rrservice", "_err106") . "</Message>
</ResultInfo>";
    send_output($output);
    exit;
}

//=============================
//  LOCATION REQUIREMENT (REMOVED)
//=============================
// JE  1/1/14 check for longitude and latitude <> 0 if geocode level requires it
// JE 10/27/25 latitude/longitude not used in this app; requirement removed per spec.
// if ($latitude == 0 or $longitude == 0) {
//     $output = "<ResultInfo>
// <ErrorNumber>205</ErrorNumber>
// <Result>Fail</Result>
// <Message>" . get_text("rrservice", "_err205") . "</Message>
// </ResultInfo>";
//     send_output($output);
//     exit;
// }

//=============================
//  LOOKUP BY AUTHORIZATION CODE
//=============================
// JE 11/6/24 - Get the employee based on the authorization code; don't allow expired codes
$sql = 'select * from authorization_code 
        join employee on authorization_code.employee_serial = employee.employee_serial 
        where employee.deleted_flag=0 and authorization_code.authorization_code="' . addslashes($authorization_code) . '"';
debug("{$service_name}: get the code: " . $sql);

// Execute the query and check for success
$result = mysqli_query($mysqli_link, $sql);
if (mysqli_error($mysqli_link)) {
    debug("{$service_name}: line q144 sql error " . mysqli_error($mysqli_link));
    debug("{$service_name}: exit 146");
    exit;
}
$authorization_row = mysqli_fetch_assoc($result);

$employee_serial   = isset($authorization_row["employee_serial"]) ? $authorization_row["employee_serial"] : 0;
$subscriber_serial = isset($authorization_row["subscriber_serial"]) ? $authorization_row["subscriber_serial"] : 0;

debug("{$service_name}: Employee Serial: " . $employee_serial);
debug("{$service_name}: Subscriber Serial: " . $subscriber_serial);

//=============================
//  FETCH EMPLOYEES
//=============================
// JE 1/6/24 if no quote serial is provided, return all employee for subscriber
$sql = 'Select * from employee where subscriber_serial ="' . addslashes($subscriber_serial) . '" order by employee.first_name';
// $sql = 'Select * from employee where subscriber_serial ="' . addslashes($subscriber_serial) . '" order by employee_name'; // JE 10/27/25 legacy alt (kept)
debug("{$service_name}: get the employee record: " . $sql);

// Execute the query
$result = mysqli_query($mysqli_link, $sql);

// JE if error, write out API response.
if (mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    $output = "<ResultInfo>
<ErrorNumber>103</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("rrservice", "_err103a") . " " . htmlspecialchars($error) . "</Message>
</ResultInfo>";
    debug("{$service_name}: Mysql error line 162: " . $error . " -- " . $sql);
    $log_comment = $error;
    send_output($output);
    exit;
}

//=============================
//  BUILD OUTPUT
//=============================
$output = '<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Quote list found</Message>
<Selections>';

while ($employee_row = mysqli_fetch_assoc($result)) {
    // JE get the status, 0 if this employee is not in the quote and 1 if it is.
    // The reason: the mobile app must not allow the same employee to be added again.
    if (trim($employee_row["preferred_name"]) == "") {
        $employee_name = $employee_row["first_name"] . " " . $employee_row["last_name"];
    } else {
        $employee_name = $employee_row["preferred_name"] . " " . $employee_row["last_name"];
    }

    $output .= '
  <employee>
    <Name>' . htmlspecialchars($employee_name) . '</Name>
    <Serial>' . htmlspecialchars($employee_row["employee_serial"]) . '</Serial>
  </employee>';
}

$output .= '
</Selections>
</ResultInfo>';

send_output($output);
exit;

?>
