<?php

include 'includes/db.php';
session_start();

if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Using a prepared statement to prevent SQL Injection
    $stmt = $conn->prepare("SELECT user_id, name, password, role, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])) {
            // Store all vital info in the session
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role']; // Important for RBAC
            $_SESSION['is_verified'] = $row['is_verified'];

            // Redirect based on role
            if($_SESSION['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            echo "<script>alert('Wrong password!');</script>";
        }
    } else {
        echo "<script>alert('User not found!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
      <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<h2>User Registration</h2>

<form method="POST">

    <input type="text" name="name" placeholder="Full Name" required>
    <br><br>

    <input type="email" name="email" placeholder="Email" required>
    <br><br>

    <input type="text" name="phone" placeholder="Phone Number" required>
    <br><br>

    <input type="password" name="password" placeholder="Password" required>
    <br><br>

    <button type="submit" name="register">
        Register</button>

</form>

</body>
</html>