<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    try {
        $stmt->execute([$new_status, $order_id]);
        header("Location: admin_orders.php?msg=Status+updated");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to update status";
    }
}

$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT o.order_id, o.created_at, o.order_status, o.total_amount, u.name AS buyer_name 
        FROM orders o
        JOIN users u ON o.buyer_id = u.user_id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$limit, $offset]);
$orders_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_data = $total_stmt->fetch(PDO::FETCH_ASSOC);
$total_orders = $total_data['total'] ?? 0;
$total_pages = ceil($total_orders / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Orders - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #f0f8ff; } .panel-card { background: white; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }</style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container-fluid px-4 mt-4">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            <div class="col-md-9">
                <div class="p-4 panel-card">
                    <h1 class="display-6 fw-bold text-dark">Order Management</h1>
                    <hr>
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                    <?php endif; ?>

                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th><th>Buyer</th><th>Price</th><th>Date</th><th>Status</th><th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($orders_result) > 0): 
                                foreach($orders_result as $row): ?>
                            <tr>
                                <td><strong>#<?php echo $row['order_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                                <td class="text-success fw-bold">R <?php echo number_format($row['total_amount'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form action="admin_orders.php" method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="Ordered" <?php if($row['order_status']=='Ordered') echo 'selected'; ?>>Ordered</option>
                                            <option value="Shipped" <?php if($row['order_status']=='Shipped') echo 'selected'; ?>>Shipped</option>
                                            <option value="Completed" <?php if($row['order_status']=='Completed') echo 'selected'; ?>>Completed</option>
                                        </select>
                                </td>
                                <td class="text-center">
                                        <button type="submit" name="update_status" class="btn btn-sm btn-outline-dark">Update</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="6" class="text-center">No orders found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>