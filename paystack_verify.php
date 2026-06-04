<?php
session_start();
include 'includes/db.php';
include 'paystack_config.php';

if (!isset($_GET['reference'])) {
    die("No payment reference");
}

$reference = $_GET['reference'];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
    ],
]);

$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);

if (!$result['status']) {
    die("Payment verification failed");
}

$data = $result['data'];

if ($data['status'] == 'success') {
    $user_id = $_SESSION['user_id'] ?? null;
    $product_id = $_SESSION['pending_product_id'] ?? null;

    if (!$user_id || !$product_id) {
        die("Missing user or product ID");
    }

    try {
        $user_stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
        $user_stmt->execute([$user_id]);
        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        $full_name = $user_data['name'] ?? 'Unknown';

        $product_stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            die("Product not found");
        }

        $amount = $product['price'];
        $delivery_fee = 50;

        $stmt = $conn->prepare("
            INSERT INTO orders
            (buyer_id, product_id, full_name, phone_number, shipping_address, city, subtotal, delivery_fee, total_amount, payment_method, order_status)
            VALUES (?, ?, ?, '', '', '', ?, ?, ?, 'Paystack', 'Processing')
        ");

        $total_amount = $amount + $delivery_fee;
        $stmt->execute([$user_id, $product_id, $full_name, $amount, $delivery_fee, $total_amount]);
        $order_id = $conn->lastInsertId();

        $item_stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity)
            VALUES (?, ?, 1)
        ");
        $item_stmt->execute([$order_id, $product_id]);

        unset($_SESSION['pending_product_id']);

        header("Location: my_orders.php?msg=success");
        exit();
    } catch(PDOException $e) {
        error_log("Payment processing error: " . $e->getMessage());
        die("Failed to process order. Please contact support with reference: " . htmlspecialchars($reference));
    }
}

die("Payment not successful");
?>
