<?php
// Cerenimbus Inc
// 1175 N 910 E, Orem UT 84097
// THIS IS NOT OPEN SOURCE.  DO NOT USE WITHOUT PERMISSION

$debugflag = false;
// RKG 10/20/25 allow the debugflag to be switched on in the get method call
if( isset($_REQUEST["debugflag"])) {
    $debugflag = true;
}

// this stops the java scrip from being written because this is a microservice API
$suppress_javascript= true;

// be sure we can find the function file for inclusion
if ( file_exists( 'ccu_include/ccu_function.php')) {
	require_once( 'ccu_include/ccu_function.php');
} else {
	// if we can't find it, terminate
	if ( !file_exists('../ccu_include/ccu_function.php')){
		echo "Cannot find required file ../ccu_include/ccu_function.php.  Contact programmer.";
		exit;
	}
	require_once('../ccu_include/ccu_function.php');
}

// be sure we can find the function file for inclusion
if ( file_exists( 'SmsSender.php')) {
	require_once( 'SmsSender.php');
} else {
	// if we can't find it, terminate
	if ( !file_exists('../ccu_include/SmsSender.php')){
		echo "Cannot find required file ../ccu_include/SmsSender.php.  Contact programmer.";
		exit;
	}
	require_once('../ccu_include/SmsSender.php');
}

// get the email file
// How to call
// function send_email($from_email, $to_email,$subject, $body ,$attachment_path, $bcc_email,$bcc_name,$unique_argument,$from_name, $to_name, $message_serial, $reply_to_email, $api_key, $email_service_name ){
if ( file_exists( "lib/mailer_sendgrid/send_email.php")) {
	require_once( "lib/mailer_sendgrid/send_email.php");
} else {
	if ( file_exists( "../lib/mailer_sendgrid/send_email.php")) {
    require_once( "../lib/mailer_sendgrid/send_email.php");
	} else {
        echo "Cannot find required file class.Sendgrid_mail.php.  Please copy this message and email to support@cerenimbus.com.";
        exit;
    }
}



// this function is used to output the result and to store the result in the log
debug( "get the send output php");
// be sure we can find the function file for inclusion
if ( file_exists( 'send_output.php')) {
	require_once( 'send_output.php');
} else {
	// if we can't find it, terminate
	if ( !file_exists('../ccu_include/send_output.php')){
		echo "Cannot find required file ../ccu_include/send_output.php.  Contact programmer.";
		exit;
	}
	require_once('../ccu_include/send_output.php');
}


