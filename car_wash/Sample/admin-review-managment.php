<?php
// Start session for admin authentication
session_start();

// Include database connection
require_once 'Database_Connection.php';

// Check if admin is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login page if not logged in or not an admin
    header('Location: admin-login.php');
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle review status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $review_id = $_POST['review_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $review_id);
    
    if ($stmt->execute()) {
        $success_message = "Review status updated successfully!";
    } else {
        $error_message = "Error updating review status: " . $conn->error;
    }
}

// Process review reply submission
if (isset($_POST['submit_reply'])) {
    $review_id = $_POST['review_id'];
    $staff_id = $_SESSION['admin_id']; // Assuming admin_id is stored in session
    $reply_text = $_POST['reply_text'];
    
    $stmt = $conn->prepare("INSERT INTO review_replies (review_id, staff_id, reply_text) 
                           VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $review_id, $staff_id, $reply_text);
    
    if ($stmt->execute()) {
        $replyMessage = "Reply submitted successfully!";
    } else {
        $errorMessage = "Error submitting reply: " . $conn->error;
    }
    $stmt->close();
}

// Delete review if requested
if (isset($_GET['delete_review']) && isset($_GET['review_id'])) {
    $review_id = $_GET['review_id'];
    
    // First delete associated replies
    $stmt = $conn->prepare("DELETE FROM review_replies WHERE review_id = ?");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $stmt->close();
    
    // Then delete the review
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $review_id);
    
    if ($stmt->execute()) {
        $deleteMessage = "Review and its replies deleted successfully!";
    } else {
        $errorMessage = "Error deleting review: " . $conn->error;
    }
    $stmt->close();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query with potential filters
$query = "SELECT r.*, 
          c.name as customer_name, 
          c.email as customer_email,
          b.booking_date,
          b.vehicle_type,
          b.vehicle_number,
          s.name as service_name,
          (SELECT COUNT(*) FROM review_replies WHERE review_id = r.id) as reply_count
          FROM reviews r
          JOIN customers c ON r.customer_id = c.id
          JOIN bookings b ON r.booking_id = b.id
          JOIN services s ON b.service_id = s.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($rating_filter)) {
    $query .= " AND r.rating = ?";
    $params[] = $rating_filter;
    $types .= "i";
}

if (!empty($search_term)) {
    $query .= " AND (r.comment LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY r.created_at DESC";

// Prepare statement with dynamic parameters
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get stats for dashboard
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'avg_rating' => 0
];

$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                AVG(rating) as avg_rating
               FROM reviews";
               
$statsResult = $conn->query($statsQuery);
if ($statsResult && $statsResult->num_rows > 0) {
    $stats = $statsResult->fetch_assoc();
}

