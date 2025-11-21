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


// RKG 11/11/25 THIS IS A SAMPLE STUB. The purpose is to always return a successful message, for testing
// REMOVE AFTER DEVELOPMENT
$output = "<ResultInfo>
	<ErrorNumber>0</ErrorNumber>
	<Result>Success</Result>
	<Message>Security code accepted</Message>
	<Auth>this is a test authorization code for testing only</Auth>
    <Selections>
		<BestPartner>
			<Name>Kirby Glad</Name>
			<ContactSerial>56</ContactSerial>
			<Amount>200</Amount>
		</BestPartner>
        <BestPartner>
			<Name>Tom Thumb</Name>
			<ContactSerial>89</ContactSerial>
			<Amount>2100</Amount>
		</BestPartner>
        <BestPartner>
			<Name>Silly Sally</Name>
			<ContactSerial>99999</ContactSerial>
			<Amount>1</Amount>
		</BestPartner>
		<BestPartner>
			<Name>Bill Clinton</Name>
			<ContactSerial>99977</ContactSerial>
			<Amount>1,000,000</Amount>
		</BestPartner>
		<Current>
			<Name>Silly Sally</Name>
			<ContactSerial>99999</ContactSerial>
			<Phone>(810) 555-1212</Phone>
		</Current>
        <Current>
			<Name>Billy Barty</Name>
			<ContactSerial>999</ContactSerial>
			<Phone>(810) 121-1212</Phone>
		</Current>
		<Current>
			<Name>Sarah Barty</Name>
			<ContactSerial>65999</ContactSerial>
			<Phone>(810) 121-1212</Phone>
		</Current>
		<Recent>
			<Name>Jack Sprat</Name>
			<ContactSerial>999</ContactSerial>
			<Phone>(810) 222-1212)</Phone>
		</Recent>
        <Recent>
			<Name>Jack Nimble</Name>
			<ContactSerial>10999</ContactSerial>
			<Phone>(810) 222-3333)</Phone>
		</Recent>
		<Recent>
			<Name>Jack Sprat</Name>
			<ContactSerial>999</ContactSerial>
			<Phone>(810) 222-1212)</Phone>
		</Recent>
        <Recent>
			<Name>Tom Cruise</Name>
			<ContactSerial>9996</ContactSerial>
			<Phone>(810) 222-1234)</Phone>
		</Recent>
		<Task>
			<Name>Tom Thumb</Name>
			<ContactSerial>25</ContactSerial>
			<TaskSerial>9999</TaskSerial>
			<TaskName>JUmping Jacks<TaskName>
			<Date>11-12-2025</Date>
		</Task>
        <Task>
			<Name>Tim Thumb</Name>
			<ContactSerial>27</ContactSerial>
			<TaskSerial>99997</TaskSerial>
			<TaskName>Thank you note<TaskName>
			<Date>11-20-2025</Date>
		</Task>
		<Task>
			<Name>Saul Alinski</Name>
			<ContactSerial>27</ContactSerial>
			<TaskSerial>99997</TaskSerial>
			<TaskName>Drop By<TaskName>
			<Date>11-20-2025</Date>
		</Task>
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
$current_crewzcontrol_version = get_setting("system","current_crewzcontrol_version");
debug("current_crewzcontrol_version = " . $current_crewzcontrol_version );
if ( $current_crewzcontrol_version > $crewzcontrol_version){
	$output = "<ResultInfo>
<ErrorNumber>106</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err106")."</Message>
</ResultInfo>";
	send_output($output);
	exit;
}

// RKG  1/1/14check for longitude and latitide <> 0 if geocode level requires it
// Rkg if error, write out API response.
	if( $latitude==0 or $longitude == 0){
		// GENIE 04/22/14 - change: echo xml to call send_output function
		$output = "<ResultInfo>
<ErrorNumber>205</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err205")."</Message>
</ResultInfo>";
	send_output($output);
	exit;
	}

// RKG 11/6/24 - Get the emmployee based on the authorization code
// don't allow expired authorization code
$sql= 'select * from authorization_code join employee on authorization_code.employee_serial = employee.employee_serial where employee.deleted_flag=0 and authorization_code.authorization_code="' . $authorization_code. '"';
debug("get the code: " . $sql);

