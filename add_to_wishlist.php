<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_POST['product_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$action = $_POST['action']; // 'add' or 'remove'

if ($action === 'add') {
    // Add to wishlist
    $stmt = $conn->prepare("
        INSERT INTO wishlist (user_id, product_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to wishlist', 'action' => 'added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding to wishlist']);
    }

} elseif ($action === 'remove') {
    // Remove from wishlist
    $stmt = $conn->prepare("
        DELETE FROM wishlist
        WHERE user_id = ? AND product_id = ?
    ");
    
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist', 'action' => 'removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error removing from wishlist']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$stmt->close();
$conn->close();
?>
