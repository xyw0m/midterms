<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/class.product.php';
require_once '../includes/class.user.php'; // To get user details for "added by"

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'pages/login.php');
    exit;
}

$product = new Product();
$products = $product->getProducts(); // Get all products for the menu

// Handle adding new products (only Superadmin/Admin)
$add_product_error = '';
if (isset($_GET['add_product_success'])) {
    $add_product_success = "Product added successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
            <p>Role: <?php echo ucfirst($_SESSION['role']); ?></p>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active">POS System</a></li>
                    <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <li><a href="register_admin.php">Register Admin</a></li>
                        <?php endif; ?>
                        <li><a href="manage_products.php">Manage Products</a></li>
                        <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <li><a href="manage_users.php">Manage Users</a></li>
                        <?php endif; ?>
                       <li>
                        <a class="nav-link" href="reports_sales.php">
                            <i class="fas fa-chart-line"></i>Reports</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        <main class="content">
            <h1>POS System</h1>

            <?php if (!empty($add_product_success)): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '<?php echo $add_product_success; ?>'
                    });
                </script>
            <?php endif; ?>

            <div class="pos-layout">
                <div class="menu-section">
                    <h2>Menu</h2>
                    <div class="product-list">
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $p): ?>
                                <div class="product-card" data-id="<?php echo $p->id; ?>" data-name="<?php echo htmlspecialchars($p->name); ?>" data-price="<?php echo $p->price; ?>">
                                    <img src="<?php echo htmlspecialchars($p->image_path); ?>" alt="<?php echo htmlspecialchars($p->name); ?>">
                                    <h3><?php echo htmlspecialchars($p->name); ?></h3>
                                    <p class="price"><?php echo number_format($p->price, 2); ?> PHP</p>
                                    <div class="add-to-order-controls">
                                        <input type="number" value="1" min="1" class="product-qty">
                                        <button class="add-to-order-btn">Add to order</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No products available. Please add some from the "Manage Products" section.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-section">
                    <h2>Ordered Items</h2>
                    <div id="ordered-items-list" class="ordered-items-list">
                        <!-- Ordered items will be dynamically added here by JavaScript --></div>
                    <div class="order-summary">
                        <h3>Total: <span id="order-total">0.00</span> PHP</h3>
                        <div class="form-group">
                            <label for="amount-paid">Enter the amount here</label>
                            <input type="number" id="amount-paid" step="0.01" min="0">
                        </div>
                        <button id="pay-button" class="btn-primary">Pay</button>
                        <button id="clear-order-button" class="btn-secondary">Clear Order</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Pass PHP product data to JavaScript
        const productsData = <?php echo json_encode($products); ?>;
        // Initialize the POS system with product data
        document.addEventListener('DOMContentLoaded', () => {
            initializePOS(productsData);
        });
    </script>
</body>
</html>