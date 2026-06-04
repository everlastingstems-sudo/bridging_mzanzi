<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

$stmt = $conn->prepare("
    SELECT * FROM delivery_options 
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$delivery_options_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_option') {
        $delivery_type = $_POST['delivery_type'];
        $estimated_days = intval($_POST['estimated_days']);
        $cost = floatval($_POST['cost']);

        $insert = $conn->prepare("
            INSERT INTO delivery_options (user_id, delivery_type, estimated_days, cost)
            VALUES (?, ?, ?, ?)
        ");
        try {
            $insert->execute([$user_id, $delivery_type, $estimated_days, $cost]);
            $message = "Delivery option added!";
            $stmt->execute([$user_id]);
            $delivery_options_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $message = "Error adding option";
        }
    }

    if ($action === 'toggle_option') {
        $delivery_id = intval($_POST['delivery_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status === 1 ? 0 : 1;

        $update = $conn->prepare("
            UPDATE delivery_options 
            SET enabled = ?
            WHERE delivery_id = ? AND user_id = ?
        ");
        try {
            $update->execute([$new_status, $delivery_id, $user_id]);
            $message = $new_status === 1 ? "Enabled!" : "Disabled!";
            $stmt->execute([$user_id]);
            $delivery_options_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $message = "Error toggling option";
        }
    }

    if ($action === 'delete_option') {
        $delivery_id = intval($_POST['delivery_id']);
        $delete = $conn->prepare("
            DELETE FROM delivery_options 
            WHERE delivery_id = ? AND user_id = ?
        ");
        try {
            $delete->execute([$delivery_id, $user_id]);
            $message = "Delivery option deleted!";
            $stmt->execute([$user_id]);
            $delivery_options_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $message = "Error deleting option";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Options</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
        .container-box { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .option-card { border: 2px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .option-card.disabled { opacity: 0.6; background: #f5f5f5; }
        .delivery-icon { font-size: 2rem; margin-right: 15px; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="container-box">
        <h2 class="fw-bold mb-4"> Delivery Options</h2>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4 p-4 bg-light">
            <h5 class="fw-bold mb-3">➕ Add Delivery Method</h5>
            <form method="POST">
                <input type="hidden" name="action" value="add_option">
                
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Delivery Type</label>
                        <select class="form-select" name="delivery_type" required>
                            <option value="">Select type...</option>
                            <option value="standard">📦 Standard (5-7 days)</option>
                            <option value="express">🚀 Express (2-3 days)</option>
                            <option value="pickup">📍 Local Pickup</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Estimated Days</label>
                        <input type="number" class="form-control" name="estimated_days" min="1" max="30" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Delivery Cost (R)</label>
                        <input type="number" class="form-control" name="cost" step="0.01" min="0" required>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            Add Option
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <h5 class="fw-bold mt-4 mb-3">Your Delivery Methods</h5>

        <?php if (count($delivery_options_list) > 0): ?>
            <?php foreach ($delivery_options_list as $option): ?>
                <div class="option-card <?php echo $option['enabled'] === 0 ? 'disabled' : ''; ?>">
                    <div class="d-flex align-items-center flex-grow-1">
                        <span class="delivery-icon">
                            <?php 
                                echo match($option['delivery_type']) {
                                    'standard' => '📦',
                                    'express' => '🚀',
                                    'pickup' => '📍',
                                    default => '🚚'
                                };
                            ?>
                        </span>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1">
                                <?php 
                                    echo match($option['delivery_type']) {
                                        'standard' => 'Standard Delivery',
                                        'express' => 'Express Delivery',
                                        'pickup' => 'Local Pickup',
                                        default => 'Delivery'
                                    };
                                ?>
                            </h6>
                            <p class="mb-0 text-muted">
                                <strong><?php echo $option['estimated_days']; ?> days</strong> • 
                                Cost: <strong>R <?php echo number_format($option['cost'], 2); ?></strong>
                            </p>
                        </div>
                    </div>

                    <div class="btn-group ms-3" role="group">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_option">
                            <input type="hidden" name="delivery_id" value="<?php echo $option['delivery_id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $option['enabled']; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $option['enabled'] === 1 ? 'btn-warning' : 'btn-success'; ?>">
                                <?php echo $option['enabled'] === 1 ? 'Disable' : ' Enable'; ?>
                            </button>
                        </form>

                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_option">
                            <input type="hidden" name="delivery_id" value="<?php echo $option['delivery_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this option?')">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No delivery options added yet. Add one above to get started!</p>
        <?php endif; ?>

        <hr class="my-4">

        <div class="text-center">
            <a href="seller_listings.php" class="btn btn-outline-secondary">
                Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
