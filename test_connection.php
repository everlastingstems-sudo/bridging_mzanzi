<?php
$servername = "localhost";
$username = "if0_42096522";
$password = "Letlhogonolo24";
$dbname = "if0_42096522_bridging_mzanzi"; // Update with your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connection successful!";
}
?>