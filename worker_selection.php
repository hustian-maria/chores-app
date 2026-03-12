<?php

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
$user_name = $_SESSION['user_name'];

// Get selected service from form or session
$selected_service = $_GET['service'] ?? $_SESSION['selected_service'] ?? '';
if ($selected_service) {
    $_SESSION['selected_service'] = $selected_service;
}

// Get available workers with their ratings
$workers_query = "
    SELECT w.*, 
           COALESCE(AVG(r.rating), 0) as avg_rating, 
           COUNT(r.id) as total_ratings,
           GROUP_CONCAT(DISTINCT r.rating) as ratings_list
    FROM workers w
    LEFT JOIN ratings r ON w.email = r.worker_email
    WHERE w.skills LIKE ?
    GROUP BY w.email, w.name, w.email, w.phone, w.skills, w.created_at
    ORDER BY avg_rating DESC, w.name ASC
";

$stmt = $db->prepare($workers_query);
$service_pattern = "%{$selected_service}%";
$stmt->bind_param("s", $service_pattern);
$stmt->execute();
$workers = $stmt->get_result();

// Handle worker selection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_worker'])) {
    $_SESSION['selected_worker'] = $_POST['selected_worker'];
    header("Location: create_booking.php");
    exit();
}

// Get service pricing for display
$service_pricing = getServicePrice($selected_service);

// Function to get service icon
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Worker - HomeClean</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .worker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .worker-card {
            background: white;
            border: 1px solid #e3f2fd;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .worker-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 86, 179, 0.2);
            border-color: #0056b3;
        }
        
        .worker-card.selected {
            border-color: #0056b3;
            background: #f8f9ff;
            box-shadow: 0 5px 20px rgba(0, 86, 179, 0.2);
        }
        
        .worker-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .worker-avatar {
            width: 60px;
            height: 60px;
            background: #0056b3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .worker-info h3 {
            margin: 0 0 5px 0;
            color: #0056b3;
            font-size: 18px;
        }
        
        .worker-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .rating-section {
            margin: 15px 0;
        }
        
        .rating-stars {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .stars {
            color: #ffc107;
            font-size: 18px;
        }
        
        .rating-text {
            color: #666;
            font-size: 14px;
        }
        
        .skills-section {
            margin: 15px 0;
        }
        
        .skill-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .skill-tag {
            background: #e3f2fd;
            color: #0056b3;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .no-ratings {
            color: #999;
            font-style: italic;
            font-size: 14px;
        }
        
        .service-header {
            background: linear-gradient(135deg, #0056b3 0%, #004494 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .service-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .service-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        
        .back-btn {
            margin-bottom: 20px;
        }
        
        .select-worker-form {
            margin-top: 20px;
        }
        
        .btn-select {
            background: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-select:hover {
            background: #004494;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .worker-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
        <div class="service-header">
            <div class="service-icon"><?php echo getServiceIcon($selected_service); ?></div>
            <h2 class="service-title"><?php echo ucfirst(str_replace('_', ' ', $selected_service)); ?> Service</h2>
            <p>Select a worker for your <?php echo str_replace('_', ' ', $selected_service); ?> needs</p>
            <div class="pricing-info" style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-top: 15px; text-align: center;">
                <strong>Service Price:</strong> <?php echo $service_pricing['formatted_currency']; ?>
                <?php if ($service_pricing['urgent_surcharge'] > 0): ?>
                    <br><small style="color: #666;">+<?php echo formatPrice($service_pricing['urgent_surcharge']); ?> for same-day service</small>
                <?php endif; ?>
            </div>
        </div>

        <a href="register.php" class="back-btn btn btn-secondary">← Back to Service Selection</a>

        <?php if ($workers->num_rows > 0): ?>
            <div class="worker-grid">
                <?php while ($worker = $workers->fetch_assoc()): ?>
                    <div class="worker-card" onclick="selectWorker('<?php echo $worker['email']; ?>')">
                        <div class="worker-header">
                            <div class="worker-avatar">
                            <?php 
                                $profile_pic = $worker['profile_picture'] ?? null;
                                if ($profile_pic && file_exists('uploads/profile_pictures/' . $profile_pic)) {
                                    echo '<img src="uploads/profile_pictures/' . $profile_pic . '" alt="Profile Picture" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #0056b3;">';
                                } else {
                                    echo '<ion-icon name="person-outline" style="font-size: 40px; color: #666;"></ion-icon>';
                                }
                            ?>
                        </div>
                            <div class="worker-info">
                                <h3><?php echo htmlspecialchars($worker['name']); ?></h3>
                                <p><?php echo htmlspecialchars($worker['email']); ?></p>
                            </div>
                        </div>
                        
                        <div class="rating-section">
                            <div class="rating-stars">
                                <span class="stars">
                                    <?php 
                                    $rating = round($worker['avg_rating'], 1);
                                    $full_stars = floor($rating);
                                    $has_half_star = ($rating - $full_stars) >= 0.5;
                                    
                                    for ($i = 0; $i < $full_stars; $i++) {
                                        echo '⭐';
                                    }
                                    if ($has_half_star) {
                                        echo '⭐';
                                    }
                                    for ($i = $full_stars + ($has_half_star ? 1 : 0); $i < 5; $i++) {
                                        echo '☆';
                                    }
                                    ?>
                                </span>
                                <span class="rating-text">
                                    <?php 
                                    if ($worker['total_ratings'] > 0) {
                                        echo $rating . ' (' . $worker['total_ratings'] . ' reviews)';
                                    } else {
                                        echo '<span class="no-ratings">No ratings yet</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="skills-section">
                            <div class="skill-tags">
                                <?php 
                                $skills = explode(',', $worker['skills']);
                                foreach ($skills as $skill) {
                                    echo '<span class="skill-tag">' . ucfirst(trim($skill)) . '</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <form method="POST" class="select-worker-form" id="form-<?php echo $worker['email']; ?>">
                            <input type="hidden" name="selected_worker" value="<?php echo $worker['email']; ?>">
                            <button type="submit" class="btn-select">Select This Worker</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 20px;">🔍</div>
                <h3>No Workers Available</h3>
                <p>Sorry, no workers are currently available for <?php echo str_replace('_', ' ', $selected_service); ?> services.</p>
                <p>Please try a different service or check back later.</p>
                <br><br>
                <a href="register.php" class="btn">Try Different Service</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function selectWorker(email) {
            // Remove selected class from all cards
            document.querySelectorAll('.worker-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Scroll to form
            document.getElementById('form-' + email).scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
