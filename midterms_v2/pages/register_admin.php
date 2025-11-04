<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/class.user.php';

// Authorization Check: Only Superadmin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ' . SITE_URL . 'pages/login.php');
    exit;
}

$user = new User();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Set the role explicitly to 'admin'
    $role = 'admin'; 

    if (empty($username) || empty($password)) {
        $error = "Please fill in both username and password.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($user->findUserByUsername($username)) {
        $error = "Username already exists. Please choose a different one.";
    } else {
        // Attempt to register the user with the 'admin' role
        if ($user->register($username, $password, $role)) {
            $success = "New Admin account for '{$username}' created successfully!";
            // Optionally, clear the form variables after success
            $username = ''; 
            $password = ''; 
        } else {
            $error = "Failed to register Admin. Please check database connection or class method.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    
    <div class="content-wrapper">
        <div class="auth-container">
            <h2>Register New Admin</h2>
            <p>Superadmin panel for creating new administrators.</p>
            
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="form-group">
                    <label for="username">Admin Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password (Min 6 chars):</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <!-- Role is hidden and set programmatically to 'admin' -->
                <button type="submit">Create Admin Account</button>
            </form>
            
            <?php if (!empty($error)): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Registration Error',
                        text: '<?php echo $error; ?>'
                    });
                </script>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '<?php echo $success; ?>'
                    });
                </script>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <a href="manage_users.php" class="link-style">← Back to Manage Users</a>
            </div>
        </div>
    </div>

</body>
</html>
