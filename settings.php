<?php
// Start session
session_start();

// Check if user is logged in (either user or worker)
if (!isset($_SESSION['user_email']) && !isset($_SESSION['worker_email'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

// Initialize variables
$is_worker = isset($_SESSION['worker_email']);
$message = "";
$message_type = "";

// Get current user data
if ($is_worker) {
    $email = $_SESSION['worker_email'];
    $query = $db->prepare("SELECT * FROM workers WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $user_data = $query->get_result()->fetch_assoc();
    $dashboard_url = "worker_dashboard.php";
} else {
    $email = $_SESSION['user_email'];
    $query = $db->prepare("SELECT * FROM users WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $user_data = $query->get_result()->fetch_assoc();
    $dashboard_url = "user_dashboard.php";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    if (empty($name)) {
        $message = "Name is required.";
        $message_type = "error";
    } else {
        // Update basic info
        if ($is_worker) {
            $update_query = $db->prepare("UPDATE workers SET name = ?, phone = ? WHERE email = ?");
            $update_query->bind_param("sss", $name, $phone, $email);
        } else {
            $address = trim($_POST['address'] ?? '');
            $update_query = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE email = ?");
            $update_query->bind_param("ssss", $name, $phone, $address, $email);
        }
        
        if ($update_query->execute()) {
            // Update session name
            if ($is_worker) {
                $_SESSION['worker_name'] = $name;
            } else {
                $_SESSION['user_name'] = $name;
            }
            
            $message = "Profile updated successfully!";
            $message_type = "success";
            
            // Handle password change if provided
            if (!empty($current_password) && !empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $message = "New passwords do not match.";
                    $message_type = "error";
                } elseif (strlen($new_password) < 6) {
                    $message = "New password must be at least 6 characters long.";
                    $message_type = "error";
                } else {
                    // Verify current password
                    if (password_verify($current_password, $user_data['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        if ($is_worker) {
                            $password_query = $db->prepare("UPDATE workers SET password = ? WHERE email = ?");
                            $password_query->bind_param("ss", $hashed_password, $email);
                        } else {
                            $password_query = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                            $password_query->bind_param("ss", $hashed_password, $email);
                        }
                        
                        if ($password_query->execute()) {
                            $message = "Profile and password updated successfully!";
                            $message_type = "success";
                        } else {
                            $message = "Profile updated but password change failed.";
                            $message_type = "error";
                        }
                    } else {
                        $message = "Current password is incorrect.";
                        $message_type = "error";
                    }
                }
            }
            
            // Refresh user data
            if ($is_worker) {
                $query = $db->prepare("SELECT * FROM workers WHERE email = ?");
                $query->bind_param("s", $email);
                $query->execute();
                $user_data = $query->get_result()->fetch_assoc();
            } else {
                $query = $db->prepare("SELECT * FROM users WHERE email = ?");
                $query->bind_param("s", $email);
                $query->execute();
                $user_data = $query->get_result()->fetch_assoc();
            }
        } else {
            $message = "Failed to update profile. Please try again.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Household Services</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">🏠 Household Services</div>
            <nav>
                <ul>
                    <li><a href="<?php echo $dashboard_url; ?>">Dashboard</a></li>
                    <li><a href="settings.php" class="active">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <h2 class="text-center mb-20">
                <?php echo $is_worker ? 'Worker' : 'User'; ?> Settings
            </h2>

            <form method="POST" action="">
                <h3>Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                        <small>Email cannot be changed. Contact support if needed.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                    </div>
                    <?php if (!$is_worker): ?>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_worker): ?>
                    <h3>Skills</h3>
                    <div class="form-group">
                        <label>Your Current Skills</label>
                        <div class="checkbox-group">
                            <?php
                            $all_skills = ['Cleaning', 'Laundry', 'Grocery Runs', 'Minor Repairs', 'Babysitting', 'Cooking'];
                            $worker_skills = explode(',', $user_data['skills']);
                            
                            foreach ($all_skills as $skill) {
                                $has_skill = in_array(trim($skill), $worker_skills);
                                echo '<div class="checkbox-item">';
                                echo '<span style="font-size: 20px; margin-right: 5px;">' . 
                                     ($has_skill ? '✅' : '❌') . '</span>';
                                echo '<span>' . htmlspecialchars($skill) . '</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <small>To update your skills, please contact support.</small>
                    </div>
                <?php endif; ?>

                <h3>Change Password</h3>
                <p class="mb-20">Leave blank if you don't want to change your password</p>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Update Settings</button>
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Account Information -->
        <div class="card" style="max-width: 800px; margin: 30px auto;">
            <h3>Account Information</h3>
            <div class="profile-info-grid">
                <div class="info-item">
                    <label>Account Type</label>
                    <p><?php echo $is_worker ? 'Worker' : 'Client'; ?></p>
                </div>
                <div class="info-item">
                    <label>Member Since</label>
                    <p><?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></p>
                </div>
                <div class="info-item">
                    <label>Last Updated</label>
                    <p><?php echo date('F j, Y g:i A', strtotime($user_data['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Only require password fields if current password is entered
        document.getElementById('current_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (this.value) {
                newPassword.setAttribute('required', '');
                confirmPassword.setAttribute('required', '');
            } else {
                newPassword.removeAttribute('required');
                confirmPassword.removeAttribute('required');
            }
        });
    </script>
</body>
</html>
