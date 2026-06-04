<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$user_stmt = $conn->prepare("SELECT name, phone FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

$profile_name = $user_data['name'] ?? 'Independent Vendor';
$profile_phone = $user_data['phone'] ?? '082 xxx xxxx';

$verify_stmt = $conn->prepare("SELECT status FROM seller_verification WHERE user_id = ?");
$verify_stmt->execute([$user_id]);
$verification = $verify_stmt->fetch(PDO::FETCH_ASSOC);
$is_verified = $verification && $verification['status'] === 'verified';

$prod_stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? AND is_deleted = 0 ORDER BY product_id DESC");
$prod_stmt->execute([$user_id]);
$products_list = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

$orders_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_orders
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE p.user_id = ?
");
$orders_stmt->execute([$user_id]);
$orders_data = $orders_stmt->fetch(PDO::FETCH_ASSOC);
$total_orders = $orders_data['total_orders'] ?? 0;

$revenue_stmt = $conn->prepare("
    SELECT COALESCE(SUM(o.total_amount), 0) AS total_revenue
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE p.user_id = ? AND o.order_status = 'Delivered'
");
$revenue_stmt->execute([$user_id]);
$revenue_data = $revenue_stmt->fetch(PDO::FETCH_ASSOC);
$total_revenue = $revenue_data['total_revenue'] ?? 0;

$pending_stmt = $conn->prepare("
    SELECT COUNT(*) AS pending_orders
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE p.user_id = ? AND o.order_status IN ('Ordered', 'Processing')
");
$pending_stmt->execute([$user_id]);
$pending_data = $pending_stmt->fetch(PDO::FETCH_ASSOC);
$pending_orders = $pending_data['pending_orders'] ?? 0;

$top_products_stmt = $conn->prepare("
    SELECT 
        p.product_id,
        p.name,
        p.price,
        COUNT(oi.order_item_id) AS units_sold,
        SUM(o.total_amount) AS product_revenue
    FROM products p
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id
    WHERE p.user_id = ? AND p.is_deleted = 0
    GROUP BY p.product_id, p.name, p.price
    ORDER BY units_sold DESC
    LIMIT 5
");
$top_products_stmt->execute([$user_id]);
$top_products_list = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #e3f2fd; }
        .dashboard-container { background: #f0f8ff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); min-height: 80vh; }
        .product-card-sm { background: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        .avatar-circle { width: 80px; height: 80px; background-color: #cfd8dc; border-radius: 50%; object-fit: cover; }
        .btn-stack .btn { font-size: 0.75rem; font-weight: bold; text-transform: uppercase; border-radius: 6px; margin-bottom: 5px; padding: 5px 10px; display: block; }
        .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 15px; }
        .stat-box.success { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-box.warning { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-box.info { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-number { font-size: 2.5rem; font-weight: bold; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; margin-top: 5px; }
        .top-products-table { background: white; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        .verification-banner { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .verification-banner.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .verification-banner.verified { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .seller-actions { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
        .seller-actions .btn { flex: 1; min-width: 150px; }
    </style>
</head>
<script>
setTimeout(function() {
    let alertBox = document.querySelector('.alert');
    if(alertBox){
        alertBox.classList.remove('show');
        alertBox.style.display = 'none';
    }
}, 3000);
</script>
<body>

<?php include 'includes/navbar.php'; ?>

<?php if (isset($_GET['msg'])): ?>

<div class="container mt-3">

    <?php if ($_GET['msg'] == 'edited'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Product successfully updated!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            Product successfully deleted!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($_GET['msg'] == 'error'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
             Something went wrong. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

</div>

<?php endif; ?>

<div class="container my-4">
    <div class="p-4 p-md-5 dashboard-container">
        
        <?php if ($is_verified): ?>
            <div class="verification-banner verified">
                <h5 class="mb-2">You are a Verified Seller!</h5>
                <p class="mb-0">Your business has been verified. Display your verified badge proudly!</p>
            </div>
        <?php elseif ($verification): ?>
            <div class="verification-banner pending">
                <h5 class="mb-2">Verification Pending</h5>
                <p class="mb-0">Your documents are under review. This usually takes 24-48 hours.</p>
            </div>
        <?php else: ?>
            <div class="verification-banner">
                <h5 class="mb-2">Get Verified</h5>
                <p class="mb-3">Become a verified seller to increase customer trust and sales.</p>
                <a href="seller_verification.php" class="btn btn-light">Start Verification Process</a>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-5">
                <h3>My Profile</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($profile_name); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile_phone); ?></p>
                <?php if ($is_verified): ?>
                    <span class="badge bg-success" style="font-size: 1rem;">✅ Verified Seller</span>
                <?php endif; ?>
            </div>

            <div class="col-md-7">
                <h3 class="mb-3">Analytics</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="stat-box success">
                            <div class="stat-number"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-box info">
                            <div class="stat-number">R <?php echo number_format($total_revenue, 0); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-box warning">
                            <div class="stat-number"><?php echo $pending_orders; ?></div>
                            <div class="stat-label">Pending Orders</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo count($products_list); ?></div>
                            <div class="stat-label">Active Products</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="seller-actions mb-4">
            <a href="delivery_options.php" class="btn btn-outline-primary">
                 Manage Delivery Options
            </a>
            <?php if (!$is_verified && !$verification): ?>
                <a href="seller_verification.php" class="btn btn-outline-success">
                    Get Verified
                </a>
            <?php endif; ?>
        </div>

        <?php if (count($top_products_list) > 0): ?>
            <div class="top-products-table">
                <h4 class="fw-bold mb-3">🏆 Top Selling Products</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_products_list as $top): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($top['name']); ?></td>
                                    <td>R <?php echo number_format($top['price'], 2); ?></td>
                                    <td><strong><?php echo $top['units_sold'] ?? 0; ?></strong></td>
                                    <td>R <?php echo number_format($top['product_revenue'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-5">
            <div class="d-flex justify-content-between mb-4">
                <h3>My Products</h3>
                <a href="add_product.php" class="btn btn-primary">Add New Product</a>
            </div>

            <div class="row g-3">
                <?php if (count($products_list) > 0): ?>
                    <?php foreach($products_list as $row): ?>
                        <div class="col-6 col-sm-4">
                            <div class="card product-card-sm p-3 h-100">
                                <h6 class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></h6>
                                <p class="text-success fw-bold">R <?php echo number_format($row['price'], 2); ?></p>
                                
                                <div class="btn-stack mt-auto">
                                    <a href="product_details.php?id=<?php echo $row['product_id']; ?>" class="btn btn-info text-white">View</a>
                                    <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" class="btn btn-warning text-white">Edit</a>
                                    <a href="delete_product.php?id=<?php echo $row['product_id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this product?');" 
                                       class="btn btn-danger text-white">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No products listed under your account yet.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
