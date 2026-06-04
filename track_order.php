<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['id'])) {
    die("Order not found.");
}

$order_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT *
    FROM orders
    WHERE order_id = ?
");

$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Track Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container text-center my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <img src="logo.png" alt="Bridging Mzanzi Banner" class="img-fluid mb-2" style="max-height: 140px; width: auto;">
                <div class="text-uppercase fw-bold tracking-widest text-secondary small" style="letter-spacing: 2px;">Consuming at its easiest</div>
            </div>
        </div>
    </div>

<div class="card p-4">

    <p><strong>Order ID:</strong> <?php echo $order['order_id']; ?></p>

    <?php

$badge = "bg-secondary";

switch($order['order_status']) {
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

<p>
    <strong>Status:</strong>
    <span class="badge <?php echo $badge; ?>">
        <?php echo htmlspecialchars($order['order_status']); ?>
    </span>
</p>

    <p><strong>Total:</strong>
        R <?php echo number_format($order['total_amount'], 2); ?>
    </p>

    <p><strong>Date:</strong>
        <?php echo $order['created_at']; ?>
    </p>

</div>

<br>

<a href="my_orders.php" class="btn btn-primary">
    My Orders
</a>
</body>
</html>