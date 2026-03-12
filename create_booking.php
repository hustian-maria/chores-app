<?php
// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}


require_once 'pricing_helper.php';


session_start();

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}


$user_email = $_SESSION['user_email'];
$selected_service = $_SESSION['selected_service'] ?? '';
$selected_date = $_SESSION['selected_date'] ?? '';
$selected_time = $_SESSION['selected_time'] ?? '';
$selected_worker = $_SESSION['selected_worker'] ?? '';


if (empty($selected_service) || empty($selected_date) || empty($selected_time) || empty($selected_worker)) {
    header("Location: register.php");
    exit();
}

// Get service pricing
$service_pricing = getServicePrice($selected_service);

// Create booking
$insert_booking = $db->prepare("INSERT INTO bookings (user_email, service, date, time, worker_email) VALUES (?, ?, ?, ?, ?)");
$insert_booking->bind_param("sssss", $user_email, $selected_service, $selected_date, $selected_time, $selected_worker);

if ($insert_booking->execute()) {
    unset($_SESSION['selected_service']);
    unset($_SESSION['selected_date']);
    unset($_SESSION['selected_time']);
    unset($_SESSION['selected_worker']);
    
    $message = "Booking created successfully! Your service has been scheduled.";
    $message_type = "success";
} else {
    $message = "Failed to create booking. Please try again.";
    $message_type = "error";
}

// Get worker name for display
$worker_query = $db->prepare("SELECT name FROM workers WHERE email = ?");
$worker_query->bind_param("s", $selected_worker);
$worker_query->execute();
$worker_result = $worker_query->get_result();
$worker_name = $worker_result->num_rows > 0 ? $worker_result->fetch_assoc()['name'] : 'Selected Worker';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - HomeClean</title>
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
                    <li><a href="register.php">Book Service</a></li>
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h2 class="text-center mb-20">Booking Confirmation</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message_type == 'success'): ?>
                <div class="booking-confirmation" style="text-align: center; padding: 30px 0;">
                    <div style="font-size: 48px; margin-bottom: 20px; color: #28a745;">
                        <ion-icon name="checkmark-circle-outline"></ion-icon>
                    </div>
                    <h3 style="color: #0056b3; margin-bottom: 15px;">Service Booked Successfully!</h3>
                    
                    <div class="booking-details" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: left;">
                        <div style="margin-bottom: 10px;">
                            <strong>Service:</strong> <?php echo ucfirst(str_replace('_', ' ', $selected_service)); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($selected_date)); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Time:</strong> <?php echo date('g:i A', strtotime($selected_time)); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Worker:</strong> <?php echo htmlspecialchars($worker_name); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Service Price:</strong> <?php echo $service_pricing['formatted_currency']; ?>
                        </div>
                        <?php if ($service_pricing['urgent_surcharge'] > 0): ?>
                            <div style="margin-bottom: 10px;">
                                <strong>Same-day Surcharge:</strong> <?php echo formatPrice($service_pricing['urgent_surcharge']); ?>
                            </div>
                        <?php endif; ?>
                        <div style="margin-bottom: 10px;">
                            <strong>Total Cost:</strong> <?php echo $service_pricing['formatted_currency']; ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <p><strong>What's Next?</strong></p>
                        <p>Your booking request has been sent to the worker. They will review it and accept or decline the request.</p>
                        <p>You can track the status of your booking in your dashboard.</p>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <a href="user_dashboard.php" class="btn">Go to Dashboard</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center mt-20">
                    <a href="worker_selection.php?service=<?php echo urlencode($selected_service); ?>" class="btn">Try Again</a>
                    <br><br>
                    <a href="user_dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
