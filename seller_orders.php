<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$seller_id = $_SESSION['user_id'];

$check = $conn->prepare("
    SELECT COUNT(*) as count
    FROM products 
    WHERE user_id = ? 
    AND is_deleted = 0
");
$check->execute([$seller_id]);
$check_result = $check->fetch(PDO::FETCH_ASSOC);
$has_products = $check_result['count'] > 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Bridging Mzanzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f8ff;
        }
        .navbar {
            background-color: #e3f2fd;
        }
        .order-card {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <h2 class="fw-bold mb-4"> Seller Dashboard</h2>

    <?php if (!$has_products): ?>

        <div class="alert alert-warning text-center">
            <h5>You currently have no products listed</h5>
            <p class="mb-2">You can only view customer orders once you start selling.</p>
        </div>

        <div class="text-center">
            <a href="add_product.php" class="btn btn-primary btn-lg">
                + Start Selling
            </a>
        </div>

    <?php else: ?>

        <?php
        $sql = "
        SELECT 
            o.order_id,
            o.full_name,
            o.phone_number,
            o.shipping_address,
            o.city,
            o.order_status,
            o.created_at,
            p.product_id,
            p.name AS product_name,
            p.price,
            oi.quantity,
            oi.order_item_id
        FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE p.user_id = ?
        ORDER BY o.created_at DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$seller_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="row">
            <div class="col-md-12">

                <?php if (count($result) > 0): ?>

                    <div class="table-responsive">
                        <table class="table table-hover bg-white rounded shadow-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php foreach($result as $row): ?>

                                <tr>
                                    <td><strong>#<?php echo $row['order_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td><strong>R <?php echo number_format($row['price'] * $row['quantity'], 2); ?></strong></td>

                                    <td>
                                        <?php
                                        $badge = "bg-secondary";

                                        switch($row['order_status']) {
                                            case 'Ordered':
                                                $badge = "bg-warning text-dark";
                                                break;
                                            case 'Processing':
                                                $badge = "bg-info text-dark";
                                                break;
                                            case 'Shipped':
                                                $badge = "bg-primary";
                                                break;
                                            case 'Delivered':
                                                $badge = "bg-success";
                                                break;
                                            case 'Cancelled':
                                                $badge = "bg-danger";
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge; ?>">
                                            <?php echo htmlspecialchars($row['order_status']); ?>
                                        </span>
                                    </td>

                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>

                                    <td>
                                        <form action="update_order_status.php" method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                            <select name="order_status" class="form-select form-select-sm">
                                                <option value="Ordered" <?php echo $row['order_status'] === 'Ordered' ? 'selected' : ''; ?>>Ordered</option>
                                                <option value="Processing" <?php echo $row['order_status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Shipped" <?php echo $row['order_status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="Delivered" <?php echo $row['order_status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="Cancelled" <?php echo $row['order_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                Update
                                            </button>
                                        </form>
                                    </td>

                                </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                <?php else: ?>

                    <div class="alert alert-info text-center">
                        <h5> No orders yet</h5>
                        <p>Orders will appear here when customers purchase your products.</p>
                    </div>

                <?php endif; ?>

            </div>
        </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>