<?php
#$servername = "193.203.184.93"; // Database host

$servername = "localhost";
$username = "u807410800_capstoneappeco"; // Database username
$password = "#@Tinauto500"; // Database password
$database = "u807410800_capstoneapp"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>");
} else {
   # echo "<p style='color: green;'>Database connected successfully!</p>";
}
?>
