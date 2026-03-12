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

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get current profile picture from database
    $table = $user_type === 'user' ? 'users' : 'workers';
    $get_query = $db->prepare("SELECT profile_picture FROM $table WHERE email = ?");
    $get_query->bind_param("s", $email);
    $get_query->execute();
    $result = $get_query->get_result();
    $user_data = $result->fetch_assoc();
    
    $current_picture = $user_data['profile_picture'] ?? null;
    
    if ($current_picture) {
        // Delete file from server
        $file_path = 'uploads/profile_pictures/' . $current_picture;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Update database to remove profile picture
        $update_query = $db->prepare("UPDATE $table SET profile_picture = NULL WHERE email = ?");
        $update_query->bind_param("s", $email);
        
        if ($update_query->execute()) {
           
            unset($_SESSION[$user_type . '_profile_picture']);
            
            header("Content-Type: application/json");
            echo json_encode([
                'success' => true, 
                'message' => 'Profile picture deleted successfully'
            ]);
        } else {
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'No profile picture to delete']);
    }
} else {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
