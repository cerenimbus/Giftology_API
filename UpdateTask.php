<?php
/*****************************************************************
  Cerenimbus

  file:           UpdateTask.php
  description:    updates a user task record for the giftology mobile app.
                  checks hash security and verifies authorization before update.

  called by:      giftology mobile app

  author:         karl Matthew Linao
  history:
      10/19/2025  Started project
******************************************************************/

require_once('ccu_include/ccu_function.php');
require_once('send_output.php');

///// cerenimbus web service stub /////

$device_id      = $_GET['DeviceID'] ?? '';
$request_date   = $_GET['Date'] ?? '';
$hash_key       = $_GET['Key'] ?? '';
$auth_code      = $_GET['AC'] ?? '';
$task_serial    = $_GET['Task'] ?? '';
$status         = $_GET['Status'] ?? '';
$mobile_version = $_GET['MobileVersion'] ?? '';
$language       = $_GET['Language'] ?? 'EN';

if ($device_id === '' || $request_date === '' || $hash_key === '' || $auth_code === '' || $task_serial === '') {
    send_output('Fail', 104, 'required information not supplied', '');
    exit;
}

$computed_hash = sha1($device_id . $request_date . $auth_code);
if ($computed_hash !== $hash_key) {
    send_output('Fail', 102, 'security failure - incorrect hash key', '');
    exit;
}

$query = "SELECT employee_serial FROM authorization_code WHERE authorization_code = ?";
$stmt = mysqli_prepare($db, $query);
mysqli_stmt_bind_param($stmt, 's', $auth_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    send_output('Fail', 202, 'invalid authorization code', '');
    exit;
}

$update_sql = "UPDATE task SET status = ? WHERE task_serial = ?";
$stmt = mysqli_prepare($db, $update_sql);
mysqli_stmt_bind_param($stmt, 'si', $status, $task_serial);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    send_output('Success', 0, 'task updated successfully', '');
} else {
    send_output('Fail', 103, 'mysql programming error', '');
}

///// end stub /////
mysqli_close($db);
?>
