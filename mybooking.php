<?php
session_start();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'efzeecottage';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: homepage.php");
    exit();
}

$user_id = $_SESSION['user']['user_id'];

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $booking_id = $_POST['booking_id'];

    // Get booking details to check cancellation eligibility
    $check_query = "SELECT check_in_date, status FROM bookings WHERE booking_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $booking_check = $check_stmt->get_result()->fetch_assoc();

    if ($booking_check) {
        $check_in_date = new DateTime($booking_check['check_in_date']);
        $current_date = new DateTime();
        $hours_difference = ($check_in_date->getTimestamp() - $current_date->getTimestamp()) / 3600;

        if ($hours_difference >= 24 && $booking_check['status'] !== 'cancelled') {
            $update_query = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $booking_id, $user_id);

            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = "Booking cancelled successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to cancel booking.";
            }
        } else {
            $_SESSION['error_message'] = "Booking can only be cancelled at least 24 hours before check-in date.";
        }
    }

    header("Location: mybooking.php");
    exit();
}

// Get user's booking count for loyalty discount
$loyalty_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE user_id = ? AND status != 'cancelled'";
$loyalty_stmt = $conn->prepare($loyalty_query);
$loyalty_stmt->bind_param("i", $user_id);
$loyalty_stmt->execute();
$loyalty_result = $loyalty_stmt->get_result()->fetch_assoc();
$has_previous_booking = $loyalty_result['booking_count'] > 0;

$query = "SELECT b.*, h.name as homestay_name, h.address, h.price_per_night,
          p.payment_method, p.status as payment_status, p.payment_date, p.discount
          FROM bookings b
          LEFT JOIN homestays h ON b.homestay_id = h.homestay_id
          LEFT JOIN payments p ON b.booking_id = p.booking_id
          WHERE b.user_id = ?
          ORDER BY b.check_in_date DESC"; // Ensure bookings are sorted by check-in date

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - EFZEE COTTAGE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/mybooking.css">
</head>

