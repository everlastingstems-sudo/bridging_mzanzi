<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: seller_listings.php");
    exit();
}

$product_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);

    $stmt = $conn->prepare("
        UPDATE products
        SET name = ?, price = ?
        WHERE product_id = ? AND user_id = ?
    ");

    try {
        $stmt->execute([$name, $price, $product_id, $user_id]);
        header("Location: seller_listings.php?msg=edited");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to update product.";
    }
}

$stmt = $conn->prepare("
    SELECT *
    FROM products
    WHERE product_id = ? 
    AND user_id = ?
    AND is_deleted = 0
");

$stmt->execute([$product_id, $user_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found or access denied.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light p-5">

<div class="container bg-white p-4 shadow rounded" style="max-width:600px;">

    <h2 class="mb-4">Edit Product</h2>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST">

        <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input
                type="text"
                name="name"
                value="<?php echo htmlspecialchars($product['name']); ?>"
                class="form-control"
                required>
        </div>

        <div class="mb-3">
            <label class="form-label">Price (R)</label>
            <input
                type="number"
                step="0.01"
                name="price"
                value="<?php echo htmlspecialchars($product['price']); ?>"
                class="form-control"
                required>
        </div>

        <div class="d-flex gap-2">
            <button
                type="submit"
                name="update"
                class="btn btn-warning">Save Changes</button>
            <a
                href="seller_listings.php"
                class="btn btn-secondary">Cancel</a>
        </div>

    </form>

</div>

</body>
</html>