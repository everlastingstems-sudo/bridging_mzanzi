<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_id = intval($_POST['verification_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $update = $conn->prepare("
            UPDATE seller_verification 
            SET status = 'verified'
            WHERE verification_id = ?
        ");
        try {
            $update->execute([$verification_id]);
            $message = "Seller approved!";
        } catch(PDOException $e) {
            $error = "Failed to approve seller";
        }
    } elseif ($action === 'reject') {
        $update = $conn->prepare("
            UPDATE seller_verification 
            SET status = 'rejected'
            WHERE verification_id = ?
        ");
        try {
            $update->execute([$verification_id]);
            $message = "Seller rejected!";
        } catch(PDOException $e) {
            $error = "Failed to reject seller";
        }
    }
}

$pending = $conn->prepare("
    SELECT 
        sv.verification_id,
        sv.user_id,
        u.name,
        u.email,
        sv.business_name,
        sv.business_registration_number,
        sv.phone_number,
        sv.id_document_url,
        sv.status,
        sv.created_at
    FROM seller_verification sv
    JOIN users u ON sv.user_id = u.user_id
    WHERE sv.status = 'pending'
    ORDER BY sv.created_at DESC
");
$pending->execute();
$pending_result = $pending->fetchAll(PDO::FETCH_ASSOC);

$verified = $conn->prepare("
    SELECT 
        sv.verification_id,
        u.name,
        sv.business_name,
        sv.status,
        sv.created_at
    FROM seller_verification sv
    JOIN users u ON sv.user_id = u.user_id
    WHERE sv.status = 'verified'
    ORDER BY sv.created_at DESC
    LIMIT 10
");
$verified->execute();
$verified_result = $verified->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Seller Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
        .admin-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .verification-card { border-left: 5px solid #ffc107; padding: 20px; margin-bottom: 15px; background: #fffbf0; border-radius: 8px; }
        .verification-card.verified { border-left-color: #28a745; background: #f0fff4; }
        .document-link { color: #007bff; text-decoration: underline; cursor: pointer; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <div class="admin-container">
        <h2 class="fw-bold mb-4"> Seller Verification Management</h2>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="mb-5">
            <h4 class="fw-bold mb-3"> Pending Verification (<?php echo count($pending_result); ?>)</h4>

            <?php if (count($pending_result) > 0): ?>
                <?php foreach ($pending_result as $seller): ?>
                    <div class="verification-card">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="fw-bold"><?php echo htmlspecialchars($seller['name']); ?></h6>
                                <p class="mb-1"><strong>Business:</strong> <?php echo htmlspecialchars($seller['business_name']); ?></p>
                                <p class="mb-1"><strong>Reg #:</strong> <?php echo htmlspecialchars($seller['business_registration_number']); ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($seller['phone_number']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($seller['email']); ?></p>
                                <p class="text-muted small">Submitted: <?php echo date('M d, Y H:i', strtotime($seller['created_at'])); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="<?php echo htmlspecialchars($seller['id_document_url']); ?>" target="_blank" class="btn btn-sm btn-info mb-2 w-100">
                                     View ID Document
                                </a>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="verification_id" value="<?php echo $seller['verification_id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success mb-2 w-100" onclick="return confirm('Approve this seller?')">
                                        Approve
                                    </button>
                                </form>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="verification_id" value="<?php echo $seller['verification_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-sm btn-danger w-100" onclick="return confirm('Reject this seller?')">
                                         Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No pending verifications</p>
            <?php endif; ?>
        </div>

        <hr>

        <div class="mb-5">
            <h4 class="fw-bold mb-3">Recently Verified! (<?php echo count($verified_result); ?>)</h4>

            <?php if (count($verified_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Seller Name</th>
                                <th>Business</th>
                                <th>Status</th>
                                <th>Verified Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($verified_result as $seller): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($seller['name']); ?></td>
                                    <td><?php echo htmlspecialchars($seller['business_name']); ?></td>
                                    <td><span class="badge bg-success">Verified</span></td>
                                    <td><?php echo date('M d, Y', strtotime($seller['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No verified sellers yet</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
