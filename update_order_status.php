<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['order_id']) || !isset($_POST['order_status'])) {
    die("Invalid request.");
}

$order_id = intval($_POST['order_id']);
$order_status = $_POST['order_status'];

$allowed = ['Ordered','Processing','Shipped','Delivered','Cancelled'];

if (!in_array($order_status, $allowed)) {
    die("Invalid status.");
}

$stmt = $conn->prepare("
    UPDATE orders
    SET order_status = ?
    WHERE order_id = ?
");

try {
    $stmt->execute([$order_status, $order_id]);
    header("Location: seller_orders.php");
    exit();
} catch(PDOException $e) {
    die("Update failed: " . $e->getMessage());
}
?>