// Execute the insert and check for success
$result=mysqli_query($mysqli_link,$sql);
if ( mysqli_error($mysqli_link)) {
	$error = mysqli_error($mysqli_link);
	debug("line 144 sql error ". $error);
	debug("exit 146");
	exit;
}
$authorization_row = mysqli_fetch_assoc($result);

$employee_serial = $authorization_row["employee_serial"];
$subscriber_serial = $authorization_row["subscriber_serial"];

debug("Employee Serial: ".$employee_serial );
debug("Subscriber Serial: ".$subscriber_serial );

//RLG 12/11/24 update the most recent quotes list for this employee
// First, delete any other access of this employee to this quote
$sql= 'update recent_quote set deleted_flag=1 where quote_serial="'. $serial. '" and employee_serial="' . $employee_serial. '"';
debug("clear previous quotes: " . $sql);

// Execute the insert and check for success
$result=mysqli_query($mysqli_link,$sql);
if (mysqli_error($mysqli_link )) {
	$error =  mysqli_error($mysqli_link);
	$output = "<ResultInfo>
<ErrorNumber>104</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err104a")." ". $update_sql ." ". mysqli_error($mysqli_link)."</Message>
</ResultInfo>";
	debug("Mysql error: ". $error. " -- ". $sql);
	$log_comment= "Error 170 -". $error;
	send_output($output);
	exit;
}


// RKG 11/6/24 - Check for quote serial
if ($serial ==""){
	// first get the closer one'
	debug("Serial ". $serial);
	$output = "<ResultInfo>
<ErrorNumber>104</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err104a")." ". $update_sql ." ". mysqli_error($mysqli_link)."</Message>
</ResultInfo>";
	$log_comment= "No quote serial";
	send_output($output);
	exit;
}


// RKG 12/11/24 now add this quote to the list
$sql= 'insert into recent_quote set quote_serial="'. $serial. '", employee_serial="' . $employee_serial. '"';
debug("insert into recent line 198: " . $sql);

// Execute the insert and check for success
$result=mysqli_query($mysqli_link,$sql);
if (mysqli_error($mysqli_link )) {
	$error =  mysqli_error($mysqli_link);
	$output = "<ResultInfo>
<ErrorNumber>104</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err104a")." ". $sql ." ". mysqli_error($mysqli_link)."</Message>
</ResultInfo>";
	debug("Mysql error: ". $error. " -- ". $sql);
	$log_comment= "Error 170 -". $error;
	send_output($output);
	exit;
} //error

//RKG 11/18/24 Get the most quotes
$sql = 'select * from quote where quote_serial ="'. $serial.'"';
debug("get the selected record line 219: " . $sql);

// Execute the insert and check for success
$result=mysqli_query($mysqli_link,$sql);

if (mysqli_error($mysqli_link )) {
		$error =  mysqli_error($mysqli_link);
			$output = "<ResultInfo>
<ErrorNumber>104</ErrorNumber>
<Result>Fail</Result>
<Message>".get_text("vcservice", "_err104a")." ". $sql ." ". mysqli_error($mysqli_link)."</Message>
</ResultInfo>";
	debug("Mysql error: ". $error. " -- ". $sql);
	$log_comment= "Error 196 -". $error;
	send_output($output);
	exit;
}
$quote_row = mysqli_fetch_assoc( $result);
$quote_serial = $serial;

//*********************************************************************************

// RKG 10/6/25 must complete by date is set t0 9990-01-01 then dont send it
// this is used to properly sort a blank deadline date
$display_complete_date = $quote_row["must_complete_by_date"];
If($display_complete_date == "9999-01-01"){
	$display_complete_date ="";
}

	$output = '<ResultInfo>
