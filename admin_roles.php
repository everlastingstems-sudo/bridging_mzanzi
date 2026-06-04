<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $target_user_id = intval($_POST['user_id']);
    $assigned_role = $_POST['role'];

    if ($target_user_id === $_SESSION['user_id'] && $assigned_role !== 'admin') {
        $error_msg = "Security Protection: You cannot revoke your own Administrator permissions!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        try {
            $stmt->execute([$assigned_role, $target_user_id]);
            header("Location: admin_roles.php?msg=System+role+updated+successfully");
            exit();
        } catch(PDOException $e) {
            $error_msg = "Failed to update user access level configuration.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Access Roles - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; }
        .panel-card { background: white; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid px-4 mt-4">
        <div class="row">
            
            <?php include 'includes/admin_sidebar.php'; ?>

            <div class="col-md-9">
                <div class="p-4 panel-card mb-4">
                    <h1 class="display-6 fw-bold text-dark mb-1">Roles & Access Control</h1>
                    <p class="text-muted">Manage system permission groups and administrative privileges</p>
                    <hr>
                    
                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                    <?php endif; ?>

                    <div class="row g-3 mb-4 mt-2">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border-start border-4 border-info">
                                <h6 class="fw-bold mb-1 text-info text-uppercase text-dark">Standard User Permissions</h6>
                                <small class="text-muted">Granted default browse, purchase, and standard seller product listing uploads.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border-start border-4 border-danger">
                                <h6 class="fw-bold mb-1 text-danger text-uppercase">Admin Permissions</h6>
                                <small class="text-muted">Full administrative backend entry. Moderation powers over users, items, and transactions.</small>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>User ID</th>
                                    <th>Account Name</th>
                                    <th>Email Address</th>
                                    <th>Current Assignment</th>
                                    <th class="text-center">Modify Access Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $users_query = $conn->query("SELECT user_id, name, email, role FROM users");
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
                                    <td class="text-center">
                                        <form action="admin_roles.php" method="POST" class="d-flex gap-2 justify-content-center">
                                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                            <select name="role" class="form-select form-select-sm rounded-pill" style="width: 150px;">
                                                <option value="user" <?php if($row['role'] === 'user') echo 'selected'; ?>>Standard User</option>
                                                <option value="admin" <?php if($row['role'] === 'admin') echo 'selected'; ?>>Administrator</option>
                                            </select>
                                            <button type="submit" name="update_role" class="btn btn-sm btn-dark rounded-pill px-3">Assign</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No platform users found.</td>
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