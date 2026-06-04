<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $user = $result;
    } else {
        echo "User not found.";
        exit();
    }
} else {
    echo "No user specified.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;

    if (!empty($name) && !empty($email)) {
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, is_verified = ? WHERE user_id = ?");
        
        try {
            $update_stmt->execute([$name, $email, $role, $is_verified, $user_id]);
            header("Location: admin_users.php?msg=User+updated+successfully");
            exit();
        } catch(PDOException $e) {
            $error_msg = "Database update failed.";
        }
    } else {
        $error_msg = "Please fill out all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Account - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
        .edit-card { background: white; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid px-4 mt-4">
        <div class="row">
            
            <?php include 'includes/admin_sidebar.php'; ?>

            <div class="col-md-9">
                <div class="p-4 edit-card">
                    <h1 class="display-6 fw-bold text-dark mb-1">Edit Account Profile</h1>
                    <p class="text-muted">Modify account properties for User #<?php echo $user['user_id']; ?></p>
                    <hr>

                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <form action="edit_user.php?id=<?php echo $user['user_id']; ?>" method="POST" class="mt-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold">System Role Permission</label>
                                <select name="role" class="form-select">
                                    <option value="user" <?php if($user['role'] === 'user') echo 'selected'; ?>>User (Buyer/Seller)</option>
                                    <option value="admin" <?php if($user['role'] === 'admin') echo 'selected'; ?>>System Administrator</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-4 d-flex align-items-center">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" name="is_verified" id="verifySwitch" <?php if(isset($user['is_verified']) && $user['is_verified'] == 1) echo 'checked'; ?>>
                                    <label class="form-check-input-label fw-semibold ms-2" for="verifySwitch">Grant Verified Account Status</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                            <a href="admin_users.php" class="btn btn-outline-secondary rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>