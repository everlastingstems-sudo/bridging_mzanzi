<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

$check_stmt = $conn->prepare("SELECT status, phone_verified, phone_number, otp_code FROM seller_verification WHERE user_id = ?");
$check_stmt->execute([$user_id]);
$verification = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    $step = $_POST['step'];

    if ($step === 'submit_docs') {
        $business_name = trim($_POST['business_name']);
        $business_reg = trim($_POST['business_registration']);
        $phone = trim($_POST['phone']);

        if (empty($business_name) || empty($business_reg) || empty($phone)) {
            $error = "All fields are required";
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error = "Phone number must be 10 digits";
        } elseif (!isset($_FILES['id_document']) || $_FILES['id_document']['error'] !== 0) {
            $error = "ID document upload failed";
        } else {
            $upload_dir = 'uploads/seller_documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file = $_FILES['id_document'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024;

            if (!in_array($file_ext, $allowed_types)) {
                $error = "Invalid file type. Allowed: PDF, JPG, PNG, GIF";
            } elseif ($file['size'] > $max_file_size) {
                $error = "File size must be less than 5MB";
            } else {
                $file_name = 'id_' . $user_id . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    if ($verification) {
                        $update = $conn->prepare("
                            UPDATE seller_verification 
                            SET business_name = ?, business_registration_number = ?, phone_number = ?, id_document_url = ?
                            WHERE user_id = ?
                        ");
                        $update->execute([$business_name, $business_reg, $phone, $file_path, $user_id]);
                    } else {
                        $insert = $conn->prepare("
                            INSERT INTO seller_verification (user_id, business_name, business_registration_number, phone_number, id_document_url)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $insert->execute([$user_id, $business_name, $business_reg, $phone, $file_path]);
                    }

                    $message = "Documents submitted successfully!";
                    $check_stmt->execute([$user_id]);
                    $verification = $check_stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Failed to upload file. Please try again.";
                }
            }
        }
    }

    if ($step === 'send_otp') {
        if ($verification && isset($verification['phone_number'])) {
            $demo_otp = "123456";
            
            $update_otp = $conn->prepare("UPDATE seller_verification SET otp_code = ? WHERE user_id = ?");
            if ($update_otp) {
                $update_otp->execute([$demo_otp, $user_id]);
                $check_stmt->execute([$user_id]);
                $verification = $check_stmt->fetch(PDO::FETCH_ASSOC);

                $phone = $verification['phone_number'];
                $message = " OTP sent to {$phone}<br><br><strong style='color: #d32f2f; font-size: 1.2rem;'>DEMO OTP: 123456</strong><br><small class='text-muted'>(For testing - In production, SMS would be sent)</small>";
            } else {
                $error = "Failed to save OTP";
            }
        } else {
            $error = "Please submit your documents first";
        }
    }

    if ($step === 'verify_otp') {
        $entered_otp = trim($_POST['otp']);

        $otp_stmt = $conn->prepare("SELECT otp_code FROM seller_verification WHERE user_id = ?");
        $otp_stmt->execute([$user_id]);
        $otp_data = $otp_stmt->fetch(PDO::FETCH_ASSOC);

        $stored_otp = isset($otp_data['otp_code']) ? trim($otp_data['otp_code']) : '';
        
        if (!empty($stored_otp) && $stored_otp === $entered_otp) {
            $update_verified = $conn->prepare("UPDATE seller_verification SET phone_verified = 1 WHERE user_id = ?");
            $update_verified->execute([$user_id]);
            $message = "Phone verified! Awaiting admin approval.";
            $check_stmt->execute([$user_id]);
            $verification = $check_stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Invalid OTP. You entered: '{$entered_otp}' but we have: '{$stored_otp}' | Click 'Send OTP' again to get a code.";
        }
    }
}