// HTML and UI
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Management - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    <style>
        .review-card {
            margin-bottom: 20px;
            border-left: 5px solid #ddd;
        }
        .review-card.pending {
            border-left-color: #ffc107;
        }
        .review-card.approved {
            border-left-color: #28a745;
        }
        .review-card.rejected {
            border-left-color: #dc3545;
        }
        .rating {
            color: #ffc107;
        }
        .stats-card {
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .reply-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .reply-form {
            display: none;
        }
        .replies-section {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">
<?php include("admin-navbar.php"); ?>

<main class="py-5">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Review Management</h1>
        </div>
                
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php
            if (empty($reviews)):
                echo '<div class="col-12"><div class="alert alert-info">No reviews found matching your criteria.</div></div>';
            else:
                foreach ($reviews as $review):
                    $statusClass = 'secondary';
                    switch ($review['status']) {
                        case 'published':
                            $statusClass = 'success';
                            break;
                        case 'pending':
                            $statusClass = 'warning';
                            break;
                        case 'hidden':
                            $statusClass = 'danger';
                            break;
                    }
                    ?>
                    <div class="col-md-6">
                        <div class="card review-card <?php echo $statusClass; ?> mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($review['customer_name']); ?></h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($review['customer_email']); ?></small>
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                        <?php echo ucfirst($review['status']); ?>
                                    </span>
                                    <small class="text-muted ms-2">
                                        <?php echo date('M d, Y h:i A', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="text-primary mb-2"><?php echo htmlspecialchars($review['service_name']); ?></h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" 
                                                data-bs-toggle="dropdown">
                                            Status
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <form action="" method="POST" class="dropdown-item">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <input type="hidden" name="status" value="published">
                                                    <button type="submit" name="update_status" class="btn btn-link p-0 text-success">
                                                        <i class="bi bi-check-circle"></i> Publish
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="" method="POST" class="dropdown-item">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <input type="hidden" name="status" value="hidden">
                                                    <button type="submit" name="update_status" class="btn btn-link p-0 text-danger">
                                                        <i class="bi bi-eye-slash"></i> Hide
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                    <a href="?delete_review=1&review_id=<?php echo $review['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this review? This will also delete all replies.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                                            
                                <div class="replies-container">
                                    <button class="btn btn-outline-primary btn-sm mb-3" 
                                            onclick="toggleReplyForm(<?php echo $review['id']; ?>)">
                                        <i class="bi bi-reply"></i> Reply
                                    </button>
                                    
                                    <?php if ($review['reply_count'] > 0): ?>
                                        <button class="btn btn-outline-secondary btn-sm mb-3 ms-2" 
                                                onclick="loadReplies(<?php echo $review['id']; ?>)">
                                            <i class="bi bi-chat-dots"></i> 
                                            View Replies (<?php echo $review['reply_count']; ?>)
                                        </button>
                                    <?php endif; ?>

                                    <div id="replyForm<?php echo $review['id']; ?>" class="reply-form mb-3">
                                        <div class="input-group">
                                            <textarea class="form-control" rows="2" 
                                                      placeholder="Write your reply..."
                                                      id="replyText<?php echo $review['id']; ?>"></textarea>
                                            <button class="btn btn-primary" 
                                                    onclick="submitReply(<?php echo $review['id']; ?>)">
                                                Send
                                            </button>
                                        </div>
                                    </div>

                                    <div id="replies<?php echo $review['id']; ?>" class="replies-section"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                endforeach;
            endif;
            ?>
        </div>
    </div>
</main>

<?php include('admin-footer.php'); ?>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleReplyForm(reviewId) {
        const form = document.getElementById(`replyForm${reviewId}`);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function loadReplies(reviewId) {
        const repliesContainer = document.getElementById(`replies${reviewId}`);
        
        fetch(`get_replies.php?review_id=${reviewId}`)
            .then(response => response.json())
            .then(data => {
                let html = '<div class="list-group list-group-flush">';
                data.forEach(reply => {
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>${reply.admin_name}</strong>
                                <small class="text-muted">${reply.created_at}</small>
                            </div>
                            <p class="mb-0">${reply.reply}</p>
                        </div>
                    `;
                });
                html += '</div>';
                repliesContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading replies:', error);
                repliesContainer.innerHTML = '<div class="alert alert-danger">Error loading replies</div>';
            });
    }

    function submitReply(reviewId) {
        const textarea = document.getElementById(`replyText${reviewId}`);
        const reply = textarea.value.trim();
        
        if (!reply) {
            alert('Please write a reply first');
            return;
        }

        fetch('add_reply.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                review_id: reviewId,
                reply: reply
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                textarea.value = '';
                toggleReplyForm(reviewId);
                loadReplies(reviewId);
                
                // Update reply count button
                const replyCountBtn = textarea.closest('.replies-container')
                    .querySelector('.btn-outline-secondary');
                if (replyCountBtn) {
                    const count = parseInt(replyCountBtn.textContent.match(/\d+/)[0]) + 1;
                    replyCountBtn.innerHTML = `<i class="bi bi-chat-dots"></i> View Replies (${count})`;
                } else {
                    const newBtn = document.createElement('button');
                    newBtn.className = 'btn btn-outline-secondary btn-sm mb-3 ms-2';
                    newBtn.innerHTML = '<i class="bi bi-chat-dots"></i> View Replies (1)';
                    newBtn.onclick = () => loadReplies(reviewId);
                    textarea.closest('.replies-container').insertBefore(newBtn, 
                        document.getElementById(`replyForm${reviewId}`));
                }
            } else {
                alert(data.error || 'Failed to add reply');
            }
        })
        .catch(error => {
            console.error('Error submitting reply:', error);
            alert('Failed to submit reply');
        });
    }
</script>
</body>
</html>

<?php
// Close database connection
$db->closeConnection();
?>
