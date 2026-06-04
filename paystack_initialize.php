<?php
session_start();
include 'includes/db.php';
include 'paystack_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$product_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found");
}

$_SESSION['pending_product_id'] = $product_id;

$email = $_SESSION['email'] ?? 'customer@example.com';
$amount = $product['price'] * 100;

$callback_url = "http://localhost/bridging_mzanzi/paystack_verify.php";

$data = [
    "email" => $email,
    "amount" => $amount,
    "callback_url" => $callback_url
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
        "Cache-Control: no-cache",
    ],
]);

$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);

if ($result['status']) {
    header("Location: " . $result['data']['authorization_url']);
    exit();
} else {
    die("Payment initialization failed: " . ($result['message'] ?? 'Unknown error'));
}
?>