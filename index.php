<?php
session_start();
include 'includes/db.php';

$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT p.product_id, p.user_id, p.name, p.description, p.price, p.category, p.product_condition, p.image, p.is_deleted, COALESCE(ROUND(AVG(r.rating),1),0) AS avg_rating, COUNT(r.review_id) AS total_reviews FROM products p LEFT JOIN reviews r ON p.product_id = r.product_id WHERE p.is_deleted = 0";

$params = [];

if (!empty($category)) {
    $query .= " AND p.category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " GROUP BY p.product_id, p.user_id, p.name, p.description, p.price, p.category, p.product_condition, p.image, p.is_deleted ORDER BY p.product_id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bridging Mzanzi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f0f8ff; }
.navbar { background-color: #e3f2fd; }
.product-card { border-radius: 12px; overflow: hidden; transition: transform 0.2s; }
.product-card:hover { transform: translateY(-5px); }
.category-active { background-color: #007bff !important; color: white !important; }
</style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">

<?php if (!empty($category) || !empty($search)): ?>
    <div class="alert alert-info mb-3">
        <?php if (!empty($search)): ?>
            <strong>Search Results:</strong> "<?php echo htmlspecialchars($search); ?>"
        <?php endif; ?>
        <?php if (!empty($category)): ?>
            <strong>Category:</strong> <?php echo htmlspecialchars($category); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row">

<div class="col-md-3">
    <h5 class="fw-bold mb-3">Categories</h5>
    <div class="list-group shadow-sm">
        <a href="index.php" class="list-group-item <?php echo empty($category) ? 'category-active' : ''; ?>">All Categories</a>
        <a href="index.php?category=Clothing" class="list-group-item <?php echo $category === 'Clothing' ? 'category-active' : ''; ?>">Clothing</a>
        <a href="index.php?category=Electronics" class="list-group-item <?php echo $category === 'Electronics' ? 'category-active' : ''; ?>">Electronics</a>
        <a href="index.php?category=Home Living" class="list-group-item <?php echo $category === 'Home Living' ? 'category-active' : ''; ?>">Home & Living</a>
        <a href="index.php?category=Books" class="list-group-item <?php echo $category === 'Books' ? 'category-active' : ''; ?>">Books</a>
        <a href="index.php?category=Other" class="list-group-item <?php echo $category === 'Other' ? 'category-active' : ''; ?>">Other</a>
    </div>
</div>

<div class="col-md-9">
<div class="row">

<?php
if (count($result) > 0) {
    foreach ($result as $row) {
        $product_id = $row['product_id'];
        $name = $row['name'];
        $price = $row['price'];
        $img = !empty($row['image']) ? $row['image'] : "https://via.placeholder.com/300x200";
        $avg_rating = isset($row['avg_rating']) ? $row['avg_rating'] : 0;
        $total_reviews = isset($row['total_reviews']) ? $row['total_reviews'] : 0;
?>

        <div class="col-md-4 mb-4">
            <div class="card product-card shadow-sm h-100">
                <img src="<?php echo htmlspecialchars($img); ?>" class="card-img-top" style="height:200px; object-fit:cover;">
                <div class="card-body text-center">
                    <h5 class="fw-bold"><?php echo htmlspecialchars($name); ?></h5>
                    <p class="text-success fw-bold">R <?php echo number_format($price, 2); ?></p>
                    <p class="mb-2">⭐ <?php echo number_format((float)$avg_rating, 1); ?>/5<br><small class="text-muted">(<?php echo $total_reviews; ?> reviews)</small></p>
                    <a href="product_details.php?id=<?php echo $product_id; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                </div>
            </div>
        </div>

<?php
    }
} else {
?>
    <div class="col-12 text-center mt-5">
        <h4 class="text-muted">No products found</h4>
        <p class="text-muted">Try adjusting your search or category filter</p>
    </div>
<?php
}
?>

</div>
</div>

</div>
</div>

</body>
</html>