<body>
    <!-- Navigation Bar -->
    <?php include 'components/navbar.php'; ?>

    <div class="container" style="margin-top: 10px;">
        <h1 class="page-title">My Bookings</h1>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message-container success-message">
                <?php echo $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message-container error-message">
                <?php echo $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <h3>No Bookings Yet</h3>
                <p>You haven't made any bookings yet. Start planning your cozy getaway now!</p>
                <a href="homepage.php#booking" class="btn btn-primary">
                    <i class="fas fa-home"></i> Browse Homestays
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <h2><?php echo htmlspecialchars($booking['homestay_name']); ?></h2>
                        <span class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>

                    <div class="booking-details">
                        <div class="detail-group">
                            <div class="detail-label"><i class="fas fa-sign-in-alt"></i> Check-in Date</div>
                            <div><?php echo date('F d, Y', strtotime($booking['check_in_date'])); ?></div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label"><i class="fas fa-sign-out-alt"></i> Check-out Date</div>
                            <div><?php echo date('F d, Y', strtotime($booking['check_out_date'])); ?></div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label"><i class="fas fa-users"></i> Total Guests</div>
                            <div><?php echo $booking['total_guests']; ?> persons</div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label"><i class="fas fa-hashtag"></i> Booking ID</div>
                            <div>#<?php echo $booking['booking_id']; ?></div>
                        </div>
                    </div>

                    <!-- Price Breakdown Section -->
                    <div class="price-breakdown">
                        <div class="price-breakdown-title">
                            <i class="fas fa-receipt"></i> Price Breakdown
                        </div>

                        <?php
                        $check_in = new DateTime($booking['check_in_date']);
                        $check_out = new DateTime($booking['check_out_date']);
                        $nights = $check_in->diff($check_out)->days;
                        $base_price = $booking['price_per_night'] * $nights;
                        $discount = $booking['discount'] ?? 0; // Get discount from payment table
                        $total_price = $base_price - $discount; // Calculate total price
                        ?>

                        <div class="price-item">
                            <span>Base Price (<?php echo $nights; ?> nights ×
                                RM<?php echo number_format($booking['price_per_night'], 2); ?>)</span>
                            <span>RM <?php echo number_format($base_price, 2); ?></span>
                        </div>

                        <?php if ($discount > 0): ?>
                            <div class="price-item loyalty-discount">
                                <span>Discount</span>
                                <span>- RM <?php echo number_format($discount, 2); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="price-item">
                            <strong>Total Amount</strong>
                            <strong>RM <?php echo number_format($total_price, 2); ?></strong>
                        </div>
                    </div>

                    <div class="payment-info">
                        <div class="payment-title">
                            <i class="fas fa-credit-card"></i> Payment Information
                        </div>
                        <div class="payment-details">
                            <div class="detail-group">
                                <div class="detail-label">Method</div>
                                <div>
                                    <?php echo $booking['payment_method'] ? ucfirst(str_replace('_', ' ', $booking['payment_method'])) : 'Not specified'; ?>
                                </div>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label">Status</div>
                                <div><?php echo $booking['payment_status'] ? ucfirst($booking['payment_status']) : 'Pending'; ?>
                                </div>
                            </div>

                            <?php if ($booking['payment_date']): ?>
                                <div class="detail-group">
                                    <div class="detail-label">Date</div>
                                    <div><?php echo date('F d, Y', strtotime($booking['payment_date'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    // Check if booking is completed and if user has already reviewed
                    $review_query = "SELECT review_id, status FROM reviews WHERE booking_id = ? LIMIT 1";
                    $review_stmt = $conn->prepare($review_query);
                    $review_stmt->bind_param("i", $booking['booking_id']);
                    $review_stmt->execute();
                    $review_result = $review_stmt->get_result();
                    $existing_review = $review_result->fetch_assoc();

                    if ($booking['status'] !== 'cancelled'):
                        $check_in_date = new DateTime($booking['check_in_date']);
                        $current_date = new DateTime();
                        $hours_difference = ($check_in_date->getTimestamp() - $current_date->getTimestamp()) / 3600;
                        $can_cancel = $hours_difference >= 24;
                        $is_completed = $booking['status'] === 'confirmed';
                        ?>
                        <div class="booking-actions">
                            <?php if ($is_completed): ?>
                                <?php if (!$existing_review): ?>
                                    <button type="button" class="btn btn-primary"
                                        onclick="openReviewModal(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['homestay_name']); ?>', <?php echo $booking['homestay_id']; ?>)">
                                        <i class="fas fa-star"></i> Write Review
                                    </button>
                                <?php else: ?>
                                    <div class="review-status">
                                        <i class="fas fa-check-circle"></i>
                                        Review <?php echo ucfirst($existing_review['status']); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($can_cancel): ?>
                                <form action="mybooking.php" method="POST">
                                    <input type="hidden" name="action" value="cancel_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times-circle"></i> Cancel Booking
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="cancellation-policy">
                                    <i class="fas fa-info-circle"></i> Booking can only be cancelled at least 24 hours before check-in
                                </div>
                            <?php endif; ?>

                            <a href="homepage.php#booking" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Book Another Stay
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <div class="modal" id="reviewModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeReviewModal()">&times;</span>
            <h2>Write a Review</h2>
            <form id="reviewForm">
                <input type="hidden" id="booking_id" name="booking_id">
                <input type="hidden" id="homestay_id" name="homestay_id">

                <div class="form-group">
                    <label>Homestay:</label>
                    <div id="homestayName" class="homestay-name"></div>
                </div>

                <div class="form-group">
                    <label>Rating:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="comment">Your Review:</label>
                    <textarea id="comment" name="comment" rows="4" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Star Rating Styles */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            padding: 5px;
            color: #ddd;
        }

        .star-rating label:hover,
        .star-rating label:hover~label,
        .star-rating input:checked~label {
            color: #ffc107;
        }

        /* Review Status Styles */
        .review-status {
            display: inline-block;
            padding: 8px 12px;
            background-color: #e9ecef;
            border-radius: 4px;
            color: #495057;
        }

        .review-status i {
            color: #28a745;
            margin-right: 5px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .homestay-name {
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
    </style>

    <script>
        // Review Modal Functions
        function openReviewModal(bookingId, homestayName, homestayId) {
            document.getElementById('booking_id').value = bookingId;
            document.getElementById('homestay_id').value = homestayId;
            document.getElementById('homestayName').textContent = homestayName;
            document.getElementById('reviewModal').style.display = 'block';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
            document.getElementById('reviewForm').reset();
        }

        // Handle Review Form Submission
        document.getElementById('reviewForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('submit_review.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Review submitted successfully!');
                        closeReviewModal();
                        location.reload(); // Reload page to update review status
                    } else {
                        alert(data.message || 'Failed to submit review');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while submitting the review');
                });
        });

        // Existing scripts
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        document.querySelectorAll('.btn-danger').forEach(button => {
            button.addEventListener('click', function (e) {
                if (!confirm('Are you sure you want to cancel this booking?')) {
                    e.preventDefault();
                }
            });
        });

        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            });
        });

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target == document.getElementById('reviewModal')) {
                closeReviewModal();
            }
        }
    </script>
</body>

</html>