<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';

$is_seller = false;

if (isset($_SESSION['user_id']) && isset($conn)) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT 1 
        FROM products 
        WHERE user_id = ? 
        AND is_deleted = 0 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $seller_result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($seller_result) {
        $is_seller = true;
    }
}

$is_verified = false;
if ($is_seller) {
    $verify_stmt = $conn->prepare("SELECT status FROM seller_verification WHERE user_id = ?");
    $verify_stmt->execute([$user_id]);
    $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    $is_verified = $verify_result && $verify_result['status'] === 'verified';
}
?>

<nav class="navbar navbar-expand-lg navbar-light shadow-sm mb-4" style="background-color: #e3f2fd;">

    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="logo.png" alt="Bridging Mzanzi Logo" height="45">
        </a>

        <!-- Mobile Button -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- LEFT MENU -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Home</a>
                </li>

                <?php if(isset($_SESSION['user_id'])): ?>

                    <li class="nav-item">
                        <a class="nav-link" href="my_orders.php">
                            My Orders
                        </a>
                    </li>

                    <?php if ($is_seller): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="seller_listings.php">
                                Seller Dashboard
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>

                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>

                    <li class="nav-item">
                        <a class="nav-link fw-bold text-danger" href="admin_dashboard.php">
                            Admin Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fw-bold text-warning" href="admin_seller_verification.php">
                            Verify Sellers
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

            <!-- SEARCH -->
            <form class="d-flex me-auto w-50"
                  action="index.php"
                  method="GET">

                <input class="form-control me-2 rounded-pill"
                       type="search"
                       name="search"
                       placeholder="Search for products..."
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">

                <button class="btn btn-outline-primary rounded-pill" type="submit">
                    Search
                </button>

            </form>

            <!-- RIGHT SIDE -->
            <div class="d-flex align-items-center">

                <?php if(isset($_SESSION['name'])): ?>

                    <a href="add_product.php"
                       class="btn btn-sm btn-primary me-2 rounded-pill px-3 fw-semibold">
                        + Sell an Item
                    </a>

                    <a href="seller_listings.php"
                       class="btn btn-sm btn-outline-dark me-3 rounded-pill px-3">
                        My Listings <?php echo $is_verified ? '✅' : ''; ?>
                    </a>

                    <span class="navbar-text me-3 text-dark">
                        Hi,
                        <strong>
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </strong>
                    </span>

                    <a href="logout.php"
                       class="btn btn-sm btn-outline-danger rounded-pill">
                        Logout
                    </a>

                <?php else: ?>

                    <a href="login.php"
                       class="btn btn-sm btn-outline-primary rounded-pill">
                        Login
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</nav>
                