<ErrorNumber>0</ErrorNumber>
<Result>Success</Result>
<Message>Quote Found</Message>
<Selections>
	<Quote>
		<Name>' . $quote_row["quote_contact_name"] .'</Name>
		<Address>' .  $quote_row["quote_address"]. '</Address>
		<City>' .  $quote_row["quote_city"]. '</City>
		<Amount>' .  $quote_row["quote_amount"]. '</Amount>
		<Expense>' .  $quote_row["quote_expense"]. '</Expense>
		<Status>' .  $quote_row["quote_status"]. '</Status>
		<Serial>' .  $quote_row["quote_serial"]. '</Serial>
		<QuoteNum>'. $quote_row["quote_number"]. '</QuoteNum>
		<Hour>' .  	$quote_row["quote_hour"]. '</Hour>
		<MultiDayHour>' .  $quote_row["multi_day_hour"]. '</MultiDayHour>
		<MultiDayFlag>' .  $quote_row["multi_day_flag"]. '</MultiDayFlag>
		<Priority>' . 	$quote_row["priority"] .'</Priority>
		<MustCompleteBy>' . display_date($display_complete_date ).'</MustCompleteBy>
		<NiceToHaveBy>' .display_date($quote_row["nice_to_have_by_date"] ).'</NiceToHaveBy>
		<BlackoutDate>' .$quote_row["blackout_date"].'</BlackoutDate>
		<NotBefore>' . display_date($quote_row["do_not_schedule_before_date"]) .'</NotBefore>
		<Services>';

	// Get the quote number specified
	$sql = 'Select * from quote_detail left join service on quote_detail.service_serial = service.service_serial where quote_detail.deleted_flag=0 and quote_detail.quote_serial ="'. $quote_serial.'"';
	debug("get the quote detail services line 315: " . $sql);

	// Execute  and check for success
	$quote_detail_result=mysqli_query($mysqli_link,$sql);
	// Rkg if error, write out API response.
	if ( mysqli_error($mysqli_link) ) {
		$error = mysqli_error($mysqli_link);
		// GENIE 04/22/14 - change: echo xml to call send_output function
		$output = "<ResultInfo>
		<ErrorNumber>103</ErrorNumber>
		<Result>Fail</Result>
		<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
		</ResultInfo>";
		$log_comment = "Error 342 " . $error;
		send_output($output);
		debug("Mysql error  schedule day 337- copy this entire messsage and email to support@cerenimbus.com: ".$error. "  ". $sql);
			exit;
	}
	while( $service_row = mysqli_fetch_assoc( $quote_detail_result) ) {
		$output .= '
		<Service>
		<ServiceSerial>' . $service_row["service_serial"] . '</ServiceSerial>
		<QuoteDetailName>' . $service_row["quote_detail_name"] . '</QuoteDetailName>
		<QuoteDetailNote>' . $service_row["quote_detail_note"] . '</QuoteDetailNote>
		<Quantity>' . $service_row["quantity"] . '</Quantity>';

		// RKG 1/24/24 If this is the first time in this quote then we need to create the
		// work package detail with the alternatives
		// quote update statuses
		// Created - just means the record was create, autopopulated
		// Populated - the work packages have been updated
		// Ready - hours have been set for the job, so we assume it is ready to schedule.

		// kg 1/23/25 use this to flag if detail was created for workpackages and anlternates.
		// RKG 7/7/25 TODO - delete any previous work package details
		$update_status_flag = false;
		if ($quote_row["quote_update_status"] == "Created") {

			$update_status_flag = true;
			// set a flag that we need to update the status

			$sql = 'Select * from service_to_work_package join work_package on service_to_work_package.work_package_serial = work_package.work_package_serial  where service_to_work_package.deleted_flag=0 ' .
				' and work_package.deleted_flag=0 and service_to_work_package.service_serial ="' . $service_row["service_serial"] . '"';
			debug("Line 358 get the  services work package: " . $sql);

			// Execute and check for success
			$work_package_result = mysqli_query($mysqli_link, $sql);
			// Rkg if error, write out API response.
			if (mysqli_error($mysqli_link)) {
				$error = mysqli_error($mysqli_link);
				// GENIE 04/22/14 - change: echo xml to call send_output function
				$output = "<ResultInfo>
				<ErrorNumber>103</ErrorNumber>
				<Result>Fail</Result>
				<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
				</ResultInfo>";
				$log_comment = "Error 376 " . $error;
				send_output($output);
				echo("Mysql error - schedule day 378 copy this entire messsage and email to support@cerenimbus.com: " . $error . "  " . $sql);
				exit;
			} // if error

			while ($work_package_row = mysqli_fetch_assoc($work_package_result)) {
				debug("line 369 got the work package " . $work_package_row["work_package_name"]);

				// RKG 1/23/25 for this service copy the Work Packges for service to this quote detail
				$sql = 'insert into quote_detail_to_work_package set quote_detail_serial="' . $service_row["quote_detail_serial"] . '", work_package_serial="' . $work_package_row["work_package_serial"] . '"';
				debug("Line 373 create the quote detail to work package record: " . $sql);

				// Execute the insert and check for success
				$result = mysqli_query($mysqli_link, $sql);
				// Rkg if error, write out API response.
				if (mysqli_error($mysqli_link)) {
					$error = mysqli_error($mysqli_link);
					// GENIE 04/22/14 - change: echo xml to call send_output function
					$output = "<ResultInfo>
					<ErrorNumber>103</ErrorNumber>
					<Result>Fail</Result>
					<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
					</ResultInfo>";
					$log_comment = "Error 393 " . $error;
					send_output($output);
					debug("Mysql error  schedule day 388- copy this entire messsage and email to support@cerenimbus.com: " . $error . "  " . $sql);
					exit;
				} // error

				// RKG /23/25  Get the sesrial number of the record just created
				$quote_detail_to_work_package_serial = mysqli_insert_id($mysqli_link);

				// RKG 1/23/25 add the alternatives for this work package
				$sql = 'Select * from work_package_alternative join work_package on work_package_alternative.work_package_serial = work_package.work_package_serial  where work_package_alternative.deleted_flag=0 ' .
					' and work_package_alternative.work_package_serial ="' . $work_package_row["work_package_serial"] . '"';
				debug("Line 390 get the quote work package alternative: " . $sql);

				// Execute the insert and check for success
				$work_alternative_result = mysqli_query($mysqli_link, $sql);
				// Rkg if error, write out API response.
				if (mysqli_error($mysqli_link)) {
					$error = mysqli_error($mysqli_link);
					// GENIE 04/22/14 - change: echo xml to call send_output function
					$output = "<ResultInfo>
					<ErrorNumber>103</ErrorNumber>
					<Result>Fail</Result>
					<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
					</ResultInfo>";
					$log_comment = "Error 410 " . $error;
					send_output($output);
					debug("Mysql error  schedule day 405 - copy this entire messsage and email to support@cerenimbus.com: " . $error . "  " . $sql);
					exit;
				}  // error

				while ($work_alternative_row = mysqli_fetch_assoc($work_alternative_result)) {
					debug("line 403 got the alternative work package " . $work_alternative_row["work_package_name"]);

					// RKG 1/23/25 for each work package, copy the alternatves to the detail fork pakcage for this quote work package
					$sql = 'insert into quote_detail_work_package_alternative set ' .
						'quote_detail_to_work_package_serial="' . $quote_detail_to_work_package_serial . '"' .
						', work_package_alternative_serial="' . $work_alternative_row["alternative_work_package_serial"] . '"' .
						', alternative_priority="' . $work_alternative_row["alternative_priority"] . '"' .
						', alternative_status="0"' .// . $work_alternative_row["alternative_status"] . '"' .
						', add_hours="' . $work_alternative_row["add_hours"] . '"' .
						', alternate_note="' . $work_alternative_row["alternate_note"] . '"';

					debug("Line 413 create the quote detail to work package record: " . $sql);

					// Execute the insert and check for success
					$result = mysqli_query($mysqli_link, $sql);
					// Rkg if error, write out API response.
					if (mysqli_error($mysqli_link)) {
						$error = mysqli_error($mysqli_link);
						// GENIE 04/22/14 - change: echo xml to call send_output function
						$output = "<ResultInfo>
						<ErrorNumber>103</ErrorNumber>
						<Result>Fail</Result>
						<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
						</ResultInfo>";
						$log_comment = "Error 433 " . $error;
						send_output($output);
						debug("Mysql error  schedule day 428 - copy this entire messsage and email to support@cerenimbus.com: " . $error . "  " . $sql);
						exit;
					} // no error on insert


				} // end for each alternative


			}     //end for weach work package

		} // end of if work items not added yet

		// RKG 1/23/25 if the flag was set to update the state to Populated, then do so. It mean we have added in the work package detail
		if ($update_status_flag) {

			// RKG 1/23/25 add the alternatives for this work package
			$sql = 'update quote set quote_update_status="Populated" where quote_serial="' . $quote_row["quote_serial"] . '"';
			debug("Line 45 get update the quote status: " . $sql);

			// Execute the insert and check for success
			$result = mysqli_query($mysqli_link, $sql);
			// Rkg if error, write out API response.
			if (mysqli_error($mysqli_link)) {
				$error = mysqli_error($mysqli_link);
				$output = "<ResultInfo>
				<ErrorNumber>103</ErrorNumber>
				<Result>Fail</Result>
				<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
				</ResultInfo>";
				$log_comment = "Error 240 " . $error;
				send_output($output);
				debug("Mysql error schedule day 453 - copy this entire messsage and email to support@cerenimbus.com: " . $error . "  " . $sql);
				exit;
			} // error

		} // of update status flag

		debug("**************************");

		// RKG 12/20/24 get the work_packages for this quote
		$sql = 'Select * from quote_detail_to_work_package join work_package on quote_detail_to_work_package.work_package_serial = work_package.work_package_serial where ' .
			'quote_detail_to_work_package.deleted_flag=0 and work_package.deleted_flag=0 and quote_detail_to_work_package.quote_detail_serial ="' . $service_row["quote_detail_serial"] . '"';
		debug("478 get the quote detail services: " . $sql);

		// Execute the insert and check for success
		$work_package_result = mysqli_query($mysqli_link, $sql);
		// Rkg if error, write out API response.
		if (mysqli_error($mysqli_link)) {
			$error = mysqli_error($mysqli_link);
			// GENIE 04/22/14 - change: echo xml to call send_output function
			$output = "<ResultInfo>
			<ErrorNumber>103</ErrorNumber>
			<Result>Fail</Result>
			<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
			</ResultInfo>";
			debug("Mysql error: " . $error . "  ", $sql);
			$log_comment = "Error 240 " . $error;
			send_output($output);
			exit;
		} // error

		$output .= '<WorkPackages>';

		while ($work_package_row = mysqli_fetch_assoc($work_package_result)) {
			debug( "504 get the work pacage row for the service");
			if ($work_package_row['work_package_name'] != "") {
				//				<WorkPackageSerial>' . $work_package_row['quote_detail_serial'] . '</WorkPackageSerial>';
				// RKG 8/5/25 make sure we are getting the right files
				if ( $debugflag){
					debug("502 record dump");
					var_dump($work_package_row);
					debug("");
				}

				$output .= '
				<WorkPackage> 
				<WorkPackageName>' . $work_package_row['work_package_name'] . '</WorkPackageName>
				<WorkPackageSerial>' . $work_package_row['work_package.work_package_serial'] . '</WorkPackageSerial>';

				debug("517 call detail for work package from service " .$work_package_row['work_package_name']);

				$output .= '<WorkPackageDetail>';
					// RKG 8/6/25 
					// $output .= get_work_package_detail_output("WorkPackageItem",$work_package_row['quote_detail_serial'] );
					$output .= get_work_package_detail_output("WorkPackageItem",$work_package_row['work_package.work_package_serial'] );
				$output .= '</WorkPackageDetail>';
			

				// RKG 1/23/25 Get the work package alternates
				$output .= '<WorkPackageAlternates>';
				$sql = 'Select * from quote_detail_work_package_alternative join work_package on quote_detail_work_package_alternative.work_package_alternative_serial = work_package.work_package_serial  where ' .
					'quote_detail_work_package_alternative.deleted_flag=0 and quote_detail_work_package_alternative.quote_detail_to_work_package_serial ="' . $work_package_row["quote_detail_to_work_package_serial"] . '"';
				debug("get the alternatives for this work package: " . $sql);

				// Execute the insert and check for success
				$alternative_package_result = mysqli_query($mysqli_link, $sql);
				// Rkg if error, write out API response.
				if (mysqli_error($mysqli_link)) {
					$error = mysqli_error($mysqli_link);
					// GENIE 04/22/14 - change: echo xml to call send_output function
					$output = "<ResultInfo>
					<ErrorNumber>103</ErrorNumber>
					<Result>Fail</Result>
					<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
					</ResultInfo>";
					debug("Mysql error: " . $error . "  ", $sql);
					$log_comment = "Error 240 " . $error;
					send_output($output);
					exit;
				}  // end of error

				while ($alternative_package_row = mysqli_fetch_assoc($alternative_package_result)) {
					$output .= '<WorkPackageAlternate>
					<AlternateName>' . $alternative_package_row["work_package_name"] . '</AlternateName>
					<AlternatePriority>' . $alternative_package_row["alternative_priority"] . '</AlternatePriority>
					<AlternateStatus>' . $alternative_package_row["alternative_status"] . '</AlternateStatus>
					<AlternateHour>' . $alternative_package_row["add_hours"] . '</AlternateHour>
					<AlternateSerial>' . $alternative_package_row["quote_detail_work_package_alternative_serial"] . '</AlternateSerial>';

					debug("555 call detail for work alternate package from service " . $alternative_package_row["work_package_name"]);
					$output .= '<AlternateWorkPackageDetail>';
						$output .= get_work_package_detail_output("AlternateWorkPackageItem", $alternative_package_row["quote_detail_work_package_alternative_serial"]  );
					$output .= '</AlternateWorkPackageDetail>';
					$output .= '</WorkPackageAlternate>';

				} // end of loop for work package alternitives
				$output .= '</WorkPackageAlternates></WorkPackage>';
			} // end if work package name is not blank
		}  // end of work package for each service
		$output .= '
		</WorkPackages>
		</Service>';
	} // end of loop of all services

	$output .= '</Services>';

	debug("************** Done with services");

	//-----------------------------------------------------------------
	// RKG 1/24/25  Get work packages for this quote- this is NOT from the quote detail

	// RKG 12/20/24 get the work_packages for this quote
	$sql = 'Select * from quote_to_work_package join work_package on quote_to_work_package.work_package_serial = work_package.work_package_serial where quote_to_work_package.deleted_flag=0 '.
		' and quote_to_work_package.quote_serial ="' . $quote_serial . '"';
	debug("Line 581 get the quote to work packages: " . $sql);

	// Execute the insert and check for success
	$work_package_result = mysqli_query($mysqli_link, $sql);
	// Rkg if error, write out API response.
	if (mysqli_error($mysqli_link)) {
		$error = mysqli_error($mysqli_link);
		// GENIE 04/22/14 - change: echo xml to call send_output function
		$output = "<ResultInfo>
		<ErrorNumber>103</ErrorNumber>
		<Result>Fail</Result>
		<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
		</ResultInfo>";
		debug("Mysql error: " . $error . "  ", $sql);
		$log_comment = "Error 240 " . $error;
		send_output($output);
		exit;
	}

	$output .= '<QuoteWorkPackages>';

	// For each work package get the alternatives if exist
	while ($work_package_row = mysqli_fetch_assoc($work_package_result)) {
		debug("544 work package found ".$work_package_row['work_package_name'] );
		debug( "Work package Serial: " . $work_package_row['work_package_serial']  );

		if ($work_package_row['work_package_name'] != "") {
				$output .= '
			<QuoteWorkPackage> 
			<QuoteWorkPackageName>' . $work_package_row['work_package_name'] . '</QuoteWorkPackageName>
			<QuoteWorkPackageSerial>' . $work_package_row['quote_to_work_package_serial'] . '</QuoteWorkPackageSerial>';

			
			$output .= '<QuoteWorkPackageDetail>';
							// RKG 7/18/25  add the details of the work packages for the display
				$output .= get_work_package_detail_output("QuoteWorkPackageItem", $work_package_row['quote_to_work_package_serial']  );
			$output .= '</QuoteWorkPackageDetail>';

			$output .='<QuoteWorkPackageAlternates>';

			$sql = 'Select * from quote_work_package_alternative join work_package on quote_work_package_alternative.work_package_alternative_serial = work_package.work_package_serial  where ' .
				'quote_work_package_alternative.deleted_flag=0 and quote_work_package_alternative.quote_to_work_package_serial ="' . $work_package_row['quote_to_work_package_serial'] . '"';
			debug("626 get the alternatives for this work package: " . $sql);

			// Execute the insert and check for success
			$alternative_package_result = mysqli_query($mysqli_link, $sql);
			// Rkg if error, write out API response.
			if (mysqli_error($mysqli_link)) {
				$error = mysqli_error($mysqli_link);
				// GENIE 04/22/14 - change: echo xml to call send_output function
				$output = "<ResultInfo>
	<ErrorNumber>103</ErrorNumber>
	<Result>Fail</Result>
	<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
	</ResultInfo>";
				debug("Mysql error: " . $error . "  ", $sql);
				$log_comment = "Error 240 " . $error;
				send_output($output);
				exit;
			}

			while ($alternative_package_row = mysqli_fetch_assoc($alternative_package_result)) {
				debug( "646 get work package alternative");
				if ($debugflag){
					var_dump($alternative_package_row);
					debug("");
				}
				$output .= '<QuoteWorkPackageAlternate>
					<WPAlternateName>' . $alternative_package_row["work_package_name"] . '</WPAlternateName>
					<WPAlternatePriority>' . $alternative_package_row["alternative_priority"] . '</WPAlternatePriority>
					<WPAlternateStatus>' . $alternative_package_row["alternative_status"] . '</WPAlternateStatus>
					<WPAlternateHour>' . $alternative_package_row["add_hours"] . '</WPAlternateHour>
					<WPAlternateSerial>' . $alternative_package_row["quote_work_package_alternative_serial"] . '</WPAlternateSerial>';

					$output .= '<WPAlternateDetail>';
							// RKG 7/18/25  add the details of the work packages for the display
					$output .= get_work_package_detail_output("WPAlternateItem",  $alternative_package_row["work_package_alternative_serial"]  );
					$output .= '</WPAlternateDetail>';

				$output .= '</QuoteWorkPackageAlternate>';

			} // end of loop for work package alternitives

			$output .= '</QuoteWorkPackageAlternates>';
		} // if work package name is not empty
		$output .= '</QuoteWorkPackage>';
	}  // work package row loop

	$output .= '
		</QuoteWorkPackages>';


	//-------------------------------------------------------------------
	// RKG 12/20/24 get the skills for this quote
	$sql = 'Select * from quote_to_skill join skill on quote_to_skill.skill_serial = skill.skill_serial where quote_to_skill.deleted_flag=0 and quote_to_skill.quote_serial ="' . $quote_serial . '"';
	debug("get the quote detail services: " . $sql);

	// Execute the insert and check for success
	$skill_result = mysqli_query($mysqli_link, $sql);
	// Rkg if error, write out API response.
	if (mysqli_error($mysqli_link)) {
		$error = mysqli_error($mysqli_link);
		// GENIE 04/22/14 - change: echo xml to call send_output function
		$output = "<ResultInfo>
<ErrorNumber>103</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
</ResultInfo>";
		debug("Mysql error: " . $error . "  ", $sql);
		$log_comment = "Error 240 " . $error;
		send_output($output);
		exit;
	} // end of error

	$output .= '<Skills>';

	while ($skill_row = mysqli_fetch_assoc($skill_result)) {
		if ($skill_row['skill_name'] != "") {
			// RKG 10/12/25 add the day number 
			$output .= '
	<Skill> 
		<SkillSerial>' . $skill_row['skill_serial'] . '</SkillSerial>
		<SkillName>' . $skill_row['skill_name'] . '</SkillName>
		<SkillCount>' . $skill_row['quantity'] . '</SkillCount>
		<SkillDay>' . $skill_row['day_number'] . '</SkillDay>
	</Skill>';
		}  // if skill name not empty
	} // end of skill row loop
	$output .= '
