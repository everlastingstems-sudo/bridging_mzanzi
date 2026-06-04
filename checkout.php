<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("No product selected.");
}

$product_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT p.*, u.user_id as seller_id, u.name as seller_name
    FROM products p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.product_id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

$seller_id = $product['seller_id'];
$delivery_stmt = $conn->prepare("
    SELECT * FROM delivery_options
    WHERE user_id = ? AND enabled = 1
    ORDER BY cost ASC
");
$delivery_stmt->execute([$seller_id]);
$delivery_options = $delivery_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $quantity = intval($_POST['quantity']);
    $delivery_id = intval($_POST['delivery_id']);

    if ($quantity < 1) {
        $error = "Invalid quantity";
    } else {
        $del_stmt = $conn->prepare("SELECT cost FROM delivery_options WHERE delivery_id = ? AND user_id = ?");
        $del_stmt->execute([$delivery_id, $seller_id]);
        $del_result = $del_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$del_result) {
            $error = "Invalid delivery option";
        } else {
            $product_total = $product['price'] * $quantity;
            $delivery_cost = $del_result['cost'];
            $final_total = $product_total + $delivery_cost;

            $_SESSION['checkout'] = [
                'product_id' => $product_id,
                'seller_id' => $seller_id,
                'quantity' => $quantity,
                'product_price' => $product['price'],
                'product_total' => $product_total,
                'delivery_id' => $delivery_id,
                'delivery_cost' => $delivery_cost,
                'final_total' => $final_total
            ];

            header("Location: checkout_summary.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bridging Mzanzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
        .checkout-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .product-summary { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .delivery-option { border: 2px solid #e0e0e0; padding: 15px; margin-bottom: 10px; border-radius: 8px; cursor: pointer; }
        .delivery-option:hover { background: #f0f8ff; border-color: #007bff; }
        .delivery-option.selected { border-color: #28a745; background: #f0fff4; }
        .price-breakdown { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .price-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .price-row.total { font-size: 1.2rem; font-weight: bold; border-top: 2px solid #ffc107; padding-top: 10px; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="checkout-container">
        <h2 class="fw-bold mb-4">🛒 Checkout</h2>

        <div class="product-summary">
            <h5 class="fw-bold">Product Summary</h5>
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-1"><strong>Product:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
                    <p class="mb-1"><strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                    <p class="text-success fw-bold">Price: R <?php echo number_format($product['price'], 2); ?></p>
                </div>
            </div>
        </div>

        <form method="POST" id="checkoutForm">
            <div class="mb-4">
                <label class="form-label fw-bold">Quantity</label>
                <input type="number" class="form-control" name="quantity" value="1" min="1" max="100" required onchange="updatePrice()">
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">🚚 Select Delivery Method</label>
                
                <?php if (count($delivery_options) > 0): ?>
                    <?php foreach ($delivery_options as $option): ?>
                        <div class="delivery-option" onclick="selectDelivery(this, <?php echo $option['delivery_id']; ?>, <?php echo $option['cost']; ?>)">
                            <div class="form-check">
                                <input class="form-check-input delivery-radio" type="radio" name="delivery_id" value="<?php echo $option['delivery_id']; ?>" required>
                                <label class="form-check-label w-100">
                                    <strong>
                                        <?php 
                                            echo match($option['delivery_type']) {
                                                'standard' => '📦 Standard Delivery',
                                                'express' => '🚀 Express Delivery',
                                                'pickup' => '📍 Local Pickup',
                                                default => 'Delivery'
                                            };
                                        ?>
                                    </strong>
                                    <br>
                                    <small class="text-muted"><?php echo $option['estimated_days']; ?> days • <strong style="color: #28a745;">R <?php echo number_format($option['cost'], 2); ?></strong></small>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        ⚠️ This seller hasn't set up delivery options yet. Please contact the seller.
                    </div>
                    <a href="product_details.php?id=<?php echo $product_id; ?>" class="btn btn-secondary">Back</a>
                    <?php exit; ?>
                <?php endif; ?>
            </div>

            <div class="price-breakdown">
                <h5 class="fw-bold mb-3">💰 Price Breakdown</h5>
                <div class="price-row">
                    <span>Product Price (R <?php echo number_format($product['price'], 2); ?> × <span id="qty">1</span>)</span>
                    <span id="product-total">R <?php echo number_format($product['price'], 2); ?></span>
                </div>
                <div class="price-row">
                    <span>Delivery Fee</span>
                    <span id="delivery-total">Select delivery option</span>
                </div>
                <div class="price-row total">
                    <span>Total Amount to Pay</span>
                    <span id="final-total">R 0.00</span>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="product_details.php?id=<?php echo $product_id; ?>" class="btn btn-secondary">← Back</a>
                <button type="submit" name="checkout" class="btn btn-success btn-lg flex-grow-1" id="checkoutBtn" disabled>
                    💳 Proceed to Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const productPrice = <?php echo $product['price']; ?>;

function updatePrice() {
    const quantity = parseInt(document.querySelector('input[name="quantity"]').value) || 1;
    const productTotal = productPrice * quantity;
    
    document.getElementById('qty').textContent = quantity;
    document.getElementById('product-total').textContent = 'R ' + productTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    
    updateFinalTotal();
}

function selectDelivery(element, deliveryId, cost) {
    document.querySelectorAll('.delivery-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    element.querySelector('input').checked = true;
    
    updateFinalTotal();
    document.getElementById('checkoutBtn').disabled = false;
}

function updateFinalTotal() {
    const quantity = parseInt(document.querySelector('input[name="quantity"]').value) || 1;
    const productTotal = productPrice * quantity;
    
    const selectedDelivery = document.querySelector('input[name="delivery_id"]:checked');
    
    if (selectedDelivery) {
        const deliveryOption = selectedDelivery.closest('.delivery-option');
        const deliveryText = deliveryOption.querySelector('label').textContent;
        const costMatch = deliveryText.match(/R ([\d.]+)/);
        const deliveryCost = costMatch ? parseFloat(costMatch[1]) : 0;
        
        const finalTotal = productTotal + deliveryCost;
        
        document.getElementById('delivery-total').textContent = 'R ' + deliveryCost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        document.getElementById('final-total').textContent = 'R ' + finalTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
}
</script>
</body>
</html>
