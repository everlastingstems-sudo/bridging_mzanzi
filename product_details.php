<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['id'])) {
    die("No product selected.");
}

$product_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT p.*, u.name AS seller_name, u.user_id
    FROM products p 
    LEFT JOIN users u ON p.user_id = u.user_id 
    WHERE p.product_id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

$seller_id = $product['user_id'];
$verify_check = $conn->prepare("SELECT status FROM seller_verification WHERE user_id = ?");
$verify_check->execute([$seller_id]);
$verify_result = $verify_check->fetch(PDO::FETCH_ASSOC);
$is_seller_verified = $verify_result && $verify_result['status'] === 'verified';

$ratings_stmt = $conn->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_ratings
    FROM seller_ratings
    WHERE seller_id = ?
");
$ratings_stmt->execute([$seller_id]);
$ratings_data = $ratings_stmt->fetch(PDO::FETCH_ASSOC);
$seller_avg_rating = $ratings_data['avg_rating'] ? round($ratings_data['avg_rating'], 1) : 0;
$seller_total_ratings = $ratings_data['total_ratings'] ?? 0;

$can_review = false;
$already_reviewed = false;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $check_purchase = $conn->prepare("
        SELECT oi.order_item_id
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE oi.product_id = ?
        AND o.buyer_id = ?
        AND o.order_status = 'Delivered'
        LIMIT 1
    ");
    $check_purchase->execute([$product_id, $user_id]);

    if ($check_purchase->fetch(PDO::FETCH_ASSOC)) {
        $can_review = true;
    }

    $check_review = $conn->prepare("
        SELECT review_id
        FROM reviews
        WHERE product_id = ?
        AND user_id = ?
        LIMIT 1
    ");
    $check_review->execute([$product_id, $user_id]);

    if ($check_review->fetch(PDO::FETCH_ASSOC)) {
        $already_reviewed = true;
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SESSION['user_id']) &&
    $can_review &&
    !$already_reviewed
) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    if ($rating >= 1 && $rating <= 5) {
        $insert = $conn->prepare("
            INSERT INTO reviews (product_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?)
        ");
        
        try {
            $insert->execute([$product_id, $user_id, $rating, $comment]);
            header("Location: product_details.php?id=" . $product_id);
            exit();
        } catch(PDOException $e) {
            // Review insertion error
        }
    }
}

$reviews_stmt = $conn->prepare("
    SELECT r.*, u.name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

$avg_stmt = $conn->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
    FROM reviews
    WHERE product_id = ?
");
$avg_stmt->execute([$product_id]);
$data = $avg_stmt->fetch(PDO::FETCH_ASSOC);

$average_rating = round($data['avg_rating'] ?? 0, 1);
$total_reviews = $data['total_reviews'] ?? 0;

$item_image = !empty($product['image']) 
    ? $product['image'] 
    : 'https://via.placeholder.com/350';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Bridging Mzanzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f8ff;
        }
        .navbar {
            background-color: #e3f2fd;
        }
        .product-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
        }
        .review-card {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .seller-info-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            background: #fafafa;
        }
        .seller-badge {
            display: inline-block;
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-5">
            <img src="<?php echo htmlspecialchars($item_image); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
            
            <div class="seller-info-card mt-4">
                <h6 class="fw-bold mb-3">Seller Information</h6>
                <div class="d-flex align-items-center mb-3">
                    <div>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($product['seller_name'] ?? 'Unknown'); ?></strong></p>
                        <?php if ($is_seller_verified): ?>
                            <span class="seller-badge">✅ Verified Seller</span>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="mb-1">
                    <strong>Seller Rating:</strong> 
                    ⭐ <?php echo $seller_avg_rating; ?>/5 
                    <span class="text-muted">(<?php echo $seller_total_ratings; ?> ratings)</span>
                </p>
            </div>
        </div>

        <div class="col-md-7">
            <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($product['name']); ?></h2>

            <div class="mb-3">
                <span class="fs-5">⭐ <?php echo $average_rating; ?>/5</span>
                <span class="text-muted">(<?php echo $total_reviews; ?> reviews)</span>
            </div>

            <h3 class="text-success fw-bold mb-4">R <?php echo number_format($product['price'], 2); ?></h3>

            <div class="mb-4">
                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                <p><strong>Condition:</strong> <?php echo htmlspecialchars($product['product_condition']); ?></p>
            </div>

            <div class="mb-4">
                <h5 class="fw-bold">Description</h5>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
            </div>

            <div class="mb-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="paystack_initialize.php?id=<?php echo $product_id; ?>" class="btn btn-success btn-lg">
                        🛒 Buy Now
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-lg">
                        Login to Buy
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-8">
            <h4 class="fw-bold mb-4">Customer Reviews</h4>

            <?php if ($can_review && !$already_reviewed): ?>
                <div class="card mb-4 p-4">
                    <h5 class="fw-bold mb-3">Leave a Review</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Rating</label>
                            <select class="form-select" name="rating" required>
                                <option value="">Select rating...</option>
                                <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                <option value="4">⭐⭐⭐⭐ Good</option>
                                <option value="3">⭐⭐⭐ Average</option>
                                <option value="2">⭐⭐ Poor</option>
                                <option value="1">⭐ Very Poor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Comment</label>
                            <textarea class="form-control" name="comment" rows="4" placeholder="Share your experience..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </form>
                </div>
            <?php elseif ($already_reviewed): ?>
                <div class="alert alert-info mb-4">
                    ✓ You have already reviewed this product
                </div>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <div class="alert alert-warning mb-4">
                    <a href="login.php">Login</a> to leave a review
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-4">
                    You must purchase and receive this product to leave a review
                </div>
            <?php endif; ?>

            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($review['name']); ?></h6>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                        </div>
                        <div class="mb-2">
                            ⭐ <?php echo $review['rating']; ?>/5
                        </div>
                        <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No reviews yet. Be the first to review!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
