<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$buyer_id = $_SESSION['user_id'];

$sql = "SELECT 
            o.order_id,
            o.order_status,
            o.created_at,
            p.name AS product_name,
            oi.quantity,
            p.price
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.buyer_id = ?
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$buyer_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container text-center my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <img src="uploads/logo.png" alt="Bridging Mzanzi Banner" class="img-fluid mb-2" style="max-height: 140px; width: auto;">
                <div class="text-uppercase fw-bold tracking-widest text-secondary small" style="letter-spacing: 2px;">Consuming at its easiest</div>
            </div>
        </div>
    </div>
    <div class="container">
    <h2 class="mb-4">My Orders</h2>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td>R <?php echo number_format($row['price'] * $row['quantity'], 2); ?></td>
                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                <?php

$badge = "bg-secondary";

switch($row['order_status']) {
    case 'Ordered':
        $badge = "bg-secondary";
        break;
    case 'Processing':
        $badge = "bg-warning text-dark";
        break;
    case 'Shipped':
        $badge = "bg-info text-dark";
        break;
    case 'Delivered':
        $badge = "bg-success";
        break;
    case 'Cancelled':
        $badge = "bg-danger";
        break;
}

?>

<td>
    <span class="badge <?php echo $badge; ?>">
        <?php echo htmlspecialchars($row['order_status']); ?>
    </span>
</td>
                
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
