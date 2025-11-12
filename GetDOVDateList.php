<?php
/*****************************************************************
  Cerenimbus

  file:           GetDovDateList
  description:    retrieves a list of dov dates for the giftology mobile app.
                  validates security hash and authorization before fetching data.

  called by:      giftology mobile app

  author:         Karl Matthew Linao– 10/19/2025
  history:
      10/19/2025  start
      10/28/2025  stubs
******************************************************************/

require_once('ccu_include/ccu_function.php');
require_once('send_output.php');

///// cerenimbus web service stub /////

$device_id      = $_GET['DeviceID'] ?? '';
$request_date   = $_GET['Date'] ?? '';
$hash_key       = $_GET['Key'] ?? '';
$auth_code      = $_GET['AC'] ?? '';
$mobile_version = $_GET['MobileVersion'] ?? '';
$language       = $_GET['Language'] ?? 'EN';

if ($device_id === '' || $request_date === '' || $hash_key === '' || $auth_code === '') {
    send_output('Fail', 104, 'required information not supplied', '');
    exit;
}

$computed_hash = sha1($device_id . $request_date . $auth_code);
if ($computed_hash !== $hash_key) {
    send_output('Fail', 102, 'security failure - incorrect hash key', '');
    exit;
}

$query = "SELECT subscriber_serial FROM authorization_code WHERE authorization_code = ?";
$stmt = mysqli_prepare($db, $query);
mysqli_stmt_bind_param($stmt, 's', $auth_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    send_output('Fail', 202, 'invalid authorization code', '');
    exit;
}

$row = mysqli_fetch_assoc($result);
$subscriber_serial = $row['subscriber_serial'];

$sql = "SELECT dov_name AS Name, COUNT(*) AS Count 
        FROM dov_date 
        WHERE subscriber_serial = ?
        GROUP BY dov_name
        ORDER BY dov_name ASC";
$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, 'i', $subscriber_serial);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$xml = '<Selections>';
while ($row = mysqli_fetch_assoc($result)) {
    $xml .= '<DOV>';
    $xml .= '<Name>' . htmlspecialchars($row['Name']) . '</Name>';
    $xml .= '<Count>' . htmlspecialchars($row['Count']) . '</Count>';
    $xml .= '</DOV>';
}
$xml .= '</Selections>';

send_output('Success', 0, 'dov date list found', $xml);

///// end stub /////
mysqli_close($db);
?>