$user_stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
        .verification-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 600px; margin: 50px auto; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { text-align: center; padding: 10px; }
        .step.active { color: #007bff; font-weight: bold; }
        .step.completed { color: #66a1e4; }
        .badge-icon { font-size: 3rem; margin: 20px 0; }
        .form-section { margin: 20px 0; }
        .file-upload-area { border: 2px dashed #007bff; border-radius: 10px; padding: 20px; text-align: center; background: #7bb4e6; }
        .file-upload-area:hover { background: #76b3e6; cursor: pointer; }
        .otp-display { background: #698ff7; border: 2px solid #81aef1; border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 20px; }
        .otp-code { font-size: 2rem; font-weight: bold; color: #1422e6; letter-spacing: 5px; font-family: monospace; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="verification-container">
    <h2 class="fw-bold mb-4"> Become a Verified Seller</h2>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($verification): ?>
        <?php if ($verification['status'] === 'verified'): ?>
            <div class="alert alert-success text-center">
                <h4> You are Verified!</h4>
                <p>You have a verified seller badge on your profile.</p>
            </div>
        <?php elseif ($verification['status'] === 'rejected'): ?>
            <div class="alert alert-danger text-center">
                <h4> Verification Rejected</h4>
                <p>Please contact support for more information.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <h4> Verification Pending</h4>
                <p>Status: <strong>Admin Review</strong></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!$verification || $verification['status'] === 'rejected'): ?>
        <form method="POST" enctype="multipart/form-data" class="form-section">
            <input type="hidden" name="step" value="submit_docs">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Business Name</label>
                <input type="text" class="form-control" name="business_name" placeholder="Your business name" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Business Registration Number</label>
                <input type="text" class="form-control" name="business_registration" placeholder="Registration #" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Phone Number (10 digits)</label>
                <input type="text" class="form-control" name="phone" placeholder="0721234567" pattern="[0-9]{10}" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">ID Document or Passport</label>
                <div class="file-upload-area" onclick="document.getElementById('id_document').click()">
                    <p class="mb-0">Click to upload or drag and drop</p>
                    <small class="text-muted">PDF, JPG, PNG, GIF (Max 5MB)</small>
                </div>
                <input type="file" class="form-control d-none" id="id_document" name="id_document" 
                       accept=".pdf,.jpg,.jpeg,.png,.gif" required onchange="showFileName(this)">
                <small id="file-name" class="text-success d-block mt-2"></small>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                 Submit Documents
            </button>
        </form>
    <?php endif; ?>

    <?php if ($verification && $verification['status'] === 'pending' && isset($verification['phone_verified']) && !$verification['phone_verified']): ?>
        <div class="form-section">
            <h5 class="fw-bold mb-4">Step 2: Verify Phone Number</h5>
            
            <form method="POST" class="mb-3">
                <input type="hidden" name="step" value="send_otp">
                <button type="submit" class="btn btn-info w-100 btn-lg">
                    Send OTP to <?php echo isset($verification['phone_number']) ? substr($verification['phone_number'], 0, 3) . '***' . substr($verification['phone_number'], -3) : 'Phone'; ?>
                </button>
            </form>

            <?php if ($verification && isset($verification['otp_code']) && !empty($verification['otp_code'])): ?>
                <div class="otp-display">
                    <small class="text-muted">Your OTP is:</small><br>
                    <div class="otp-code"><?php echo htmlspecialchars($verification['otp_code']); ?></div>
                    <small class="text-muted">Enter this code below (Demo Mode)</small>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                     Click the "Send OTP" button above to get your demo OTP code
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="step" value="verify_otp">
                <div class="mb-3">
                    <label class="form-label fw-bold">Enter OTP (6 digits)</label>
                    <input type="text" class="form-control text-center" name="otp" placeholder="000000" pattern="[0-9]{6}" maxlength="6" required style="font-size: 1.5rem; letter-spacing: 5px; font-family: monospace;">
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100">
                    ✓ Verify OTP
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($verification && $verification['status'] === 'pending' && isset($verification['phone_verified']) && $verification['phone_verified']): ?>
        <div class="alert alert-warning text-center">
            <h5> Awaiting Admin Approval</h5>
            <p>Your documents are being reviewed by our team. This usually takes 24-48 hours.</p>
            <p class="text-muted">You'll receive an email once approved.</p>
        </div>
    <?php endif; ?>

    <hr class="my-4">

    <div class="text-center">
        <a href="seller_listings.php" class="btn btn-outline-secondary">
            Back to Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showFileName(input) {
    const fileName = input.files[0]?.name;
    const fileNameEl = document.getElementById('file-name');
    if (fileName) {
        fileNameEl.textContent = 'Selected: ' + fileName;
    }
}

const uploadArea = document.querySelector('.file-upload-area');
if (uploadArea) {
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.backgroundColor = '#bbdefb';
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.backgroundColor = '#e3f2fd';
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('id_document').files = files;
            showFileName(document.getElementById('id_document'));
        }
    });
}
</script>
</body>
</html>