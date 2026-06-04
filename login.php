<?php
include 'includes/db.php';
session_start();

// Handle Login Logic
if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, name, password, role, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if($row) {
        if(password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['is_verified'] = $row['is_verified'];
            header("Location: index.php");
            exit();
        } else { $error = "Invalid password."; }
    } else { $error = "User not found."; }
}

// Handle Registration Logic
if(isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users(name, email, phone, password, role) VALUES(?, ?, ?, ?, 'user')";
    $stmt = $conn->prepare($sql);
    
    try {
        $stmt->execute([$name, $email, $phone, $password]);
        $success = "Account created! You can now login.";
    } catch(PDOException $e) {
        $error = "Registration failed. Email might already exist.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login & Register - Bridging Mzanzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; height: 100vh; display: flex; align-items: center; }
        .auth-card { 
            background: white; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
        }
        .login-section { padding: 40px; }
        .register-section { 
            background-color: #e3f2fd;
            padding: 40px; 
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .btn-primary { background-color: #0d6efd; border-radius: 50px; padding: 10px 30px; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card auth-card">
                <div class="row g-0">
                    
                    <div class="col-md-6 login-section">
                        <h3 class="fw-bold mb-4">Login to your Account</h3>
                        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                        <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">LOGIN</button>
                        </form>
                    </div>

                    <div class="col-md-6 register-section text-center">
                        <h3 class="fw-bold mb-3">Don't have an Account?</h3>
                        <p class="text-muted mb-4">Sign up to start trading in your community.</p>
                        
                        <form method="POST">
                            <input type="text" name="name" class="form-control mb-2" placeholder="Full Name" required>
                            <input type="email" name="email" class="form-control mb-2" placeholder="Email Address" required>
                            <input type="text" name="phone" class="form-control mb-2" placeholder="Phone Number" required>
                            <input type="password" name="password" class="form-control mb-3" placeholder="Create Password" required>
                            <button type="submit" name="register" class="btn btn-outline-primary w-100 rounded-pill">REGISTER</button>
                        </form>
                    </div>

                </div>
            </div>
            <div class="text-center mt-3">
                <a href="index.php" class="text-decoration-none text-muted">← Back to Marketplace</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>