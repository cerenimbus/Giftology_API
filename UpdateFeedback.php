<?php
/*****************************************************************
  Cerenimbus 

  file: UpdateFeedback.php
  description: handles feedback submissions from the giftology mobile app.
                  validates the request, checks hash integrity, and saves feedback data.

  called by: giftology mobile app

  author: karl Matthew linao – 10/19/2025
  history:
      10/19/2025  Started project
      10/28/2025  stubs
******************************************************************/

require_once('ccu_include/ccu_function.php');
require_once('send_output.php');

///// cerenimbus web service stub /////

$device_id      = $_GET['DeviceID'] ?? '';
$request_date   = $_GET['Date'] ?? '';
$hash_key       = $_GET['Key'] ?? '';
$auth_code      = $_GET['AC'] ?? '';
$name           = $_GET['Name'] ?? '';
$email          = $_GET['Email'] ?? '';
$phone          = $_GET['Phone'] ?? '';
$response_flag  = $_GET['Response'] ?? '';
$update_flag    = $_GET['Update'] ?? '';
$comment        = $_GET['Comment'] ?? '';
$mobile_version = $_GET['MobileVersion'] ?? '';
$language       = $_GET['Language'] ?? 'EN';

if ($device_id === '' || $request_date === '' || $hash_key === '' || $auth_code === '' || $comment === '') {
    send_output('Fail', 104, 'required information not supplied', '');
    exit;
}

$computed_hash = sha1($device_id . $request_date . $auth_code);
if ($computed_hash !== $hash_key) {
    send_output('Fail', 102, 'security failure - incorrect hash key', '');
    exit;
}

$query = "SELECT authorization_code FROM authorization_code WHERE authorization_code = ?";
$stmt = mysqli_prepare($db, $query);
mysqli_stmt_bind_param($stmt, 's', $auth_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    send_output('Fail', 202, 'invalid authorization code', '');
    mysqli_close($db);
    exit;
}

$insert_sql = "
    INSERT INTO feedback 
        (name, email, phone, response_flag, update_flag, comment, date_created)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
";
$stmt = mysqli_prepare($db, $insert_sql);
mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $phone, $response_flag, $update_flag, $comment);
$insert_success = mysqli_stmt_execute($stmt);

if ($insert_success) {
    send_output('Success', 0, 'feedback updated successfully', '');
} else {
    send_output('Fail', 103, 'mysql programming error', '');
}

///// end stub /////
mysqli_close($db);
?>
