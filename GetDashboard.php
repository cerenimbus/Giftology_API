<?php
// Cerenimbus Inc
// 1175 N 910 E, Orem UT 84097
// THIS IS NOT OPEN SOURCE.  DO NOT USE WITHOUT PERMISSION
/*
GetDashboard
Get all the information needed to display the dashboard
*/

$debugflag= false;

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

debug("GetDashboard");

//-------------------------------------
// Get the values passed in
$device_ID  	= urldecode( $_REQUEST["DeviceID"]); //-alphanumeric up to 60 characters which uniquely identifies the mobile device (iphone, ipad, etc)
$requestDate   	= $_REQUEST["Date"];//- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$mobile_version = $_REQUEST["MobileVersion"];
$authorization_code 	= $_REQUEST["AC"];//- date/time as a string � alphanumeric up to 20 [format:  MM/DD/YYYY HH:mm]
$key   			= $_REQUEST["Key"];// � alphanumeric 40, SHA-1 hash of Mobile Device ID + date string + secret phrase
$serial			= $_REQUEST["Serial"];

if( $mobile_version==""){
	$mobile_version=0;
}
$hash = sha1($device_ID . $requestDate.$authorization_code  );

// RKG 11/30/2013
// make a log entry for this call to the web service
// compile a string of all of the request values
$text= var_export($_REQUEST, true);
//RKG 3/10/15 clean quote marks
$test = str_replace(chr(34), "'", $text);
$log_sql= 'insert web_log SET method="GetDashboard", text="'. $text. '", created="' . date("Y-m-d H:i:s") .'"';
debug("Web log:" .$log_sql);


// FOR TESTING ONLY  write the values back out so we can see them
debug(
"Device ID: ".$device_ID  	."<br>".
"AuthroiZation code: ". $authorization_code  ."<br>".
$requestDate   ."<br>".
"Search: ". $search. "<br>".

'Key: '. $key   			."<br>".
'Hash '. $hash  			."<br>"
);




//-------------------------------------

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

// RKG 11/20/2015 make sure they have the currnet software version. 
$current_mobile_version = get_setting("system","current_mobile_version");
    debug("current_mobile_version = " . $current_mobile_version );
    if ( $current_mobile_version > $mobile_version){
        $output = "<ResultInfo>
    <ErrorNumber>106</ErrorNumber>
    <Result>Fail</Result>
    <Message>".get_text("vcservice", "_err106")."</Message>
    </ResultInfo>";
	send_output($output);
	exit;
}



// RKG 11/6/24 - Get the emmployee based on the authorization code
// don't allow expired authorization code
$sql= 'select * from authorization_code join user on authorization_code.user_serial = '.
	' user.user_serial where user.deleted_flag=0 and authorization_code.authorization_code="' . $authorization_code. '"';
debug("122 get the code: " . $sql);

// Execute the insert and check for success
$result=mysqli_query($mysqli_link,$sql);
if ( mysqli_error($mysqli_link)) {
	$error = mysqli_error($mysqli_link);
	debug("line 144 sql error ". $error);
	debug("exit 146");
	exit;
}
$authorization_row = mysqli_fetch_assoc($result);

$user_serial = $authorization_row["user_serial"];
$subscriber_serial = $authorization_row["subscriber_serial"];

debug("UserSerial: ".$user_serial );
debug("Subscriber Serial: ".$subscriber_serial );


// RKG 11/11/25 THIS IS A SAMPLE STUB. The purpose is to always return a successful message, for testing
// REMOVE AFTER DEVELOPMENT
$output = "<ResultInfo>
	<ErrorNumber>0</ErrorNumber>
	<Result>Success</Result>
	<Message>Dashboard Completed</Message>
    <Selections><BestPartner>";

	// RKG G2/9/26 get highest revenue referrals
	// ---------------------------------------------
	
$sql = 'SELECT * 
    FROM contact 
	join revenue on contact.contact_serial=revenue.contact_serial
    WHERE user_serial ="' . $user_serial . '" 
    AND contact.deleted_flag = 0 
	AND revenue.deleted_flag = 0 
    ORDER BY first_name';

debug("159 Contact list SQL for revenue: " . $sql);

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
// Check if any contacts were found

    while ($row = mysqli_fetch_assoc($result)) {
        $contact_name = trim($row["first_name"] . " " . $row["last_name"]);
        $serial = $row["contact_serial"];
        $amount = $row["amount"];

    $output .= '
    <Contact>
      <Name>' . $contact_name . '</Name>
      <Serial>' . $serial . '</Serial>
      <Amount>' . $amount . '</Amount>
    </Contact>';
    }

	$output.="</BestPartner>";
	// -----------------------
	$output.="<Current>";
		// RKG G2/9/26 contacts with referrals
	// ---------------------------------------------
	
$sql = 'SELECT * 
    FROM contact 
    WHERE user_serial ="' . $user_serial . '" 
    AND status="Runway Relationship:
	AND deleted_flag = 0 
    ORDER BY first_name';

