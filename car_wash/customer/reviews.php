<?php
session_start();
require_once 'Database_Connection.php';

$error = '';
$success = '';
$average_rating = 0;
$review_count = 0;
$rating_distribution = array_fill(1, 5, 0);

try {
$db = new Database();
$conn = $db->getConnection();

    // Handle review submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['customer_id'])) {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_review':
                    $booking_id = $_POST['booking_id'];
                    $rating = $_POST['rating'];
                    $comment = trim($_POST['comment']);
                    
                    if (empty($comment) || $rating < 1 || $rating > 5) {
                        throw new Exception("Please provide a valid rating and comment");
                    }
                    
                    // Check if booking exists and belongs to customer
                    $stmt = $conn->prepare("
                        SELECT id FROM bookings 
                        WHERE id = ? AND customer_id = ? AND status = 'completed'
                    ");
                    $stmt->bind_param("ii", $booking_id, $_SESSION['customer_id']);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows === 0) {
                        throw new Exception("Invalid booking or service not completed yet");
                    }
                    
                    // Check if review already exists
                    $stmt = $conn->prepare("
                        SELECT id FROM reviews 
                        WHERE booking_id = ? AND customer_id = ?
                    ");
                    $stmt->bind_param("ii", $booking_id, $_SESSION['customer_id']);
    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        throw new Exception("You have already reviewed this service");
                    }
                    
                    // Add review
                    $stmt = $conn->prepare("
                        INSERT INTO reviews (
                            booking_id, customer_id, rating, comment, status, created_at
                        ) VALUES (?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)
                    ");
                    $stmt->bind_param("iiis", $booking_id, $_SESSION['customer_id'], $rating, $comment);
        $stmt->execute();
                    
                    $success = "Review submitted successfully";
                    break;
            }
        }
    }
    
    // Get completed bookings for review (if logged in)
    $completed_bookings = array();
    if (isset($_SESSION['customer_id'])) {
        $stmt = $conn->prepare("
            SELECT b.*, 
                   s.name as service_name
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            LEFT JOIN reviews r ON b.id = r.booking_id
            WHERE b.customer_id = ? 
            AND b.status = 'completed'
            AND r.id IS NULL
            ORDER BY b.booking_date DESC
        ");
        $stmt->bind_param("i", $_SESSION['customer_id']);
        $stmt->execute();
        $completed_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get all active reviews with customer and booking details
$stmt = $conn->prepare("
        SELECT r.*, 
               c.name as customer_name,
               b.booking_date,
               b.vehicle_type,
               b.vehicle_number,
               s.name as service_name,
               COALESCE(sr.comment, '') as staff_reply,
               COALESCE(sr.created_at, '') as reply_date,
               COALESCE(st.name, '') as staff_name
        FROM reviews r
        JOIN customers c ON r.customer_id = c.id
        JOIN bookings b ON r.booking_id = b.id
        JOIN services s ON b.service_id = s.id
        LEFT JOIN staff_replies sr ON r.id = sr.review_id AND sr.status = 'active'
        LEFT JOIN staff st ON sr.staff_id = st.id
        WHERE r.status = 'active'
        ORDER BY r.created_at DESC
    ");
$stmt->execute();
    $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate average rating
    $total_rating = 0;
    $review_count = count($reviews);
    $rating_distribution = array_fill(1, 5, 0);
    
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
        $rating_distribution[$review['rating']]++;
    }
    
    $average_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;
    
$stmt->close();
    $db->closeConnection();
    
} catch (Exception $e) {
    error_log("Reviews error: " . $e->getMessage());
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <style>
        .rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .rating-form .bi {
            cursor: pointer;
            font-size: 1.5rem;
        }
        .rating-form .bi:hover {
            color: #ffc107;
        }
        .rating-form .bi.active {
            color: #ffc107;
        }
        .review-card {
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .staff-reply {
            background-color: #f8f9fa;
            border-left: 3px solid #0d6efd;
            padding: 1rem;
            margin-top: 1rem;
        }
        .rating-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .rating-bar-fill {
            height: 100%;
            background-color: #ffc107;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('customer-navbar.php'); ?>

    <div class="container my-5">
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
            </div>
                    <?php endif; ?>
                    
        <!-- Rating Summary -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <h1 class="display-4 fw-bold mb-0"><?php echo number_format($average_rating, 1); ?></h1>
                        <div class="rating mb-2">
                            <?php
                            $full_stars = floor($average_rating);
                            $half_star = $average_rating - $full_stars >= 0.5;
                            
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $full_stars) {
                                    echo '<i class="bi bi-star-fill"></i>';
                                } elseif ($i == $full_stars + 1 && $half_star) {
                                    echo '<i class="bi bi-star-half"></i>';
                                } else {
                                    echo '<i class="bi bi-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <p class="text-muted mb-0">Based on <?php echo $review_count; ?> reviews</p>
                    </div>
                    
                    <div class="col-md-8">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="text-muted me-2" style="width: 60px;">
                                    <?php echo $i; ?> <i class="bi bi-star-fill"></i>
                                </div>
                                <div class="rating-bar flex-grow-1 me-2">
                                    <div class="rating-bar-fill" style="width: <?php 
                                        echo $review_count > 0 ? ($rating_distribution[$i] / $review_count * 100) : 0; 
                                    ?>%"></div>
                                </div>
                                <div class="text-muted" style="width: 40px;">
                                    <?php echo $rating_distribution[$i]; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Write Review Section (for logged-in users) -->
        <?php if (isset($_SESSION['customer_id']) && !empty($completed_bookings)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Write a Review</h4>
                        </div>
                        <div class="card-body">
                    <form method="POST" action="" id="reviewForm">
                        <input type="hidden" name="action" value="add_review">
                        
                                <div class="mb-3">
                            <label for="booking_id" class="form-label">Select Service</label>
                            <select class="form-select" id="booking_id" name="booking_id" required>
                                <option value="">Choose a completed service...</option>
                                <?php foreach ($completed_bookings as $booking): ?>
                                    <option value="<?php echo $booking['id']; ?>">
                                        <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> - 
                                        <?php echo htmlspecialchars($booking['service_name']); ?> - 
                                        <?php echo ucfirst($booking['vehicle_type']) . ' (' . htmlspecialchars($booking['vehicle_number']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-form mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star" data-rating="<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                            <input type="hidden" name="rating" id="rating" required>
                                </div>

                                <div class="mb-3">
                            <label for="comment" class="form-label">Your Review</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                                </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit Review
                        </button>
                            </form>
                        </div>
                    </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-chat-square-text display-1 text-muted"></i>
                    <h3 class="mt-3">No Reviews Yet</h3>
                    <p class="text-muted">Be the first to review our services!</p>
                        </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="card review-card shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($review['customer_name']); ?></h5>
                                    <p class="text-muted mb-0">
                                        <small>
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?> - 
                                            <?php echo htmlspecialchars($review['service_name']); ?> - 
                                            <?php echo ucfirst($review['vehicle_type']) . ' (' . htmlspecialchars($review['vehicle_number']) . ')'; ?>
                                        </small>
                                    </p>
                                </div>
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            
                            <?php if ($review['staff_reply']): ?>
                                <div class="staff-reply">
                                    <h6 class="mb-2">Response from <?php echo htmlspecialchars($review['staff_name']); ?></h6>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($review['staff_reply'])); ?></p>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($review['reply_date'])); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                            <?php endif; ?>
        </div>
    </div>

    <footer style="background-color: #1b1f23; color: #ccc; padding: 40px 10%; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 30px; font-size: 15px;">
  
        <div style="flex: 1; min-width: 200px;">
            <h2 style="color: #2FA5EB; font-family: 'Forte', cursive; font-size: 26px;">Car Medic</h2>
            <p style="margin: 15px 0;">We care for your car like doctors care for patients. Trusted by thousands, driven by excellence.</p>
            <p>&copy; 2025 Car Medic Automotive Repair</p>
        </div>
    
        <div style="flex: 1; min-width: 200px;">
            <h3 style="color: #2FA5EB; margin-bottom: 15px;">Quick Links</h3>
            <ul style="list-style: none; padding: 0;">
                <li><a href="home.php" style="color: #ccc; text-decoration: none;">Home</a></li>
                <li><a href="services.php" style="color: #ccc; text-decoration: none;">Services</a></li>
                <li><a href="book_service.php" style="color: #ccc; text-decoration: none;">Appointment</a></li>
                <li><a href="register.php" style="color: #ccc; text-decoration: none;">User Sign Up</a></li>
                <li><a href="../Sample/admin-login.php" style="color: #ccc; text-decoration: none;">Admin Panel</a></li>
            </ul>
        </div>
    
        <div style="flex: 1; min-width: 200px;">
            <h3 style="color: #2FA5EB; margin-bottom: 15px;">Contact Us</h3>
            <p><i class="fas fa-map-marker-alt"></i> 250 Galle Road, Colombo 3</p>
            <p><i class="fas fa-phone"></i> ‪+94 0112525456‬</p>
            <p><i class="fas fa-envelope"></i> CarMedicAutomotiveRepair@gmail.com</p>

            <div style="margin-top: 15px;">
                <a href="#" style="color: #2FA5EB; margin-right: 10px;"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" style="color: #2FA5EB; margin-right: 10px;"><i class="fab fa-instagram fa-lg"></i></a>
                <a href="#" style="color: #2FA5EB; margin-right: 10px;"><i class="fab fa-twitter fa-lg"></i></a>
                <a href="#" style="color: #2FA5EB;"><i class="fab fa-youtube fa-lg"></i></a>
            </div>
        </div>
    
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        document.addEventListener('DOMContentLoaded', function() {
            const ratingForm = document.querySelector('.rating-form');
            const ratingInput = document.getElementById('rating');
            const stars = ratingForm?.getElementsByClassName('bi');
            
            if (stars) {
                Array.from(stars).forEach(star => {
                    star.addEventListener('mouseover', function() {
                        const rating = this.dataset.rating;
                        highlightStars(rating);
                    });
                    
                    star.addEventListener('mouseout', function() {
                        const currentRating = ratingInput.value;
                        highlightStars(currentRating);
                    });
                    
                    star.addEventListener('click', function() {
                        const rating = this.dataset.rating;
                        ratingInput.value = rating;
                        highlightStars(rating);
                    });
                });
            }
            
            function highlightStars(rating) {
                Array.from(stars).forEach(star => {
                    const starRating = star.dataset.rating;
                    star.classList.remove('bi-star-fill', 'bi-star');
                    star.classList.add(starRating <= rating ? 'bi-star-fill' : 'bi-star');
                });
            }
        });
    </script>
</body>
</html>