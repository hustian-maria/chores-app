<?php
// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_email']) && !isset($_SESSION['worker_email'])) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Determine user type and email
$user_type = isset($_SESSION['user_email']) ? 'user' : 'worker';
$email = $_SESSION['user_email'] ?? $_SESSION['worker_email'];

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
        exit();
    }
    
    if ($file['size'] > $max_size) {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
        exit();
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $table = $user_type === 'user' ? 'users' : 'workers';
        $update_query = $db->prepare("UPDATE $table SET profile_picture = ? WHERE email = ?");
        $update_query->bind_param("ss", $filename, $email);
        
        if ($update_query->execute()) {
            // Update session
            $_SESSION[$user_type . '_profile_picture'] = $filename;
            
            header("Content-Type: application/json");
            echo json_encode([
                'success' => true, 
                'message' => 'Profile picture updated successfully',
                'filename' => $filename,
                'url' => $upload_dir . $filename
            ]);
        } else {
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
    
    // Clean up old profile picture if exists
    if (isset($_SESSION[$user_type . '_profile_picture']) && $_SESSION[$user_type . '_profile_picture']) {
        $old_file = $upload_dir . $_SESSION[$user_type . '_profile_picture'];
        if (file_exists($old_file) && $_SESSION[$user_type . '_profile_picture'] !== $filename) {
            unlink($old_file);
        }
    }
} else {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}
?>
