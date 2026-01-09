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
		debug("found mailer file in ../lib/mailer_sendgrid/ ");
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


debug("AuthorizeUser");

//-------------------------------------
// Get the values passed in
$device_ID  	=  urldecode($_REQUEST["DeviceID"]); //-alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate   	= $_REQUEST["Date"];//- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$device_type  	= urldecode($_REQUEST["DeviceType"]);	//  alphanumeric, 10 characters.
$device_model 	= urldecode($_REQUEST["DeviceModel"]); 	// - alphanumeric, 10 characters
$device_version  = urldecode($_REQUEST["DeviceVersion"]);// alphanumeric, 10 characters.
$software_version= urldecode($_REQUEST["SoftwareVersion"]);// alphanumeric, 10 characters
$mobile_version= urldecode($_REQUEST["MobileVersion"]);// alphanumeric, 10 characters
$username       = urldecode($_REQUEST["UserName"]);		// alphanumeric, 10 characters
$password       = urldecode($_REQUEST["Password"]);		// alphanumeric, 10 characters
$key   			= trim( $_REQUEST["Key"]);						//  alphanumeric 40, SHA-1 hash of Mobile Device ID + date string + secret phrase

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
if ( $current_mobile_version > $mobile_version){
	$log_comment.= " invalid version";
	$output = "<ResultInfo>
<ErrorNumber>106</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err106")."</Message>
</ResultInfo>";
	send_output($output);
	exit;
}


//--------------------------------------------------
// lookup the username and password
$sql= 'select * from user left join subscriber on subscriber.subscriber_serial = user.subscriber_serial where email="' .
     $username. '" and password="' .$password. '" and status<>"Inactive" and user.deleted_flag=0';
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
$user_row = mysqli_fetch_assoc($result);

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
		', mobile_version="'. $mobile_version.'"'.
		', device_version="'. $device_version.'"'.
		', operating_system="'.$software_version.
		'" where user_serial="' . $user_row["user_serial"].'"';

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
	$log_comment .= " Not found ". $sql;
	send_output($output);
	exit;
}


// RKG 10/14/24 we need the settings for the email system
$from_email = 	$user_row["email_from_email"] ;
$from_name=     $user_row["email_from_name"];
$to_name=      $user_row["first_name"] ." ".$user_row["last_name"];
$to_email=     $user_row["email"];
$subject=       "ROR Login attempt";
$email_body=    "Thank you for logging in to the ROR mobile app. Your ROR security code is: ".$security_code ;
$attachement=   null;
$message_serial=0;
$reply_to_email= $user_row["email_reply_to_email"];
$api_key =      $user_row["email_API_key"];
$email_service_name = "Sendgrid";

debug("call sendemail 127");

debug("API Key= ".  $api_key);
// RKG change this is there is ever more than one sender service
debug("email service name= ".  $email_service_name);
debug("from email= ". $from_email);
debug("from name= " . $from_name );
debug("to email= " . $to_email );
debug("264 reply to email: ". $reply_to_email);
debug("subject= " . $subject );
debug("email bodye= " . $email_body);

// RKG 4/8/25 stop the emails and send sms insteaed
$result =send_email($from_email, $to_email, $subject, $email_body, $attachment, null,null, null, $from_name, $to_name, $message_serial,  $reply_to_email, $api_key, $email_service_name );
debug( "452 ". $to_email. " result ". $result->statusCode());
if($result->statusCode()==202){
	debug( "454 Sent: ". $to_email. " result ". $result->statusCode());
} else {
	debug( "456 SENDING ERROR: ". $to_email. " result ". $result->statusCode());
	var_dump( $result);
}


// RKG 10/14/24 ----------------------------------------
$log_comment .=" Success";
$output = "<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>A security code will be sent by email message.</Message>
<Comp>" . $user_row["company_name"]. "</Comp>
<Name>" . $to_name. "</Name>
</ResultInfo>";
send_output($output);
exit;
?>


