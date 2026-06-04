<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "sql107.infinityfree.com";
$username = "if0_42096522";
$password = "Letlhogonolo24";
$dbname = "if0_42096522_bridging_mzanzi";

try {
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>