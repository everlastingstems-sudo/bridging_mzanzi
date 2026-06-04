<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        UPDATE products 
        SET is_deleted = 1 
        WHERE product_id = ? AND user_id = ?
    ");

    try {
        $stmt->execute([$product_id, $user_id]);
        header("Location: seller_listings.php?msg=deleted");
        exit();
    } catch(PDOException $e) {
        header("Location: seller_listings.php?msg=error");
        exit();
    }
}

header("Location: seller_listings.php");
exit();
?>