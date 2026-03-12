<?php
// Database connection
$db = new mysqli("localhost", "root", "", "chores_app");
if ($db->connect_error) { 
    die("Connection failed: " . $db->connect_error); 
}

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'];

// Get worker and booking details from URL
$worker_email = $_GET['worker'] ?? '';
$booking_id = $_GET['booking'] ?? '';

if (empty($worker_email) || empty($booking_id)) {
    header("Location: user_dashboard.php");
    exit();
}

// Get worker and booking details
$worker_query = $db->prepare("
    SELECT w.name as worker_name, w.email as worker_email, 
           b.service, b.date, b.time, b.id as booking_id
    FROM workers w
    JOIN bookings b ON w.email = b.worker_email
    WHERE w.email = ? AND b.id = ? AND b.user_email = ? AND b.status = 'completed'
");

$worker_query->bind_param("sss", $worker_email, $booking_id, $user_email);
$worker_query->execute();
$result = $worker_query->get_result();

if ($result->num_rows === 0) {
    header("Location: user_dashboard.php");
    exit();
}

$booking = $result->fetch_assoc();

// Handle rating submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = $_POST['rating'] ?? '';
    $review = trim($_POST['review'] ?? '');
    
    if (empty($rating) || $rating < 1 || $rating > 5) {
        $error = "Please select a valid rating between 1 and 5 stars.";
    } else {
        // Check if already rated
        $check_rating = $db->prepare("SELECT id FROM ratings WHERE user_email = ? AND worker_email = ?");
        $check_rating->bind_param("ss", $user_email, $worker_email);
        $check_rating->execute();
        $existing_rating = $check_rating->get_result();
        
        if ($existing_rating->num_rows > 0) {
            $error = "You have already rated this worker.";
        } else {
            // Insert rating
            $insert_rating = $db->prepare("INSERT INTO ratings (user_email, worker_email, rating, review) VALUES (?, ?, ?, ?)");
            $insert_rating->bind_param("ssis", $user_email, $worker_email, $rating, $review);
            
            if ($insert_rating->execute()) {
                $success = "Thank you! Your rating has been submitted successfully.";
                
                // Update worker's average rating display
                header("refresh:2;url=user_dashboard.php");
            } else {
                $error = "Failed to submit rating. Please try again.";
            }
        }
    }
}

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
    <title>Rate Worker - HomeCean</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .rating-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .worker-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .worker-avatar {
            width: 80px;
            height: 80px;
            background: #0056b3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            margin: 0 auto 15px;
        }
        
        .worker-name {
            font-size: 24px;
            font-weight: 700;
            color: #0056b3;
            margin: 0 0 10px 0;
        }
        
        .service-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin: 15px 0;
            color: #666;
        }
        
        .rating-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            border: 1px solid #e3f2fd;
        }
        
        .rating-stars-input {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0;
        }
        
        .star-input {
            font-size: 48px;
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
            background: none;
            border: none;
        }
        
        .star-input:hover,
        .star-input.active {
            color: #ffc107;
        }
        
        .review-section {
            margin: 30px 0;
        }
        
        .review-textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            font-size: 16px;
            resize: vertical;
            font-family: inherit;
        }
        
        .review-textarea:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }
        
        .submit-section {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn-rate {
            background: #0056b3;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-rate:hover {
            background: #004494;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 86, 179, 0.4);
        }
        
        .rating-label {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .rating-container {
                padding: 10px;
            }
            
            .star-input {
                font-size: 36px;
            }
            
            .btn-rate {
                width: 100%;
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">🏠 Household Services</div>
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
        <div class="rating-container">
            <div class="worker-info">
                <div class="worker-avatar">
                    <?php 
                        $profile_pic = $worker_data['profile_picture'] ?? null;
                        if ($profile_pic && file_exists('uploads/profile_pictures/' . $profile_pic)) {
                            echo '<img src="uploads/profile_pictures/' . $profile_pic . '" alt="Profile Picture" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #0056b3;">';
                        } else {
                            echo '<ion-icon name="person-outline" style="font-size: 40px; color: #666;"></ion-icon>';
                        }
                    ?>
                </div>
                <h2 class="worker-name"><?php echo htmlspecialchars($booking['worker_name']); ?></h2>
                
                <div class="service-info">
                    <span><?php echo getServiceIcon($booking['service']); ?></span>
                    <span><?php echo ucfirst(str_replace('_', ' ', $booking['service'])); ?></span>
                    <span><?php echo date('M j, Y', strtotime($booking['date'])); ?></span>
                    <span><?php echo date('g:i A', strtotime($booking['time'])); ?></span>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($success)): ?>
                <form method="POST" class="rating-form">
                    <h3 class="rating-label">How was your experience?</h3>
                    
                    <div class="rating-stars-input">
                        <button type="button" class="star-input" data-rating="1" onclick="setRating(1)">☆</button>
                        <button type="button" class="star-input" data-rating="2" onclick="setRating(2)">☆</button>
                        <button type="button" class="star-input" data-rating="3" onclick="setRating(3)">☆</button>
                        <button type="button" class="star-input" data-rating="4" onclick="setRating(4)">☆</button>
                        <button type="button" class="star-input" data-rating="5" onclick="setRating(5)">☆</button>
                    </div>
                    
                    <input type="hidden" name="rating" id="rating-value" value="5" required>
                    
                    <div class="review-section">
                        <label for="review" style="display: block; margin-bottom: 10px; font-weight: 600;">Share your experience (optional)</label>
                        <textarea 
                            id="review" 
                            name="review" 
                            class="review-textarea" 
                            placeholder="Tell us about your experience with this worker..."
                        ></textarea>
                    </div>
                    
                    <div class="submit-section">
                        <button type="submit" class="btn-rate">Submit Rating</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let selectedRating = 5; // Default to 5 stars

        function setRating(rating) {
            selectedRating = rating;
            document.getElementById('rating-value').value = rating;
            
            // Update star display
            const stars = document.querySelectorAll('.star-input');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.textContent = '⭐';
                    star.classList.add('active');
                } else {
                    star.textContent = '☆';
                    star.classList.remove('active');
                }
            });
        }

        // Initialize with 5 stars
        setRating(5);

        // Add hover effects
        document.querySelectorAll('.star-input').forEach(star => {
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                const stars = document.querySelectorAll('.star-input');
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });

        // Reset on mouse leave
        document.querySelector('.rating-stars-input').addEventListener('mouseleave', function() {
            setRating(selectedRating);
        });
    </script>
</body>
</html>
