<?php



	$mysql_username = "root";
	$mysql_password = "embonds";
	$mysql_database = "embonds";
	$mysql_server = "127.0.0.1";



// Create connection
$conn = mysqli_connect($mysql_server, $mysql_username, $mysql_password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} 
//echo "Connected successfully";

mysqli_select_db($conn,$mysql_database);
?>

