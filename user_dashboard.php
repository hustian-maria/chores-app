<?php
// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

// Helper function to get service icon
function getServiceIcon($service) {
    $icons = [
        'cleaning' => '🧹',
        'laundry' => '👕',
        'grocery_runs' => '🛒',
        'minor_repairs' => '�',
        'babysitting' => '👶',
        'cooking' => '�‍🍳'
    ];
    return $icons[$service] ?? '🏠';
}

// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'];

// Get user details
$user_query = $db->prepare("SELECT * FROM users WHERE email = ?");
$user_query->bind_param("s", $user_email);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

// Get user's bookings
$bookings_query = $db->prepare("SELECT * FROM bookings WHERE user_email = ? ORDER BY date DESC, time DESC");
$bookings_query->bind_param("s", $user_email);
$bookings_query->execute();
$bookings_result = $bookings_query->get_result();

// Handle booking actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        $booking_id = $_POST['booking_id'];
        $action = $_POST['action'];
        
        if ($action == 'cancel') {
            $update_query = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_email = ?");
            $update_query->bind_param("is", $booking_id, $user_email);
            $update_query->execute();
            
            // Refresh the page to show updated status
            header("Refresh:0");
            exit();
        } elseif ($action == 'reschedule') {
            $new_date = $_POST['new_date'] ?? '';
            $new_time = $_POST['new_time'] ?? '';
            
            if (!empty($new_date) && !empty($new_time)) {
                $update_query = $db->prepare("UPDATE bookings SET date = ?, time = ? WHERE id = ? AND user_email = ?");
                $update_query->bind_param("ssis", $new_date, $new_time, $booking_id, $user_email);
                $update_query->execute();
                
                // Refresh the page to show updated status
                header("Refresh:0");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - HomeClean</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><ion-icon name="home-outline"></ion-icon> HomeClean</div>
            <nav>
                <ul>
                    <li><a href="user_dashboard.php">Dashboard</a></li>
                    <li><a href="register.php">Book New Service</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <div class="dashboard-nav">
                <a href="user_register.html" class="btn btn-sm">Book New Service</a>
            </div>
        </div>

        <!-- User Profile Information -->
        <div class="card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php 
                        $profile_pic = $user['profile_picture'] ?? null;
                        if ($profile_pic && file_exists('uploads/profile_pictures/' . $profile_pic)) {
                            echo '<img src="uploads/profile_pictures/' . $profile_pic . '" alt="Profile Picture" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #0056b3;">';
                        } else {
                            echo '<ion-icon name="person-outline" style="font-size: 40px; color: #666;"></ion-icon>';
                        }
                    ?>
                </div>
                <div class="profile-details">
                    <h2><?php echo htmlspecialchars($user_name); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="profile-stats">
                        <span class="stat-badge">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="register.php" class="btn btn-sm">Book New Service</a>
                </div>
            </div>
            <div class="profile-info-grid">
                <div class="info-item">
                    <label>Phone</label>
                    <p><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Address</label>
                    <p><?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3>Quick Actions</h3>
            <div class="quick-actions-grid">
                <a href="register.php" class="action-card">
                    <div class="action-icon">📅</div>
                    <h4>Book New Service</h4>
                    <p>Schedule a new household service</p>
                </a>
                <div class="action-card" onclick="showBookingHistory()">
                    <div class="action-icon">📋</div>
                    <h4>View History</h4>
                    <p>See all your past bookings</p>
                </div>
                <div class="action-card" onclick="showProfileSettings()">
                    <div class="action-icon">⚙️</div>
                    <h4>Profile Settings</h4>
                    <p>Update your information</p>
                </div>
                <a href="logout.php" class="action-card">
                    <div class="action-icon">🚪</div>
                    <h4>Logout</h4>
                    <p>Sign out of your account</p>
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="card">
            <h3>Your Service Overview</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <h4><?php echo $bookings_result->num_rows; ?></h4>
                    <p>Total Bookings</p>
                </div>
                <?php
                // Count bookings by status
                $pending_count = 0;
                $accepted_count = 0;
                $completed_count = 0;
                $cancelled_count = 0;
                
                $bookings_result->data_seek(0); // Reset result pointer
                while ($booking = $bookings_result->fetch_assoc()) {
                    switch($booking['status']) {
                        case 'pending': $pending_count++; break;
                        case 'accepted': $accepted_count++; break;
                        case 'completed': $completed_count++; break;
                        case 'cancelled': $cancelled_count++; break;
                    }
                }
                ?>
                <div class="stat-card">
                    <h4><?php echo $pending_count; ?></h4>
                    <p>Pending</p>
                </div>
                <div class="stat-card">
                    <h4><?php echo $accepted_count; ?></h4>
                    <p>Active</p>
                </div>
                <div class="stat-card">
                    <h4><?php echo $completed_count; ?></h4>
                    <p>Completed</p>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h3>Your Bookings</h3>
                <div class="filter-tabs">
                    <button class="tab-btn active" onclick="filterBookings('all')">All</button>
                    <button class="tab-btn" onclick="filterBookings('pending')">Pending</button>
                    <button class="tab-btn" onclick="filterBookings('active')">Active</button>
                    <button class="tab-btn" onclick="filterBookings('completed')">Completed</button>
                </div>
            </div>
            
            <?php if ($bookings_result->num_rows > 0): ?>
                <div class="bookings-container">
                    <?php 
                    $bookings_result->data_seek(0); // Reset result pointer again
                    while ($booking = $bookings_result->fetch_assoc()): 
                        // Get worker name and phone if assigned
                        $worker_name = 'Not assigned';
                        $worker_phone = 'Not provided';
                        if (!empty($booking['worker_email'])) {
                            $worker_query = $db->prepare("SELECT name, phone FROM workers WHERE email = ?");
                            $worker_query->bind_param("s", $booking['worker_email']);
                            $worker_query->execute();
                            $worker_result = $worker_query->get_result();
                            if ($worker = $worker_result->fetch_assoc()) {
                                $worker_name = htmlspecialchars($worker['name']);
                                $worker_phone = htmlspecialchars($worker['phone']);
                            }
                        }
                    ?>
                        <div class="booking-card" data-status="<?php echo $booking['status']; ?>">
                            <div class="booking-info">
                                <div class="service-info">
                                    <span class="service-icon"><?php echo getServiceIcon($booking['service']); ?></span>
                                    <div>
                                        <h4><?php echo htmlspecialchars(ucfirst($booking['service'])); ?></h4>
                                        <p class="customer-name"><ion-icon name="person-outline"></ion-icon> <?php echo htmlspecialchars($worker_name); ?></p>
                                    </div>
                                </div>
                                <div class="booking-status">
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="booking-details">
                                <div class="detail-item">
                                    <span class="detail-label"><ion-icon name="calendar-outline"></ion-icon> Date:</span>
                                    <span><?php echo date('M d, Y', strtotime($booking['date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><ion-icon name="time-outline"></ion-icon> Time:</span>
                                    <span><?php echo date('h:i A', strtotime($booking['time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><ion-icon name="call-outline"></ion-icon> Worker Phone:</span>
                                    <span><?php echo htmlspecialchars($worker_phone ?: 'Not provided'); ?></span>
                                </div>
                            </div>
                            <div class="booking-actions">
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</button>
                                    </form>
                                    <button class="btn btn-warning btn-sm" onclick="showRescheduleForm(<?php echo $booking['id']; ?>)">Reschedule</button>
                                <?php elseif ($booking['status'] == 'accepted'): ?>
                                    <span class="text-muted">✓ Worker assigned</span>
                                <?php elseif ($booking['status'] == 'completed'): ?>
                                    <span class="text-success">✓ Completed</span>
                                    <a href="rate_worker.php?worker=<?php echo urlencode($booking['worker_email']); ?>&booking=<?php echo $booking['id']; ?>" class="btn btn-success btn-sm">
                                        ⭐ Rate Worker
                                    </a>
                                <?php elseif ($booking['status'] == 'cancelled'): ?>
                                    <span class="text-danger">✗ Cancelled</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Reschedule Form (hidden by default) -->
                        <div id="reschedule-<?php echo $booking['id']; ?>" class="reschedule-form hidden">
                            <h4>Reschedule Booking</h4>
                            <form method="POST" class="form-row">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <input type="hidden" name="action" value="reschedule">
                                <div class="form-group">
                                    <label>New Date:</label>
                                    <input type="date" name="new_date" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>New Time:</label>
                                    <input type="time" name="new_time" required>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success btn-sm">Update</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="hideRescheduleForm(<?php echo $booking['id']; ?>)">Cancel</button>
                                </div>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><ion-icon name="document-text-outline"></ion-icon></div>
                    <h4>No bookings yet</h4>
                    <p>You haven't made any bookings yet. Book your first service now!</p>
                    <a href="register.php" class="btn">Book Your First Service</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showRescheduleForm(bookingId) {
            document.getElementById('reschedule-' + bookingId).classList.remove('hidden');
        }
        
        function hideRescheduleForm(bookingId) {
            document.getElementById('reschedule-' + bookingId).classList.add('hidden');
        }
    </script>
</body>
</html>
