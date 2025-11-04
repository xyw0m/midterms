<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/class.user.php';

// Check if a Superadmin already exists. If so, redirect to login.
$user = new User();
if ($user->findUserByUsername('superadmin')) {
    // If the default superadmin user exists, redirect to login page.
    // NOTE: In a real app, you might check for any user with the 'superadmin' role.
    header('Location: ' . SITE_URL . 'pages/login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Hardcode the role to 'superadmin' for this specific registration form
    $role = 'superadmin'; 

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Attempt to register the user
        if ($user->register($username, $password, $role)) {
            $success = "Superadmin account created successfully! Redirecting to login...";
            // Redirect after a short delay for the user to see the success message
            header('Refresh: 3; URL=' . SITE_URL . 'pages/login.php');
        } else {
            $error = "Registration failed. Username may already be taken or there was a database error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Registration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="auth-container">
        <h2>Initial Superadmin Setup</h2>
        <p>This is a one-time registration for the system's primary administrator.</p>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="form-group">
                <label for="username">Superadmin Username:</label>
                <!-- Pre-fill the username suggestion, but allow modification -->
                <input type="text" id="username" name="username" value="superadmin" required>
            </div>
            <div class="form-group">
                <label for="password">Password (Min 6 chars):</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Create Superadmin</button>
        </form>
        
        <?php if (!empty($error)): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
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
    </div>
</body>
</html>
