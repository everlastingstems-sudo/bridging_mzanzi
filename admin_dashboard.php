<?php
session_start();
include 'includes/db.php';

// Role-Based Access Control (RBAC) Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch dynamic statistics numbers from database tables
$user_count_result = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $user_count_result->fetch_assoc()['total'];

$product_count_result = $conn->query("SELECT COUNT(*) as total FROM products");
$total_products = $product_count_result->fetch_assoc()['total'];

// Fallback count if you haven't built an orders table yet
$total_orders = 0;
$table_check = $conn->query("SHOW TABLES LIKE 'orders'");
if($table_check->num_rows > 0) {
    $order_count_result = $conn->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $order_count_result->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bridging Mzanzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
        .stat-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid px-4 mt-4">
        <div class="row">
            
            <?php include 'includes/admin_sidebar.php'; ?>

            <div class="col-md-9">
                <div class="p-4 bg-white shadow-sm mb-4" style="border-radius: 20px;">
                    <h1 class="display-6 fw-bold text-dark mb-1">Dashboard</h1>
                    <p class="text-muted">Real-time platform summaries Overview</p>
                    <hr>

                    <div class="row g-4 mt-2">
                        <div class="col-md-4">
                            <div class="card stat-card p-4 text-center">
                                <h5 class="text-muted mb-2 fw-semibold">Total Users</h5>
                                <h2 class="display-5 fw-bold text-primary"><?php echo $total_users; ?></h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card p-4 text-center">
                                <h5 class="text-muted mb-2 fw-semibold">Total Products</h5>
                                <h2 class="display-5 fw-bold text-success"><?php echo $total_products; ?></h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card p-4 text-center">
                                <h5 class="text-muted mb-2 fw-semibold">Total Orders</h5>
                                <h2 class="display-5 fw-bold text-warning"><?php echo $total_orders; ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-4 bg-light rounded-3 border-0">
                        <h5 class="fw-bold text-secondary mb-3">Recent System Updates</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 text-muted">🔹 Admin dashboard successfully linked to active system counters.</li>
                            <li class="text-muted">🔹 Role Validation framework online.</li>
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>