</Skills>';


// RKG 12/20/24 get the equipments for this quote
	$sql = 'Select * from quote_to_equipment join equipment on quote_to_equipment.equipment_serial = equipment.equipment_serial where quote_to_equipment.deleted_flag=0 and quote_to_equipment.quote_serial ="' . $quote_serial . '"';
	debug("get the equipment: " . $sql);

// Execute the insert and check for success
	$equipment_result = mysqli_query($mysqli_link, $sql);
// Rkg if error, write out API response.
	if (mysqli_error($mysqli_link)) {
		$error = mysqli_error($mysqli_link);
		// GENIE 04/22/14 - change: echo xml to call send_output function
		$output = "<ResultInfo>
<ErrorNumber>103</ErrorNumber>
<Result>Fail</Result>
<Message>" . get_text("vcservice", "_err103a") . " " . $error . "</Message>
</ResultInfo>";
		debug("Mysql error: " . $error . "  ", $sql);
		$log_comment = "Error 240 " . $error;
		send_output($output);
		exit;
	}
	$output .= '
		<Equipments>';

	while ($equipment_row = mysqli_fetch_assoc($equipment_result)) {
		if ($equipment_row['equipment_name'] != "") {
			// RKG 10/12/25 add the day number 
			$output .= '
	<Equipment> 
		<EquipmentSerial>' . $equipment_row['equipment_serial'] . '</EquipmentSerial>
		<EquipmentName>' . $equipment_row['equipment_name'] . '</EquipmentName>
		<EquipmentCount>' . $equipment_row['quantity'] . '</EquipmentCount>
		<EquipmentDay>' . $equipment_row['day_number'] . '</EquipmentDay>
	</Equipment>';
		} // if name not empty
	}  // end of euipment loop
	$output .= '</Equipments>';

	$output .= '</Quote></Selections></ResultInfo>';

	send_output($output);
	exit;
	

?>


