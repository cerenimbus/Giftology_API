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

function generateGUID() {
	$data = random_bytes(16);
	$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

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

debug("AuthorizeDeviceID");

//-------------------------------------
// Get the values passed in
$device_ID  	=  urldecode($_REQUEST["DeviceID"]); //-alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate   	= $_REQUEST["Date"];//- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$security_code 	= $_REQUEST["SecurityCode"];//- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$key   			= $_REQUEST["Key"];// � alphanumeric 40, SHA-1 hash of Mobile Device ID + date string + secret phrase
$username       = urldecode($_REQUEST["UserName"]);		// alphanumeric, 10 characters
$password       = urldecode($_REQUEST["Password"]);		// alphanumeric, 10 characters

$hash = sha1($device_ID . $requestDate);

// RKG 11/30/2013
// make a log entry for this call to the web service
// compile a string of all of the request values
$text= var_export($_REQUEST, true);
//RKG 3/10/15 clean quote marks
$test = str_replace(chr(34), "'", $text);
$log_sql= 'insert web_log SET method="AuthorizeDeviceID", text="'. $text. '", created="' . date("Y-m-d H:i:s") .'"';
debug("Web log:" .$log_sql);


// FOR TESTING ONLY  write the values back out so we can see them
debug( "input varialbles <br>".
	"Device ID ".$device_ID  	."<br>".
	"Security Code ".$security_code  	."<br>".
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
	$log_comment= "Hash:".$hash."  and Key:". $key;
	send_output($output);
	exit;
}


	// RKG 11/6/24  Get the user serial - and verify security
// RKG 11/6/24 - Get the emmployee based on the authorization code
$sql= 'select * from user where deleted_flag=0 AND employee_device_ID="' . $device_ID.'"' ;
debug("get the user: " . $sql);

// Execute the insert and check for success
$result=mysqli_query($mysqli_link,$sql);
if ( mysqli_error($mysqli_link)) {
	debug("line q144 sql error ". mysqli_error($mysqli_link));
	debug("exit 146");
	exit;
}
$user_row= mysqli_fetch_assoc($result);
$subscriber_serial = $user_row["subscriber_serial"];
$user_serial = $user_row["user_serial"];
$row_count= mysqli_num_rows($result);
debug("Row count: ". $row_count);
debug("user username: ".  $user_row["user_username"]);
debug("user serial: ".  $user_serial);
debug("Security Code: ". $security_code);

//RKG 11/12/24  make a back door for apple testers. Return unauthorized if not match
if (($row_count>0) OR ( $user_row["email"]=="kirbystuff1@comcast.net" AND $security_code="999999") OR ( $user_row["security_code"]==$security_code)
	OR $security_code=="999999" ){
	// the code is good - continue
} else {
			$output = "<ResultInfo>
<ErrorNumber>203</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err203")."</Message>
</ResultInfo>";
	send_output($output);
	exit;
}


// RKG 1/6/24 Create the authorization Code
// First make a GUID.  GenerateGUID is a function located above that chuncks up a random number.  URLENCODE is then used to remove any characters that could cause problems
// in being sent over the internet.  That could expand some of the characters and make the thing too long, so we have to shop it off
$loop_flag= true;
while($loop_flag ) {
	$authorization_code = substr(urlencode(generateGUID()), 0, 48);
	debug("line 151 code: ".$authorization_code );
	// Now check to see if this GUID is already in use in the authorization_code file

	$sql= 'select * from authorization_code where authorization_code.authorization_code="' . $authorization_code. '"';
	debug("check the code: " . $sql);

	// Execute the insert and check for success
	$result=mysqli_query($mysqli_link,$sql);
	if ( mysqli_error($mysqli_link)) {
		debug("line q144 sql error ". mysqli_error($mysqli_link));
		debug("exit 146");
		exit;
	}
	$rows= mysqli_num_rows($result);
	if($rows==0){

		// Rkg 11/12/24  mark all the other cods for this person as deleted
		$sql= 'update authorization_code set deleted_flag=1 where user_serial="'. $user_serial. '"';
		debug("save the code: " . $sql);

		// Execute the insert and check for success
		$result=mysqli_query($mysqli_link,$sql);
		if ( mysqli_error($mysqli_link) ) {
			debug("line 155 sql error ".$sql."   ". mysqli_error($mysqli_link));
			debug("exit 157");
			exit;
		}

		$sql= 'insert authorization_code set authorization_code.authorization_code="' . $authorization_code. '", user_serial="'. $user_serial. '"';
		debug("save the code: " . $sql);

		// Execute the insert and check for success
		$result=mysqli_query($mysqli_link,$sql);
		if ( mysqli_error($mysqli_link) ) {
			debug("line 155 sql error ".$sql."   ". mysqli_error($mysqli_link));
			debug("exit 157");
			exit;
		}

		// stop looing
		$loop_flag= false;

	} // otherwise keep making a new one because the code was found

	//if the code is not foundk, save the code, and stop the loop

}
debug('out of the authoriztion code loop');
//-------------------------------------
// RKG 110/13/24 The user has no authorization code as of yet.  It will be assigned after the SMS code is verified
// Get the authorization record


// Rkg if error, write out API response.
//if ( mysqlerr( $update_sql)) {
if ( mysqli_error($mysqli_link) ) {
	// GENIE 04/22/14 - change: echo xml to call send_output function
	$output = "<ResultInfo>
<ErrorNumber>103</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err103a")." ". $update_sql ." ". mysqli_error($mysqli_link)."</Message>
</ResultInfo>";
	send_output($output);
	exit;
}


// RKG 11/19/25 ----------------------------------------
$output = "<ResultInfo>
	<ErrorNumber>0</ErrorNumber>
	<Result>Success</Result>
	<Message>Security code accepted</Message>
	<Auth>". $authorization_code. "</Auth>
</ResultInfo>";
send_output($output);
exit;
?>


