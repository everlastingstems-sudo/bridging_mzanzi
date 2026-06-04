<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=Please+login+to+add+a+product");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    $product_condition = $_POST['product_condition'];
    $user_id = $_SESSION['user_id'];

    $image_path = 'uploads/default.jpg';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    if (!empty($name) && $price > 0) {
        $stmt = $conn->prepare("INSERT INTO products (user_id, name, description, price, category, product_condition, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([$user_id, $name, $description, $price, $category, $product_condition, $image_path]);
            $success_msg = "Marketplace listing posted successfully!";
        } catch(PDOException $e) {
            $error_msg = "Database error: Could not save the product.";
        }
    } else {
        $error_msg = "Please provide valid product details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List an Item - Bridging Mzanzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #f0f8ff; }.form-card { background: white; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }</style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="p-5 form-card">
                    <h2 class="fw-bold text-dark">List a New Product</h2>
                    <?php if (isset($success_msg)): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
                    <?php if (isset($error_msg)): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>
                    
                    <form action="add_product.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (R)</label>
                                <input type="number" step="0.01" name="price" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="Electronics">Electronics</option>
                                    <option value="Clothing">Clothing</option>
                                    <option value="Home Living">Home Living</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item Condition</label>
                            <select name="product_condition" class="form-select">
                                <option value="New">New</option>
                                <option value="Like New">Like New</option>
                                <option value="Fair">Fair</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="product_image" class="form-control" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Publish Listing</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>