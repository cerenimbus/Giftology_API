<?php
function send_output( $output){
    global $debugflag;
	debug("Send Output");
	// RKG 1/20/20
	global $mysqli_link; // the mysqli form requires the link value
	global $log_sql;

	// RKG 1/28/2020 added by kirby so we can put error messages in the output log
	global $log_comment;
	$log_comment = str_replace(chr(34), "'", $log_comment);


	//debug("Send output:". $log_sql);

	//debug("output before strip: ". $output);
	$output = str_replace(chr(34), "'", $output);
	//debug("output after strip: ".$output);

	// RKG 1/28/2020 added by kirby so we can put error messages in the output log
	$temp= $log_sql. ', result="'. $output. '", comment="'. $log_comment.'"';
	//debug( $log_sql);
	// Excute the insert and check for success
	debug("Final Log Entry: ". $temp);

	$result= mysqli_query($mysqli_link, $temp );

	if ( mysqli_error($mysqli_link)) {
		echo "send output web log error ". mysqli_error($mysqli_link);
		exit;
	} else {
		//debug("Web log written: ");
	}

	echo $output;
	return;

} // end function
?>