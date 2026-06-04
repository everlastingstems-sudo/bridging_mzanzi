<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    if (isset($_SESSION['user_id']) && $delete_id == $_SESSION['user_id']) {
        $error_msg = "You cannot delete your own admin account while logged in!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        try {
            $stmt->execute([$delete_id]);
            header("Location: admin_users.php?msg=User+deleted+successfully");
            exit();
        } catch(PDOException $e) {
            $error_msg = "Error deleting user account.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid px-4 mt-4">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>

            <div class="col-md-9">
                <div class="p-4 bg-white shadow-sm mb-4" style="border-radius: 20px;">
                    <h1 class="display-6 fw-bold text-dark mb-1">User Management</h1>
                    <p class="text-muted">Review, verify, update, or remove registered accounts</p>
                    <hr>

                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                    <?php endif; ?>

                    <div class="table-responsive mt-3">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $users_query = $conn->query("SELECT * FROM users");
                                $users = $users_query->fetchAll(PDO::FETCH_ASSOC);
                                if (count($users) > 0):
                                    foreach($users as $row):
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $row['user_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($row['role'] === 'admin') ? 'bg-danger' : 'bg-info text-dark'; ?> rounded-pill px-3">
                                            <?php echo strtoupper($row['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(isset($row['is_verified']) && $row['is_verified'] == 1): ?>
                                            <span class="text-success fw-bold">✔ Verified</span>
                                        <?php else: ?>
                                            <span class="text-muted">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-outline-primary me-1 rounded-pill">Edit</a>
                                        <a href="admin_users.php?delete_id=<?php echo $row['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-pill" 
                                           onclick="return confirm('Are you absolutely sure you want to delete this user account?')">
                                           Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    endforeach;
                                else: 
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No users registered in the system database.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
