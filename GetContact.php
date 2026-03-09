<?php
include("db_connection.php");

// JMF 03/09/26 Applied mysqli_real_escape_string to sanitize input values from request

$contactID = "";
$userID = "";

if(isset($_GET['contactID'])){
    $contactID = mysqli_real_escape_string($conn, $_GET['contactID']);
}

if(isset($_POST['contactID'])){
    $contactID = mysqli_real_escape_string($conn, $_POST['contactID']);
}

if(isset($_REQUEST['userID'])){
    $userID = mysqli_real_escape_string($conn, $_REQUEST['userID']);
}

$query = "SELECT * FROM contacts WHERE contactID = '$contactID' OR userID = '$userID'";

$result = mysqli_query($conn, $query);

$contacts = array();

while($row = mysqli_fetch_assoc($result)){
    $contacts[] = $row;
}

echo json_encode($contacts);
?>