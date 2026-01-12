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


$debugflag = false;

if( isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}
// this stops the java scrip from being written because this is a microservice API
$suppress_javascript = true;

// be sure we can find the function file for inclusion

if (file_exists('ccu_include/ccu_function.php')) {
	require_once('ccu_include/ccu_function.php');
}
else {
  // if we can't find it, terminate
	if (!file_exists('../ccu_include/ccu_function.php')) {
		echo "Cannot find required file ../ccu_include/ccu_function.php.  Contact programmer.";
		exit;
  }
  require_once('../ccu_include/ccu_function.php');
}

// GENIE 04/22/14 (from DeAuthorizeVoter.php)
// this function is used to output the result and to store the result in the log
debug( "get the send output php");
// be sure we can find the function file for inclusion
if ( file_exists( 'send_output.php')) {
    require_once( 'send_output.php');
} else {
    // if we can't find it, terminate
    if ( !file_exists('../ccu_include/send_output.php')){
        echo "Cannot find required file send_output.php Contact programmer.";
        exit;
    }
    require_once('send_output.php');
}



debug("GetContactList");

//-------------------------------------
// Get the values passed in
$device_ID          = urldecode($_REQUEST["DeviceID"] ?? "");
$requestDate        = $_REQUEST["Date"] ?? "";
$authorization_code = $_REQUEST["AC"] ?? "";
$key                = $_REQUEST["Key"] ?? "";
$language           = $_REQUEST["Language"] ?? "en";
$mobile_version     = $_REQUEST["MobileVersion"] ?? "1.0";

$hash = sha1($device_ID . $requestDate.$authorization_code  );

// RKG 11/30/2013
// make a log entry for this call to the web service
// compile a string of all of the request values
$text= var_export($_REQUEST, true);

//RKG 3/10/15 clean quote marks
$test = str_replace(chr(34), "'", $text);
$log_sql= 'insert web_log SET method="GetContactList", text="'. $text. '", created="' . date("Y-m-d H:i:s") .'"';
debug("Web log:" .$log_sql);

// FOR TESTING ONLY  write the values back out so we can see them
debug(
"Device ID: ".$device_ID  	."<br>".
"Authorization code: ". $authorization_code  ."<br>". 
$requestDate   ."<br>".
'Key: '. $key   			."<br>".
'Hash '. $hash  			."<br>"
);

// // STUB: Return only ONE contact to simulate the specific query
// $output = '<ResultInfo>
//   <ErrorNumber>0</ErrorNumber>
//   <Result>Success</Result>
//   <Message>Contact list retrieved successfully (STUB)</Message>
//   <Contacts>
//     <Contact>
//       <Name>James E</Name>
//       <Serial>1001</Serial>
//       <Status>Active</Status>
//     </Contact>
//     <Contact>
//       <Name>Alfred C</Name>
//       <Serial>1002</Serial>
//       <Status>Active</Status>
//     </Contact>
//     <Contact>
//       <Name>Janvel A</Name>
//       <Serial>1003</Serial>
//       <Status>Active</Status>
//     </Contact>
//   </Contacts>
// </ResultInfo>';

// send_output($output);
// exit;


// Check the security key
// GENIE 04/22/14 - change: echo xml to call send_output function
if( $hash != $key){
	debug( "hash error ". 'Key / Hash: <br>'. $key ."<br>".
	$hash."<br>");

	$output = "<ResultInfo>
	<ErrorNumber>102</ErrorNumber>
	<Result>Fail</Result>
	<Message>". get_text("rrservice", "_err102b")."</Message>
	</ResultInfo>";
	//RKG 1/29/2020 New field of $log_comment allows the error message to be written to the web log
	$log_comment= "Hash:".$hash."  and Key:". $key;
	send_output($output);
	exit;
}

// RKG 11/20/2015 make sure they have the currnet software version. 
$current_mobile_version = get_setting("system","current_mobile_version");
    debug("current_mobile_version = " . $current_mobile_version );
    if ( $current_mobile_version > $mobile_version){
        $output = "<ResultInfo>
    <ErrorNumber>106</ErrorNumber>
    <Result>Fail</Result>
    <Message>".get_text("rrservice", "_err106")."</Message>
    </ResultInfo>";
	send_output($output);
	exit;
}

// Retrieve user info from authorization code
$authorization_sql = 'select * from authorization_code 
                      join user on authorization_code.user_serial = user.user_serial 
                      where user.deleted_flag=0 
                      and authorization_code.authorization_code="' . $authorization_code . '"';

debug($authorization_sql);

// Excute and check for success
$authorization_result=mysqli_query($mysqli_link,$authorization_sql);
if ( mysqlerr( $authorization_sql)) {

    exit;

}

$authorization_row= mysqli_fetch_array( $authorization_result);
$authorization_row_count = mysqli_num_rows($authorization_result);
//-------------------------------------

// If no authorization code is returned, give an error code indicating it was not found
debug( "check for code found");

debug($authorization_row['authorization_code']." = ". $authorization_code  );

if ( $authorization_row['authorization_code']!= $authorization_code OR  $authorization_row_count==0 ){

    // RKG 12/8/25 return error "invalid authorization code" if not found

    $output = "<ResultInfo>

<ErrorNumber>202</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("rrservice", "_err202a")."</Message>
</ResultInfo>";
send_output($output);
    exit;
}


$contact_serial = $authorization_row["contact_serial"] ?? null;

$sql = 'SELECT 
    c.contact_serial, 
    c.first_name, 
    c.last_name, 
    c.status
    FROM contact c
    -- LEFT JOIN contact_to_user ctu ON c.contact_serial = ctu.contact_serial AND ctu.deleted_flag = 0
    -- LEFT JOIN user u ON ctu.contact_to_user_serial = u.user_serial AND u.deleted_flag = 0
    WHERE c.contact_serial ="' . $contact_serial . '" 
    AND c.deleted_flag = 0';

// IF a specific serial was requested, append the filter
if (!empty($target_contact_serial)) {
  $safe_serial = mysqli_real_escape_string($mysqli_link, $target_contact_serial);
  $sql .= " AND contact_serial = '$safe_serial'";
  }
  // Order by name
  $sql .= " ORDER BY first_name";

debug("Contact list SQL: " . $sql);

$result = mysqli_query($mysqli_link, $sql);

// Rkg if error, write out API response.
//if ( mysqlerr( $update_sql)) {
if (mysqli_error($mysqli_link)) {
    $error = mysqli_error($mysqli_link);
    
	// GENIE 04/22/14 - change: echo xml to call send_output function
	$output = "<ResultInfo>
		<ErrorNumber>103</ErrorNumber>
		<Result>Fail</Result>
		<Message>" . get_text("rrservice", "_err103a") . " " . $update_sql . " " . $error . "</Message>
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
?>