debug("159 Contact list SQL for revenue: " . $sql);

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
	// Check if any contacts were found

    while ($row = mysqli_fetch_assoc($result)) {
        $contact_name = trim($row["first_name"] . " " . $row["last_name"]);
        $serial = $row["contact_serial"];
        $phone = $row["mobile_phone"];

    $output .= '
    <Contact>
      <Name>' . $contact_name . '</Name>
      <Serial>' . $serial . '</Serial>
      <Phone>' . $phone . '</Phone>
    </Contact>';
    }

	$output.="</Current>";
	// -----------------------

	$output.="<Recent>";
	// -----------------------

		// RKG G2/9/26 contacts with NO referrals or revenue
	// ---------------------------------------------
	
$sql = 'SELECT * 
    FROM contact 
    WHERE user_serial ="' . $user_serial . '" 
    AND deleted_flag = 0 
    ORDER BY first_name';

debug("159 Contact list SQL for revenue: " . $sql);

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
	// Check if any contacts were found

    while ($row = mysqli_fetch_assoc($result)) {
        $contact_name = trim($row["first_name"] . " " . $row["last_name"]);
        $serial = $row["contact_serial"];
        $phone = $row["mobile_phone"];

		$output .= '
		<Contact>
		<Name>' . $contact_name . '</Name>
		<Serial>' . $serial . '</Serial>
		<Phone>' . $phone . '</Phone>
		</Contact>';
    }

	$output.="</Recent>";

		// RKG 1/19/26 go through the events and show the most recent
				
		$sql = 'SELECT *,
					CONCAT(c.first_name, " ", c.last_name) AS contact_name
				FROM event e
				LEFT JOIN workflow_detail wd ON e.workflow_detail_serial = wd.workflow_detail_serial
				LEFT JOIN contact c ON e.contact_serial = c.contact_serial
				LEFT JOIN user u ON e.user_serial = u.user_serial
				WHERE u.user_serial = ' . intval($user_serial) . '
				AND e.deleted_flag = 0
				AND e.event_completed_date is null 
				ORDER BY e.event_target_date';

		debug("156 Task list SQL: " . $sql);

		$result = mysqli_query($mysqli_link, $sql);
		// Rkg if error, write out API response.
		//if ( mysqlerr( $update_sql)) {
		if (mysqli_error($mysqli_link)) {
			$error = mysqli_error($mysqli_link);
			// GENIE 04/22/14 - change: echo xml to call send_output function
			$output = "<ResultInfo>
				<ErrorNumber>103</ErrorNumber>
				<Result>Fail</Result>
				<Message>Get Task error " . get_text("vcservice", "_err103a") . " " . $sql . " " . $error . "</Message>
				</ResultInfo>";
			debug("Mysql error: " . $error . " -- " . $sql);
			$log_comment = $error;
			send_output($output);
			exit;
		}

		//-------------------------------------
		// RKG 1/29/26 Build output
		$task_count= mysqli_num_rows($result);
		debug("177 count of tasks ".$task_count );
		if ($task_count > 0) {
			while ($task_row = mysqli_fetch_assoc($result)) {

				$output .= '
					<Task>
						<Name>' . htmlspecialchars($task_row["contact_name"]) . '</Name>
						<ContactSerial>' . $task_row["contact_serial"] . '</ContactSerial>
						<TaskSerial>' . $task_row["event_serial"] . '</TaskSerial>
						<TaskName>' . htmlspecialchars($task_row["workflow_detail_name"]) . '</TaskName>
						<Date>' . $task_row["event_target_date"] . '</Date>
					</Task>';
			}
		}

	
		$output .= "<DOVGraph>
			<DataPoint>
				<Label>10/01/2025</Label>
				<Value>46</Value>
			</DataPoint>
			<DataPoint>
				<Label>11/01/2025</Label>
				<Value>89</Value>
			</DataPoint>
			<DataPoint>
				<Label>12/01/2025</Label>
				<Value>345</Value>
			</DataPoint>
		</DOVGraph>
		<RevenueGraph>
			<DataPoint>
				<Label>10/01/2025</Label>
				<Value>10200</Value>
			</DataPoint>
			<DataPoint>
				<Label>11/01/2025</Label>
				<Value>16230</Value>
			</DataPoint>
			<DataPoint>
				<Label>12/01/2025</Label>
				<Value>20100</Value>
			</DataPoint>
		</RevenueGraph>
		<DOV>
			<Name>Phone calls</Name>
			<Count>10000</Count>
		</DOV>
        <DOV>
			<Name>Thank you note</Name>
			<Count>820</Count>
		</DOV>
		<HarmlessStarter>26</HarmlessStarter>
        <Greenlight>95</Greenlight>
        <ClarityConvos>87</ClarityConvos>
        <TotalDOV>65</TotalDOV>
        <Introduction>2</Introduction>
        <Referral>9999</Referral>
        <Partner>500</Partner>
    </Selections>
</ResultInfo>";
send_output($output);
exit;


?>


