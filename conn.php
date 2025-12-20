<?php
// Deklarace php proměnných
$servername = "localhost";
$username = "zlatnjir";
$password = "webove aplikace";
$database = "zlatnjir";


//MYSQLI - MYSQL Improved
$conn = new mysqli($servername, $username, $password, $database);

//Ověření spojení
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>