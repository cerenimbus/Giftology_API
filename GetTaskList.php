<?php
include("db_connection.php");

// JMF 03/09/26 Applied mysqli_real_escape_string to sanitize input values from request

$userID = "";
$status = "";

if(isset($_GET['userID'])){
    $userID = mysqli_real_escape_string($conn, $_GET['userID']);
}

if(isset($_POST['userID'])){
    $userID = mysqli_real_escape_string($conn, $_POST['userID']);
}

if(isset($_REQUEST['status'])){
    $status = mysqli_real_escape_string($conn, $_REQUEST['status']);
}

$query = "SELECT * FROM tasks WHERE userID = '$userID'";

if($status != ""){
    $query .= " AND status = '$status'";
}

$result = mysqli_query($conn, $query);

$tasks = array();

while($row = mysqli_fetch_assoc($result)){
    $tasks[] = $row;
}

echo json_encode($tasks);
?>