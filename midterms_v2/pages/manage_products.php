<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/class.product.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'superadmin' && $_SESSION['role'] !== 'admin')) {
    header('Location: ' . SITE_URL . 'pages/login.php');
    exit;
}

$productObj = new Product();
$products = $productObj->getProducts(); // Get all products for display

$error = '';
$success = '';

// Handle Add Product
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $image_path = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/products/"; // Directory to save product images
        // Create directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = SITE_URL . 'assets/images/products/' . basename($_FILES["image"]["name"]);
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    }

    if (empty($error)) {
        if ($productObj->addProduct($name, $price, $image_path, $_SESSION['user_id'])) {
            $success = "Product added successfully!";
            header('Location: ' . SITE_URL . 'pages/manage_products.php?add_product_success=true');
            exit;
        } else {
            $error = "Failed to add product.";
        }
    }
}

// Handle Delete Product
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($productObj->deleteProduct($id)) {
        $success = "Product deleted successfully!";
        header('Location: ' . SITE_URL . 'pages/manage_products.php?delete_product_success=true');
        exit;
    } else {
        $error = "Failed to delete product.";
    }
}

// Handle Update Product (similar to add, but with existing ID and optional image update)
if (isset($_POST['update_product'])) {
    $id = (int)$_POST['product_id'];
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $current_image_path = $_POST['current_image_path']; // Hidden field for current image
    $new_image_path = null;

    // Handle image upload for update
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/products/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $new_image_path = SITE_URL . 'assets/images/products/' . basename($_FILES["image"]["name"]);
            } else {
                $error = "Sorry, there was an error uploading the new image.";
            }
        } else {
            $error = "File is not an image.";
        }
    }

    if (empty($error)) {
        // Use new image path if uploaded, otherwise keep current one
        $final_image_path = $new_image_path ?? $current_image_path;
        if ($productObj->updateProduct($id, $name, $price, $final_image_path)) {
            $success = "Product updated successfully!";
            header('Location: ' . SITE_URL . 'pages/manage_products.php?update_product_success=true');
            exit;
        } else {
            $error = "Failed to update product.";
        }
    }
}

// Check for success/error messages from redirect
if (isset($_GET['add_product_success'])) {
    $success = "Product added successfully!";
}
if (isset($_GET['delete_product_success'])) {
    $success = "Product deleted successfully!";
}
if (isset($_GET['update_product_success'])) {
    $success = "Product updated successfully!";
}

// Reload products after any action
$products = $productObj->getProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
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
                    <li><a href="dashboard.php">POS System</a></li>
                    <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <li><a href="register_admin.php">Register Admin</a></li>
                        <?php endif; ?>
                        <li><a href="manage_products.php" class="active">Manage Products</a></li>
                        <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <li><a href="manage_users.php">Manage Users</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        <main class="content">
            <h1>Manage Products</h1>

            <?php if (!empty($success)): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '<?php echo $success; ?>'
                    });
                </script>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: '<?php echo $error; ?>'
                    });
                </script>
            <?php endif; ?>

            <div class="card add-product-card">
                <h3>Add New Product</h3>
                <form action="manage_products.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="product_name">Product Name:</label>
                        <input type="text" id="product_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="product_price">Price (PHP):</label>
                        <input type="number" id="product_price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product_image">Product Image:</label>
                        <input type="file" id="product_image" name="image" accept="image/*">
                    </div>
                    <button type="submit" name="add_product" class="btn-primary">Add Product</button>
                </form>
            </div>

            <hr>

            <div class="card">
                <h3>Current Products</h3>
                <div class="product-management-list">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $p): ?>
                            <div class="product-item">
                                <img src="<?php echo htmlspecialchars($p->image_path ?: SITE_URL . 'assets/images/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($p->name); ?>">
                                <div class="product-details">
                                    <h4><?php echo htmlspecialchars($p->name); ?></h4>
                                    <p>Price: <?php echo number_format($p->price, 2); ?> PHP</p>
                                    <p class="added-info">Added by: <?php echo htmlspecialchars($p->added_by_username); ?> on <?php echo date('M d, Y H:i', strtotime($p->date_added)); ?></p>
                                </div>
                                <div class="product-actions">
                                    <button class="btn-edit" onclick="openEditModal(<?php echo $p->id; ?>, '<?php echo htmlspecialchars(addslashes($p->name)); ?>', <?php echo $p->price; ?>, '<?php echo htmlspecialchars($p->image_path); ?>')">Edit</button>
                                    <a href="manage_products.php?action=delete&id=<?php echo $p->id; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No products added yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit Product Modal --><div id="editProductModal" class="modal">
                <div class="modal-content">
                    <span class="close-button" onclick="closeEditModal()">&times;</span>
                    <h3>Edit Product</h3>
                    <form action="manage_products.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" id="edit_product_id" name="product_id">
                        <input type="hidden" id="current_image_path" name="current_image_path">
                        <div class="form-group">
                            <label for="edit_product_name">Product Name:</label>
                            <input type="text" id="edit_product_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_price">Price (PHP):</label>
                            <input type="number" id="edit_product_price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Current Image:</label>
                            <img id="edit_current_image" src="" alt="Current Product Image" style="max-width: 100px; height: auto; display: block; margin-top: 5px;">
                        </div>
                        <div class="form-group">
                            <label for="edit_product_image">New Product Image (optional):</label>
                            <input type="file" id="edit_product_image" name="image" accept="image/*">
                        </div>
                        <button type="submit" name="update_product" class="btn-primary">Update Product</button>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        // JavaScript for Edit Product Modal
        const editProductModal = document.getElementById('editProductModal');
        const editProductId = document.getElementById('edit_product_id');
        const editProductName = document.getElementById('edit_product_name');
        const editProductPrice = document.getElementById('edit_product_price');
        const editCurrentImage = document.getElementById('edit_current_image');
        const currentImagePathInput = document.getElementById('current_image_path');

        function openEditModal(id, name, price, imagePath) {
            editProductId.value = id;
            editProductName.value = name;
            editProductPrice.value = price;
            editCurrentImage.src = imagePath;
            currentImagePathInput.value = imagePath; // Store current path
            editProductModal.style.display = 'block';
        }

        function closeEditModal() {
            editProductModal.style.display = 'none';
        }

        // Close modal if clicking outside
        window.onclick = function(event) {
            if (event.target == editProductModal) {
                editProductModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>