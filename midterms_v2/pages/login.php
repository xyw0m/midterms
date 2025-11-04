<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/class.user.php';

$user = new User();
$error = '';



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $loggedInUser = $user->login($username, $password);

        if ($loggedInUser === 'suspended') {
            // New check for suspended status
            $error = "Your account has been suspended. Please contact the Superadmin.";
        } elseif ($loggedInUser) {
            $_SESSION['user_id'] = $loggedInUser->id;
            $_SESSION['username'] = $loggedInUser->username;
            $_SESSION['role'] = $loggedInUser->role;
            header('Location: ' . SITE_URL . 'pages/dashboard.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="auth-container">
        <h2>Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <?php if (!empty($error)): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Login Error',
                    text: '<?php echo $error; ?>'
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
