<?php

$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

session_start();

// Initialize message variables
$message = "";
$message_type = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'] ?? '';
    
    if ($form_type == 'user') {
        // Handle user registration and booking
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $service = $_POST['service'] ?? '';
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($password) || empty($service) || empty($date) || empty($time)) {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $message_type = "error";
        } else {
            // Check if user already exists
            $check_user = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check_user->bind_param("s", $email);
            $check_user->execute();
            $result = $check_user->get_result();
            
            if ($result->num_rows > 0) {
                $message = "A user with this email already exists.";
                $message_type = "error";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $insert_user = $db->prepare("INSERT INTO users (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)");
                $insert_user->bind_param("sssss", $name, $email, $phone, $address, $hashed_password);
                
                if ($insert_user->execute()) {
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['selected_service'] = $service;
                    $_SESSION['selected_date'] = $date;
                    $_SESSION['selected_time'] = $time;
                    $message = "Registration successful! Redirecting to worker selection...";
                    $message_type = "success";
                    
                    // Redirect to worker selection after 2 seconds
                    header("refresh:2;url=worker_selection.php?service=" . urlencode($service));
                } else {
                    $message = "Registration successful but failed to proceed. Please contact support.";
                    $message_type = "error";
                }
            }
        }
        
    } elseif ($form_type == 'worker') {
        // Handle worker registration
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $skills = isset($_POST['skills']) ? implode(',', $_POST['skills']) : '';
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($password) || empty($skills)) {
            $message = "Please fill in all required fields and select at least one skill.";
            $message_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $message_type = "error";
        } else {
            // Check if worker already exists
            $check_worker = $db->prepare("SELECT id FROM workers WHERE email = ?");
            $check_worker->bind_param("s", $email);
            $check_worker->execute();
            $result = $check_worker->get_result();
            
            if ($result->num_rows > 0) {
                $message = "A worker with this email already exists.";
                $message_type = "error";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert worker
                $insert_worker = $db->prepare("INSERT INTO workers (name, email, phone, password, skills) VALUES (?, ?, ?, ?, ?)");
                $insert_worker->bind_param("sssss", $name, $email, $phone, $hashed_password, $skills);
                
                if ($insert_worker->execute()) {
                    $_SESSION['worker_email'] = $email;
                    $_SESSION['worker_name'] = $name;
                    $message = "Worker registration successful!";
                    $message_type = "success";
                    
                    // Redirect to worker dashboard after 2 seconds
                    header("refresh:2;url=worker_dashboard.php");
                } else {
                    $message = "Registration failed. Please try again.";
                    $message_type = "error";
                }
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
    <title>Registration - HomeClean</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"> HomeClean</div>
            <nav>
                <ul>
                    <li><a href="user_login.html">User Login</a></li>
                    <li><a href="worker_login.html">Worker Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h2 class="text-center mb-20">Registration Result</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-20">
                <p>
                    <?php if ($message_type == 'success'): ?>
                        Redirecting to your dashboard...
                    <?php else: ?>
                        <a href="javascript:history.back()" class="btn">Go Back</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