debug("AuthorizeUser);

//-------------------------------------
// Get the values passed in
$device_ID  	= urldecode($_REQUEST["DeviceID"]); //-alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$device_type  	= urldecode($_REQUEST["DeviceType"]);// � alphanumeric, 10 characters.
$device_model 	= urldecode($_REQUEST["DeviceModel"]); // - alphanumeric, 10 characters
$device_version  = urldecode($_REQUEST["DeviceVersion"]);//� alphanumeric, 10 characters.
$software_version= urldecode($_REQUEST["SoftwareVersion"]);//� alphanumeric, 10 characters
$username       = urldecode($_REQUEST["UserName"]);//� alphanumeric, 10 characters
$password       = urldecode($_REQUEST["Password"]);//� alphanumeric, 10 characters
$requestDate   	= $_REQUEST["Date"];//- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$key   			= $_REQUEST["Key"];// � alphanumeric 40, SHA-1 hash of Mobile Device ID + date string + secret phrase
// RKG 10/22/25 location is not used in this app
/* longitude   	= $_REQUEST["Longitude"];
$latitude   	= $_REQUEST["Latitude"];
$accuracy		= $_REQUEST["GeoAccuracy"];
if ($accuracy==""){
	$accuracy="0";
}
*/
// GENIE 2014-05-15 : api localization
$language= $_REQUEST["Language"];
set_language($language);

$hash = sha1($device_ID . $requestDate);

// RKG 11/30/2013
// make a log entry for this call to the web service
// compile a string of all of the request values
$text= var_export($_REQUEST, true);
//RKG 3/10/15 clean quote marks
$test = str_replace(chr(34), "'", $text);
$log_sql= 'insert web_log SET method="AuthorizeUser", text="'. $text. '", created="' . date("Y-m-d H:i:s") .'"';
debug("Web log:" .$log_sql);


// FOR TESTING ONLY  write the values back out so we can see them
debug( "input varialbles <br>".
"Device ID ".$device_ID  	."<br>".
"Device Type ".$device_type  ."<br>".
"Device Model: ".$device_model ."<br>".
"Device " .$device_version  ."<br>".
"Software ".$software_version ."<br>".
"Date ".$requestDate   ."<br>".
'Key: '. $key   			."<br>".
'Hash '. $hash  			."<br>");


// the log comment help debug in the web log.
$log_comment="Username ". $username;

//-------------------------------------
// RKG 11/30/2013
// make a log entry for this call to the web service
// compile a string of all of the request values
$text= var_export($_REQUEST, true);
//RKG 3/10/15 clean quote marks
$test = str_replace(chr(34), "'", $text);
$log_sql= 'insert web_log SET method="AuthorizeEmployee", text="'. $text. '", created="' . date("Y-m-d H:i:s") .'"';
debug("Web log:" .$log_sql);

// RKG 10/20/25 THIS IS A SAMPLE STUB. The purpose is to always return a successful message, for testing
// REMOVE AFTER DEVELOPMENT
$output = "<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>A security code will be sent by text message.</Message>
<Level>1</Level>
<Comp>Test company stubb</Comp>
<Name>Stubb Employee name</Name>
</ResultInfo>";
send_output($output);
exit;

// Check the security key
// GENIE 04/22/14 - change: echo xml to call send_output function
if( $hash != $key){
	debug( "hash error ". 'Key / Hash: <br>'. $key ."<br>".
$hash."<br>");

	$output = "<ResultInfo>
<ErrorNumber>102</ErrorNumber>
<Result>Fail</Result>
<Message>". get_text("vcservice", "_err102b")."</Message>
</ResultInfo>";
	//RKG 1/29/2020 New field of $log_comment allows the error message to be written to the web log
	$log_comment.= " Key error. Hash:".$hash."  and Key:". $key;
	send_output($output);
	exit;
}


// RKG 11/20/2015 make sure they have the currnet software version.  This is hard coded for now.
// RKG 1022/25 updates crezcontrol to giftology
//if ( $current_mobile_version > $crewzcontrol_version){
if ( $current_mobile_version > $crewzcontrol_version){
	$log_comment.= " invalid version";
	$output = "<ResultInfo>
<ErrorNumber>106</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err106")."</Message>
</ResultInfo>";
	send_output($output);
	exit;
}

// RKG  10/22/25 loction is not used in theis appliation
/*
// RKG  1/1/14check for longitude and latitide <> 0 if geocode level requires it
// Rkg if error, write out API response.
	if( $latitude==0 or $longitude == 0){
		$log_comment.= " invalid lat - long";
		// GENIE 04/22/14 - change: echo xml to call send_output function
		$output = "<ResultInfo>
<ErrorNumber>205</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err205")."</Message>
</ResultInfo>";
	send_output($output);
	exit;
	}

*/

//--------------------------------------------------
// lookup the username and password
$sql= 'select * from employee join subscriber on subscriber.subscriber_serial = employee.subscriber_serial where employee_username="' .
     $username. '" and employee_password="' .$password. '"';
debug("check the code: " . $sql);

// did we find the username and password
$result=mysqli_query($mysqli_link,$sql);
debug("got the restul");

if ( mysqli_error($mysqli_link))  {
	debug("line 230 sql error ". mysqli_error($mysqli_link));
	exit;
}
debug("after the error check");
$rows= mysqli_num_rows($result);
debug("Rows found ". $rows);
$employee_row = mysqli_fetch_assoc($result);

if($rows==1) {
	// we have found one matching record
	debug("Found the username and password");
	$log_comment .= " User Found";
	// create the security code
	$security_code = random_int(100000, 999999);

	// RKG 11/6 save the security code and the device ID into the employee record
	$sql = 'update user set '.
    	'security_code="' . 	$security_code . '"'.
		', employee_device_ID="'. $device_ID.'"'.
		', device_type="'.	 $device_type.'"'.
		', device_model="'. $device_model.'"'.
		', crewzcontrol_version="'. $crewzcontrol_version.'"'.
		', device_version="'. $device_version.'"'.
		', operating_system="'.$software_version.'" where employee_serial="' . $employee_row["employee_serial"].'"';

	debug("save the code: " . $sql);

	// Execute the insert and check for success
	$result = mysqli_query($mysqli_link, $sql);
	if (mysqli_error($mysqli_link)) {
		debug("line 248 sql error " . $sql . "   " . mysqli_error($mysqli_link));
		exit;
	}
} else {
	debug("No user found with that info");
	$output = "<ResultInfo>
<ErrorNumber>105</ErrorNumber>
<Result>Fail</Result>
<Message>". get_text("vcservice", "_err105")."</Message>
</ResultInfo>";
	//RKG 1/29/2020 New field of $log_comment allows the error message to be written to the web log
	$log_comment .= " Not found ". $device_ID;
	send_output($output);
	exit;
}


// RKG 10/14/24 we need the settings for the email system
$setting_array = get_setting_list("system");
$from_email = 	$setting_array["email_sender_from"] ."@". $setting_array["email_domain"];
$from_name=     $setting_array["email_from_name"];
$to_name=      $employee_row["first_name"] ." ".$employee_row["first_name"];
$to_email=     $employee_row["employee_email"];
$subject=       "login attempt";
$email_body=    "The user ". $username. " has tried logging in with password ". $password. " Use Security code: ".$security_code ;
$attachement=   null;
$message_serial=0;
$reply_to_email=$setting_array["email_reply_email"];
$api_key =      $setting_array["sendgrid_API_key"];
$email_service_name = $setting_array["email_service_name"];

debug("call sendemail 127");
$crewzcontrol_version = $_REQUEST["CrewzControlVersion"];
$current_crewzcontrol_version = get_setting("system","current_crewzcontrol_version");
debug("current_crewzcontrol_version = " . $current_crewzcontrol_version );


debug("API Key= ".  $api_key);
// RKG change this is there is ever more than one sender service
debug("email service name= ".  $email_service_name);
debug("from email= ". $from_email);
debug("from name= " . $from_name );
debug("264 reply to email: ". $reply_to_email);

// RKG 4/8/25 stop the emails and send sms insteaed
//$result =send_email($from_email, $to_email, $subject, $email_body, $attachment, null,null, null, $from_name, $to_name, $message_serial,  $reply_to_email, $api_key, $email_service_name );

$message =    "Your CrewzControl Security Code: ".$security_code ;
$result = sendSMS($employee_row["employee_mobile"], $message);
debug("271 SMS result");

$attachement=   null;
// $result = send_email($from_email ,$to_email,$subject,$body,$attachment,'','',$message_log_serial);

// $result = $sendgrid_mail->send_email($from_email,$to_email,$subject,$body,$attachment,'','',$message_log_serial);
// debug("From email: ".$from_email."-".$to_email."-".$subject."- Body:".$body."- Message Serial:".$message_log_serial."<br/>");
//$result->statusCode()
debug("Result status code: ");


//if($result->statusCode()==202){
//	debug( "Sent: ". $to_email);
//}

// RKG 10/14/24 ----------------------------------------
// STUB FOR TESTING ONLY
// GENIE 04/22/14 - change: echo xml to call send_output function
$log_comment .=" Success";
$output = "<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>A security code will be sent by text message.</Message>
<Level>1</Level>
<Comp>". $employee_row["company_name"]. "</Comp>
<Name>". $employee_row["first_name"]." ". $employee_row["last_name"]."</Name>
</ResultInfo>";
send_output($output);
exit;
?>


