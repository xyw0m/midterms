<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/class.user.php';

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ' . SITE_URL . 'pages/login.php');
    exit;
}

$user = new User();
$success = '';
$error = '';

// Handle Suspend/Unsuspend action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $admin_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'suspend') {
        if ($user->suspendUser($admin_id)) {
            $success = "Admin ID {$admin_id} has been suspended successfully.";
        } else {
            $error = "Failed to suspend user.";
        }
    } elseif ($action === 'unsuspend') {
        if ($user->unsuspendUser($admin_id)) {
            $success = "Admin ID {$admin_id} has been unsuspended successfully.";
        } else {
            $error = "Failed to unsuspend user.";
        }
    }
    
    // Redirect to clear GET parameters and display success/error
    $message = urlencode($success ?: $error);
    $status_type = $success ? 'success' : 'error';
    header("Location: " . SITE_URL . "pages/manage_users.php?status={$status_type}&message={$message}");
    exit;
}

// Check for status messages from redirect
if (isset($_GET['status']) && isset($_GET['message'])) {
    if ($_GET['status'] === 'success') {
        $success = urldecode($_GET['message']); 
    } elseif ($_GET['status'] === 'error') {
        $error = urldecode($_GET['message']);
    }
}


$admins = $user->getAllAdmins(); // Get all admin users
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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
                    <li><a href="register_admin.php">Register Admin</a></li>
                    <li><a href="manage_products.php">Manage Products</a></li>
                    <li><a href="manage_users.php" class="active">Manage Users</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        <main class="content">
            <h1>Manage Admin Users</h1>
            
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


            <div class="card">
                <h3>Registered Admins</h3>
                <?php if (count($admins) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Date Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo $admin->id; ?></td>
                                    <td><?php echo htmlspecialchars($admin->username); ?></td>
                                    <td><?php echo ucfirst($admin->role); ?></td>
                                    <td>
                                        <span class="status-indicator <?php echo $admin->is_suspended ? 'suspended' : 'active'; ?>">
                                            <?php echo $admin->is_suspended ? 'Suspended' : 'Active'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($admin->created_at)); ?></td>
                                    <td>
                                        <?php if ($admin->is_suspended): ?>
                                            <!-- Button to Unsuspend -->
                                            <a href="manage_users.php?action=unsuspend&id=<?php echo $admin->id; ?>" class="btn-primary btn-action" onclick="return confirm('Are you sure you want to UNSUSPEND <?php echo htmlspecialchars($admin->username); ?>?');">Unsuspend</a>
                                        <?php else: ?>
                                            <!-- Button to Suspend -->
                                            <a href="manage_users.php?action=suspend&id=<?php echo $admin->id; ?>" class="btn-delete btn-action" onclick="return confirm('Are you sure you want to SUSPEND <?php echo htmlspecialchars($admin->username); ?>?');">Suspend</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No admin users registered yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Added simple CSS for status display (would typically go in style.css) -->
    <style>
        .status-indicator {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            display: inline-block;
        }
        .status-indicator.active {
            background-color: #2ecc71; /* Green */
            color: white;
        }
        .status-indicator.suspended {
            background-color: #e67e22; /* Orange/Warning */
            color: white;
        }
        .btn-action {
            display: inline-block;
            text-decoration: none;
            padding: 8px 12px;
            font-size: 0.9em;
        }
        .btn-action.btn-primary { /* Unsuspend Button */
            background-color: #3498db;
        }
        .btn-action.btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-action.btn-delete { /* Suspend Button */
            background-color: #e74c3c;
        }
        .btn-action.btn-delete:hover {
            background-color: #c0392b;
        }
    </style>
</body>
</html>
