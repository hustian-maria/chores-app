<?php
// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

// Helper function to get service icon
function getServiceIcon($service) {
    $icons = [
        'cleaning' => '<ion-icon name="broom-outline"></ion-icon>',
        'laundry' => '<ion-icon name="shirt-outline"></ion-icon>',
        'grocery_runs' => '<ion-icon name="cart-outline"></ion-icon>',
        'minor_repairs' => '<ion-icon name="build-outline"></ion-icon>',
        'babysitting' => '<ion-icon name="child-outline"></ion-icon>',
        'cooking' => '<ion-icon name="restaurant-outline"></ion-icon>'
    ];
    return $icons[$service] ?? '<ion-icon name="home-outline"></ion-icon>';
}

// Include pricing helper
require_once 'pricing_helper.php';

// Start session and check if worker is logged in
session_start();
if (!isset($_SESSION['worker_email'])) {
    header("Location: login.php");
    exit();
}

$worker_email = $_SESSION['worker_email'];
$worker_name = $_SESSION['worker_name'];
$worker_skills = $_SESSION['worker_skills'] ?? '';

// If worker_skills is empty, get it from database
if (empty($worker_skills)) {
    $worker_query = $db->prepare("SELECT skills FROM workers WHERE email = ?");
    $worker_query->bind_param("s", $worker_email);
    $worker_query->execute();
    $worker_result = $worker_query->get_result();
    if ($worker_result->num_rows > 0) {
        $worker_data = $worker_result->fetch_assoc();
        $worker_skills = $worker_data['skills'];
        $_SESSION['worker_skills'] = $worker_skills;
    }
}

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
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .job-card {
            background: white;
            border: 1px solid #e3f2fd;
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 86, 179, 0.15);
            border-color: #0056b3;
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .service-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .service-icon {
            font-size: 32px;
        }
        
        .service-info h4 {
            margin: 0;
            color: #0056b3;
            font-size: 18px;
        }
        
        .customer-name {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .job-priority {
            text-align: right;
        }
        
        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .priority-badge.urgent {
            background: #dc3545;
            color: white;
        }
        
        .priority-badge.normal {
            background: #6c757d;
            color: white;
        }
        
        .job-details {
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        
        .pay-amount {
            color: #28a745;
            font-weight: 700;
            font-size: 16px;
        }
        
        .job-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        @media (max-width: 768px) {
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            
            .job-card {
                padding: 15px;
            }
            
            .job-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .job-actions {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><ion-icon name="home-outline"></ion-icon> Household Services</div>
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
                        <?php 
                            $profile_pic = $worker['profile_picture'] ?? null;
                            if ($profile_pic && file_exists('uploads/profile_pictures/' . $profile_pic)) {
                                echo '<img src="uploads/profile_pictures/' . $profile_pic . '" alt="Profile Picture" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #0056b3;">';
                            } else {
                                echo '<ion-icon name="person-outline" style="font-size: 40px; color: #666;"></ion-icon>';
                            }
                        ?>
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
                    <div class="action-icon"><ion-icon name="refresh-outline"></ion-icon></div>
                    <h4>Refresh Jobs</h4>
                    <p>Check for new opportunities</p>
                </div>
                <div class="action-card" onclick="showEarnings()">
                    <div class="action-icon"><ion-icon name="wallet-outline"></ion-icon></div>
                    <h4>My Earnings</h4>
                    <p>View your income summary</p>
                </div>
                <div class="action-card" onclick="showCalendar()">
                    <div class="action-icon"><ion-icon name="calendar-outline"></ion-icon></div>
                    <h4>Work Calendar</h4>
                    <p>View your schedule</p>
                </div>
                <a href="logout.php" class="action-card">
                    <div class="action-icon"><ion-icon name="log-out-outline"></ion-icon></div>
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
                    <h4>XAF<?php echo number_format($completed_count * 50, 0); ?></h4>
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
                <div class="jobs-grid">
                    <?php while ($job = $available_jobs_result->fetch_assoc()): ?>
                        <div class="job-card">
                            <div class="job-header">
                                <div class="service-info">
                                    <span class="service-icon"><?php echo getServiceIcon($job['service']); ?></span>
                                    <div>
                                        <h4><?php echo htmlspecialchars(ucfirst($job['service'])); ?></h4>
                                        <p class="customer-name"><ion-icon name="person-outline"></ion-icon> <?php echo htmlspecialchars($job['user_name']); ?></p>
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
                                    <span class="detail-label"><ion-icon name="calendar-outline"></ion-icon> Date:</span>
                                    <span><?php echo date('M d, Y', strtotime($job['date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><ion-icon name="time-outline"></ion-icon> Time:</span>
                                    <span><?php echo date('h:i A', strtotime($job['time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><ion-icon name="call-outline"></ion-icon> Phone:</span>
                                    <span><?php echo htmlspecialchars($job['user_phone'] ?: 'Not provided'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><ion-icon name="cash-outline"></ion-icon> Est. Pay:</span>
                                    <span class="pay-amount"><?php 
                                        $is_urgent = isUrgentJob($job['date']);
                                        $pricing = getServicePrice($job['service'], $is_urgent);
                                        echo $pricing['formatted_currency'];
                                    ?></span>
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
                    <div class="empty-icon"><ion-icon name="search-outline"></ion-icon></div>
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
                                        <br><br>
                                        <a href="rate_worker.php?worker=<?php echo urlencode($worker_email); ?>&booking=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm">
                                            ⭐ Rate Worker
                                        </a>
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
