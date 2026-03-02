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

// Start session and check if worker is logged in
session_start();
if (!isset($_SESSION['worker_email'])) {
    header("Location: worker_login.html");
    exit();
}

$worker_email = $_SESSION['worker_email'];
$worker_name = $_SESSION['worker_name'];
$worker_skills = $_SESSION['worker_skills'];

// Get worker details
$worker_query = $db->prepare("SELECT * FROM workers WHERE email = ?");
$worker_query->bind_param("s", $worker_email);
$worker_query->execute();
$worker_result = $worker_query->get_result();
$worker = $worker_result->fetch_assoc();

// Get available jobs matching worker's skills
$skills_array = explode(',', $worker_skills);
$skills_placeholders = str_repeat('?,', count($skills_array) - 1) . '?';
$available_jobs_query = $db->prepare("
    SELECT b.*, u.name as user_name, u.phone as user_phone 
    FROM bookings b 
    JOIN users u ON b.user_email = u.email 
    WHERE b.status = 'pending' 
    AND b.service IN ($skills_placeholders)
    ORDER BY b.date ASC, b.time ASC
");
$available_jobs_query->bind_param(str_repeat('s', count($skills_array)), ...$skills_array);
$available_jobs_query->execute();
$available_jobs_result = $available_jobs_query->get_result();

// Get worker's assigned jobs
$assigned_jobs_query = $db->prepare("
    SELECT b.*, u.name as user_name, u.phone as user_phone 
    FROM bookings b 
    JOIN users u ON b.user_email = u.email 
    WHERE b.worker_email = ? 
    ORDER BY b.date DESC, b.time DESC
");
$assigned_jobs_query->bind_param("s", $worker_email);
$assigned_jobs_query->execute();
$assigned_jobs_result = $assigned_jobs_query->get_result();

// Handle job actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        $booking_id = $_POST['booking_id'];
        $action = $_POST['action'];
        
        if ($action == 'accept') {
            $update_query = $db->prepare("UPDATE bookings SET status = 'accepted', worker_email = ? WHERE id = ? AND status = 'pending'");
            $update_query->bind_param("si", $worker_email, $booking_id);
            $update_query->execute();
            
            // Refresh the page to show updated status
            header("Refresh:0");
            exit();
        } elseif ($action == 'decline') {
            // Just refresh - the job remains available for other workers
            header("Refresh:0");
            exit();
        } elseif ($action == 'complete') {
            $update_query = $db->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND worker_email = ?");
            $update_query->bind_param("is", $booking_id, $worker_email);
            $update_query->execute();
            
            // Refresh the page to show updated status
            header("Refresh:0");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - Household Services</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">🏠 Household Services</div>
            <nav>
                <ul>
                    <li><a href="worker_dashboard.php">Dashboard</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($worker_name); ?>!</h1>
            <div class="dashboard-nav">
                <span class="badge">Worker</span>
            </div>
        </div>

        <!-- Worker Profile Information -->
        <div class="card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-circle">🔧</div>
                </div>
                <div class="profile-details">
                    <h2><?php echo htmlspecialchars($worker_name); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($worker['email']); ?></p>
                    <div class="profile-stats">
                        <span class="stat-badge">Worker since <?php echo date('M Y', strtotime($worker['created_at'])); ?></span>
                        <span class="stat-badge"><?php echo count(explode(',', $worker['skills'])); ?> Skills</span>
                    </div>
                </div>
                <div class="profile-actions">
                    <div class="worker-rating">
                        <span class="rating-stars">⭐⭐⭐⭐⭐</span>
                        <span class="rating-text">4.9 Rating</span>
                    </div>
                </div>
            </div>
            <div class="profile-info-grid">
                <div class="info-item">
                    <label>Phone</label>
                    <p><?php echo htmlspecialchars($worker['phone'] ?: 'Not provided'); ?></p>
                </div>
                <div class="info-item">
                    <label>Skills</label>
                    <p><?php echo htmlspecialchars(str_replace(',', ', ', $worker['skills'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3>Quick Actions</h3>
            <div class="quick-actions-grid">
                <div class="action-card" onclick="refreshJobs()">
                    <div class="action-icon">🔄</div>
                    <h4>Refresh Jobs</h4>
                    <p>Check for new opportunities</p>
                </div>
                <div class="action-card" onclick="showEarnings()">
                    <div class="action-icon">💰</div>
                    <h4>My Earnings</h4>
                    <p>View your income summary</p>
                </div>
                <div class="action-card" onclick="showCalendar()">
                    <div class="action-icon">📅</div>
                    <h4>Work Calendar</h4>
                    <p>View your schedule</p>
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
            <h3>Your Work Overview</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <h4><?php echo $available_jobs_result->num_rows; ?></h4>
                    <p>Available Jobs</p>
                </div>
                <?php
                // Count assigned jobs by status
                $accepted_count = 0;
                $completed_count = 0;
                
                $assigned_jobs_result->data_seek(0); // Reset result pointer
                while ($job = $assigned_jobs_result->fetch_assoc()) {
                    if ($job['status'] == 'accepted') {
                        $accepted_count++;
                    } elseif ($job['status'] == 'completed') {
                        $completed_count++;
                    }
                }
                ?>
                <div class="stat-card">
                    <h4><?php echo $accepted_count; ?></h4>
                    <p>Active Jobs</p>
                </div>
                <div class="stat-card">
                    <h4><?php echo $completed_count; ?></h4>
                    <p>Completed Jobs</p>
                </div>
                <div class="stat-card">
                    <h4>$<?php echo number_format($completed_count * 50, 0); ?></h4>
                    <p>Est. Earnings</p>
                </div>
            </div>
        </div>

        <!-- Available Jobs -->
        <div class="card">
            <div class="card-header">
                <h3>Available Jobs (Matching Your Skills)</h3>
                <div class="filter-tabs">
                    <button class="tab-btn active" onclick="filterJobs('all')">All Jobs</button>
                    <button class="tab-btn" onclick="filterJobs('urgent')">Urgent</button>
                    <button class="tab-btn" onclick="filterJobs('today')">Today</button>
                </div>
            </div>
            
            <?php if ($available_jobs_result->num_rows > 0): ?>
                <div class="jobs-container">
                    <?php while ($job = $available_jobs_result->fetch_assoc()): ?>
                        <div class="job-card">
                            <div class="job-header">
                                <div class="service-info">
                                    <span class="service-icon"><?php echo getServiceIcon($job['service']); ?></span>
                                    <div>
                                        <h4><?php echo htmlspecialchars(ucfirst($job['service'])); ?></h4>
                                        <p class="customer-name">👤 <?php echo htmlspecialchars($job['user_name']); ?></p>
                                    </div>
                                </div>
                                <div class="job-priority">
                                    <?php if (strtotime($job['date']) <= strtotime('+1 day')): ?>
                                        <span class="priority-badge urgent">Urgent</span>
                                    <?php else: ?>
                                        <span class="priority-badge normal">Standard</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="job-details">
                                <div class="detail-item">
                                    <span class="detail-label">📅 Date:</span>
                                    <span><?php echo date('M d, Y', strtotime($job['date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">⏰ Time:</span>
                                    <span><?php echo date('h:i A', strtotime($job['time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📞 Phone:</span>
                                    <span><?php echo htmlspecialchars($job['user_phone'] ?: 'Not provided'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">💰 Est. Pay:</span>
                                    <span class="pay-amount">$<?php echo rand(30, 80); ?></span>
                                </div>
                            </div>
                            <div class="job-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $job['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to accept this job?')">
                                        ✓ Accept Job
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $job['id']; ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" class="btn btn-secondary btn-sm">Skip</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h4>No available jobs</h4>
                    <p>No new jobs matching your skills right now. Check back later!</p>
                    <button class="btn" onclick="refreshJobs()">Refresh Jobs</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Your Assigned Jobs -->
        <div class="card">
            <h3>Your Assigned Jobs</h3>
            
            <?php if ($assigned_jobs_result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Customer Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $assigned_jobs_result->data_seek(0); // Reset result pointer
                        while ($job = $assigned_jobs_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars(ucfirst($job['service'])); ?></td>
                                <td><?php echo htmlspecialchars($job['user_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($job['date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($job['time'])); ?></td>
                                <td><?php echo htmlspecialchars($job['user_phone'] ?: 'Not provided'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $job['status']; ?>">
                                        <?php echo ucfirst($job['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($job['status'] == 'accepted'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $job['id']; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure this job is completed?')">Mark Complete</button>
                                        </form>
                                    <?php elseif ($job['status'] == 'completed'): ?>
                                        <span class="text-success">✓ Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>You haven't accepted any jobs yet. Check the available jobs above!</p>
            <?php endif; ?>
        </div>
    </div>

    <script>    </script>
</body>
</html>
