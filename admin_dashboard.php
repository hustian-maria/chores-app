<?php
// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

// Start session and check if admin is logged in (simple password check for demo)
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // Simple admin login form
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_password'])) {
        if ($_POST['admin_password'] === 'admin123') { // Change this in production!
            $_SESSION['admin_logged_in'] = true;
        } else {
            $error = "Invalid admin password";
        }
    }
    
    if (!isset($_SESSION['admin_logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - Household Services</title>
            <link rel="stylesheet" href="style.css">
        </head>
        <body>
            <header>
                <div class="header-content">
                    <div class="logo"> Household Services - Admin</div>
                </div>
            </header>

            <div class="container">
                <div class="card" style="max-width: 400px; margin: 100px auto;">
                    <h2 class="text-center mb-20">Admin Login</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="admin_password">Admin Password</label>
                            <input type="password" id="admin_password" name="admin_password" required>
                        </div>
                        <button type="submit" class="btn">Login</button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

$users_count = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$workers_count = $db->query("SELECT COUNT(*) as count FROM workers")->fetch_assoc()['count'];
$bookings_count = $db->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];

// get all users
$users_query = $db->query("SELECT * FROM users ORDER BY created_at DESC");

// get all workers
$workers_query = $db->query("SELECT * FROM workers ORDER BY created_at DESC");

// Get all bookings with user and worker info
$bookings_query = $db->query("
    SELECT b.*, u.name as user_name, w.name as worker_name 
    FROM bookings b 
    LEFT JOIN users u ON b.user_email = u.email 
    LEFT JOIN workers w ON b.worker_email = w.email 
    ORDER BY b.created_at DESC
");

// Handle delete actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        $db->query("DELETE FROM users WHERE id = $user_id");
        header("Refresh:0");
        exit();
    } elseif (isset($_POST['delete_worker']) && isset($_POST['worker_id'])) {
        $worker_id = $_POST['worker_id'];
        $db->query("DELETE FROM workers WHERE id = $worker_id");
        header("Refresh:0");
        exit();
    } elseif (isset($_POST['delete_booking']) && isset($_POST['booking_id'])) {
        $booking_id = $_POST['booking_id'];
        $db->query("DELETE FROM bookings WHERE id = $booking_id");
        header("Refresh:0");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Household Services</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Household Services - Admin</div>
            <nav>
                <ul>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <div class="dashboard-nav">
                <span class="badge">Administrator</span>
            </div>
        </div>


        <div class="stats-grid">
            <div class="stat-card">
                <h4><?php echo $users_count; ?></h4>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h4><?php echo $workers_count; ?></h4>
                <p>Total Workers</p>
            </div>
            <div class="stat-card">
                <h4><?php echo $bookings_count; ?></h4>
                <p>Total Bookings</p>
            </div>
        </div>

        <!-- user management -->
        <div class="card">
            <h3>Users Management</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users_query->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="delete_user" value="1">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- worker management -->
        <div class="card">
            <h3>Workers Management</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Skills</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($worker = $workers_query->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $worker['id']; ?></td>
                            <td><?php echo htmlspecialchars($worker['name']); ?></td>
                            <td><?php echo htmlspecialchars($worker['email']); ?></td>
                            <td><?php echo htmlspecialchars($worker['phone'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(str_replace(',', ', ', $worker['skills'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($worker['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                                    <input type="hidden" name="delete_worker" value="1">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this worker?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- bookings Management -->
        <div class="card">
            <h3>Bookings Management</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Worker</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings_query->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $booking['id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['user_name'] ?: 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($booking['service'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($booking['time'])); ?></td>
                            <td><?php echo htmlspecialchars($booking['worker_name'] ?: 'Not assigned'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <input type="hidden" name="delete_booking" value="1">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this booking?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
