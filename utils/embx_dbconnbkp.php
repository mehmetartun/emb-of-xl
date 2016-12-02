<?php

$servername = "127.0.0.1";
$username = "root";
$password = "embonds";

// Create connection
$conn = mysql_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} 
//echo "Connected successfully";

mysql_select_db('embonds',$conn);
?>

