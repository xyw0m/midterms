<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/class.user.php'; 
require_once '../includes/class.order.php'; // NEW: Include the Order Class

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'pages/login.php');
    exit;
}

// Fetch all sales history
try {
    $order = new Order();
    $sales_history = $order->getSalesHistory();
} catch (Exception $e) {
    // Handle database connection error during report generation
    $sales_history = [];
    $error_message = "Error loading reports: " . $e->getMessage();
}

$user_role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Transaction History</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Assuming you use a CSS library like Tailwind or similar for the nice layout -->
</head>
<body>
    <div class="container">
        <!-- Sidebar (Omitted for brevity, assuming structure is similar to dashboard) -->
        <aside class="sidebar">
            <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
            <p>Role: <?php echo ucfirst($_SESSION['role']); ?></p>
            <nav>
                <ul>
                    <li><a href="dashboard.php">POS System</a></li>
                    <?php if ($user_role === 'superadmin'): ?>
                        <li><a href="register_admin.php">Register Admin</a></li>
                    <?php endif; ?>
                    <li><a href="manage_products.php">Manage Products</a></li>
                    <?php if ($user_role === 'superadmin'): ?>
                        <li><a href="manage_users.php">Manage Users</a></li>
                    <?php endif; ?>
                    <li><a href="reports_sales.php" class="active">Reports</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="content">
            <a href="dashboard.php" class="back-link">&leftarrow; Back to Dashboard</a>
            
            <h1>Sales Transaction History</h1>

            <?php if (isset($error_message)): ?>
                <div class="error-message p-3 bg-red-100 text-red-700 border border-red-400 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="report-controls flex space-x-4 mb-6 p-4 border rounded shadow-sm bg-gray-50">
                <!-- Date Filter Controls (Static/Placeholder based on your screenshot) -->
                <div class="date-filter">
                    <label for="start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" id="start-date" value="2025-11-01" class="p-2 border rounded">
                </div>
                <div class="date-filter">
                    <label for="end-date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" id="end-date" value="2025-11-04" class="p-2 border rounded">
                </div>
                <button class="filter-btn btn-primary self-end">Filter</button>
                <button class="download-btn btn-secondary self-end">Download Report (Placeholder)</button>
            </div>

            <div class="transaction-table-container mt-6">
                <table class="min-w-full divide-y divide-gray-200 shadow overflow-hidden sm:rounded-lg">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date/Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Cashier</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Total Amount</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Paid</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Change</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($sales_history) > 0): ?>
                            <?php foreach ($sales_history as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($order['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('Y-m-d H:i:s', strtotime($order['order_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($order['cashier_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                        <?php echo number_format($order['total_amount'], 2); ?> PHP
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">
                                        <?php echo number_format($order['amount_paid'], 2); ?> PHP
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                        <?php echo number_format($order['change_due'], 2); ?> PHP
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No order transactions found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
