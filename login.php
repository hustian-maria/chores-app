<?php
// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

// Start session
session_start();

// Initialize message variables
$message = "";
$message_type = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        if ($form_type == 'user') {
            // Handle user login
            $query = $db->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $query->bind_param("s", $email);
            $query->execute();
            $result = $query->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_id'] = $user['id'];
                    
                    $message = "Login successful! Redirecting to your dashboard...";
                    $message_type = "success";
                    
                    // Redirect to user dashboard after 2 seconds
                    header("refresh:2;url=user_dashboard.php");
                } else {
                    $message = "Incorrect password. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "No user found with this email address.";
                $message_type = "error";
            }
            
        } elseif ($form_type == 'worker') {
            // Handle worker login
            $query = $db->prepare("SELECT id, name, email, password, skills FROM workers WHERE email = ?");
            $query->bind_param("s", $email);
            $query->execute();
            $result = $query->get_result();
            
            if ($result->num_rows > 0) {
                $worker = $result->fetch_assoc();
                
                if (password_verify($password, $worker['password'])) {
                    $_SESSION['worker_email'] = $worker['email'];
                    $_SESSION['worker_name'] = $worker['name'];
                    $_SESSION['worker_id'] = $worker['id'];
                    $_SESSION['worker_skills'] = $worker['skills'];
                    
                    $message = "Login successful! Redirecting to your dashboard...";
                    $message_type = "success";
                    
                    // Redirect to worker dashboard after 2 seconds
                    header("refresh:2;url=worker_dashboard.php");
                } else {
                    $message = "Incorrect password. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "No worker found with this email address.";
                $message_type = "error";
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
    <title>Login - Household Services</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">🏠 Household Services</div>
            <nav>
                <ul>
                    <li><a href="user_register.html">User Register</a></li>
                    <li><a href="worker_register.html">Worker Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h2 class="text-center mb-20">Login Result</h2>
            
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
                        <br><br>
                        <p>
                            <a href="user_login.html">User Login</a> | 
                            <a href="worker_login.html">Worker Login</a> | 
                            <a href="user_register.html">Register</a>
                        </p>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
