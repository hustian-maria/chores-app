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
    
   
    if (empty($name)) {
        $message = "Name is required.";
        $message_type = "error";
    } else {
        
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
    <title>Settings - HomeClean</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .profile-picture-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }
        
        .current-picture {
            text-align: center;
        }
        
        .picture-display {
            margin-top: 10px;
            display: flex;
            justify-content: center;
        }
        
        .default-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .upload-section {
            text-align: center;
        }
        
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .upload-area:hover {
            border-color: #0056b3;
            background: #f0f8ff;
        }
        
        .upload-area.highlight {
            border-color: #0056b3;
            background: #e3f2fd;
        }
        
        .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .upload-progress {
            margin-top: 20px;
            text-align: center;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0056b3, #007BFF);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .upload-info {
            margin-top: 15px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .profile-picture-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .upload-area {
                padding: 30px 15px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"> HomeClean</div>
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
                            $all_skills = ['<ion-icon name="broom-outline"></ion-icon> Cleaning', '<ion-icon name="shirt-outline"></ion-icon> Laundry', '<ion-icon name="cart-outline"></ion-icon> Grocery Runs', '<ion-icon name="build-outline"></ion-icon> Minor Repairs', '<ion-icon name="child-outline"></ion-icon> Babysitting', '<ion-icon name="restaurant-outline"></ion-icon> Cooking'];
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

        <!-- Profile Picture Upload -->
        <div class="card" style="max-width: 800px; margin: 30px auto;">
            <h3>Profile Picture</h3>
            <div class="profile-picture-section">
                <div class="current-picture">
                    <label>Current Picture</label>
                    <div class="picture-display" id="currentPicture">
                        <?php 
                        $profile_pic = $user_data['profile_picture'] ?? null;
                        if ($profile_pic && file_exists('uploads/profile_pictures/' . $profile_pic)) {
                            echo '<div style="position: relative; display: inline-block;">
                                    <img src="uploads/profile_pictures/' . $profile_pic . '" alt="Profile Picture" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #0056b3;">
                                    <button type="button" onclick="deleteProfilePicture()" style="position: absolute; top: 0; right: 0; background: #dc3545; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                        <ion-icon name="trash-outline" style="color: white; font-size: 16px;"></ion-icon>
                                    </button>
                                  </div>';
                        } else {
                            echo '<div class="default-avatar" style="width: 120px; height: 120px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 3px solid #0056b3;"><ion-icon name="person-outline" style="font-size: 60px; color: #666;"></ion-icon></div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="upload-section">
                    <label>Upload New Picture</label>
                    <div class="upload-area" id="uploadArea">
                        <input type="file" id="profilePictureInput" accept="image/*" style="display: none;">
                        <div class="upload-content">
                            <ion-icon name="cloud-upload-outline" style="font-size: 48px; color: #0056b3; margin-bottom: 10px;"></ion-icon>
                            <p>Drag & drop your profile picture here</p>
                            <p style="font-size: 14px; color: #666; margin: 5px 0;">or</p>
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('profilePictureInput').click()">Browse Files</button>
                        </div>
                        <div class="upload-progress" id="uploadProgress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                            <p id="uploadStatus">Uploading...</p>
                        </div>
                    </div>
                    <div class="upload-info">
                        <small style="color: #666;">
                            Allowed formats: JPG, PNG, GIF, WebP<br>
                            Maximum size: 5MB<br>
                            Recommended: Square image (1:1 ratio)
                        </small>
                    </div>
                </div>
            </div>
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

        // Profile Picture Upload Functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('profilePictureInput');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const uploadStatus = document.getElementById('uploadStatus');
        const currentPicture = document.getElementById('currentPicture');

        if (uploadArea) {
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Highlight drop area when item is dragged over it
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                uploadArea.classList.add('highlight');
            }

            function unhighlight(e) {
                uploadArea.classList.remove('highlight');
            }

            // Handle dropped files
            uploadArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }

            // Handle file selection via input
            fileInput.addEventListener('change', function() {
                handleFiles(this.files);
            });

            function handleFiles(files) {
                if (files.length > 0) {
                    uploadFile(files[0]);
                }
            }

            function uploadFile(file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
                    return;
                }

                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('File too large. Maximum size is 5MB.');
                    return;
                }

                const formData = new FormData();
                formData.append('profile_picture', file);

                // Show progress
                uploadProgress.style.display = 'block';
                uploadStatus.textContent = 'Uploading...';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'upload_profile_picture.php', true);

                // Track upload progress
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressFill.style.width = percentComplete + '%';
                        uploadStatus.textContent = `Uploading... ${Math.round(percentComplete)}%`;
                    }
                });

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                uploadStatus.textContent = 'Upload successful!';
                                progressFill.style.width = '100%';
                                
                                // Update current picture display
                                setTimeout(() => {
                                    currentPicture.innerHTML = `
                                        <div style="position: relative; display: inline-block;">
                                            <img src="uploads/profile_pictures/${response.filename}" alt="Profile Picture" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #0056b3;">
                                            <button type="button" onclick="deleteProfilePicture()" style="position: absolute; top: 0; right: 0; background: #dc3545; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                                <ion-icon name="trash-outline" style="color: white; font-size: 16px;"></ion-icon>
                                            </button>
                                        </div>
                                    `;
                                    uploadProgress.style.display = 'none';
                                    progressFill.style.width = '0%';
                                }, 1000);
                            } else {
                                uploadStatus.textContent = response.message || 'Upload failed';
                                uploadProgress.style.display = 'block';
                            }
                        } catch (e) {
                            uploadStatus.textContent = 'Upload failed';
                            uploadProgress.style.display = 'block';
                        }
                    } else {
                        uploadStatus.textContent = 'Upload failed';
                        uploadProgress.style.display = 'block';
                    }
                };

                xhr.onerror = function() {
                    uploadStatus.textContent = 'Upload failed';
                    uploadProgress.style.display = 'block';
                };

                xhr.send(formData);
            }
        }

        // Delete Profile Picture Function
        function deleteProfilePicture() {
            if (confirm('Are you sure you want to delete your profile picture? This action cannot be undone.')) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_profile_picture.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Update current picture display to show default avatar
                                currentPicture.innerHTML = `
                                    <div class="default-avatar" style="width: 120px; height: 120px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 3px solid #0056b3;">
                                        <ion-icon name="person-outline" style="font-size: 60px; color: #666;"></ion-icon>
                                    </div>
                                `;
                                alert('Profile picture deleted successfully!');
                            } else {
                                alert(response.message || 'Failed to delete profile picture');
                            }
                        } catch (e) {
                            alert('Failed to delete profile picture');
                        }
                    } else {
                        alert('Failed to delete profile picture');
                    }
                };
                
                xhr.onerror = function() {
                    alert('Failed to delete profile picture');
                };
                
                xhr.send();
            }
        }
    </script>
</body>
</html>
