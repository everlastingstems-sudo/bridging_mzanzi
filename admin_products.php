<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    
    try {
        $stmt->execute([$delete_id]);
        header("Location: admin_products.php?msg=Product+removed+successfully");
        exit();
    } catch(PDOException $e) {
        $error_msg = "Error removing item from the database.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
        .panel-card { background: white; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid px-4 mt-4">
        <div class="row">
            
            <?php include 'includes/admin_sidebar.php'; ?>

            <div class="col-md-9">
                <div class="p-4 panel-card mb-4">
                    <h1 class="display-6 fw-bold text-dark mb-1">Product Management</h1>
                    <p class="text-muted">Monitor marketplace items, filter inventory, or moderate listings</p>
                    <hr>
                    
                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                    <?php endif; ?>

                    <div class="table-responsive mt-3">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Condition</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $products_query = $conn->query("SELECT * FROM products");
                                $products = $products_query->fetchAll(PDO::FETCH_ASSOC);
                                if (count($products) > 0):
                                    foreach($products as $row):
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $row['product_id']; ?></strong></td>
                                    <td>
                                        <span class="fw-semibold text-primary"><?php echo htmlspecialchars($row['name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['category'] ?? 'General'); ?></td>
                                    <td class="text-success fw-bold">R <?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-secondary rounded-pill px-3">
                                            <?php echo htmlspecialchars($row['product_condition'] ?? 'New'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="admin_products.php?delete_id=<?php echo $row['product_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                           onclick="return confirm('Are you sure you want to remove this item entry permanently from the platform?')">
                                           Remove Listing
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    endforeach;
                                else: 
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No active marketplace items found in the database.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>