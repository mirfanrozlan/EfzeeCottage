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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/mybooking.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                                    <button type="submit" class="btn btn-danger" onclick="event.preventDefault(); Swal.fire({
                                            title: 'Cancel Booking',
                                            text: 'Are you sure you want to cancel this booking?',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#d33',
                                            cancelButtonColor: '#3085d6',
                                            confirmButtonText: 'Yes, cancel it!',
                                            cancelButtonText: 'No, keep it'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                this.form.submit();
                                            }
                                        })">
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
    <div id="reviewModal" class="modal" role="dialog" aria-labelledby="reviewModalTitle" aria-modal="true">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="reviewModalTitle">Write a Review</h2>
                <button type="button" class="close" onclick="closeReviewModal()" aria-label="Close">&times;</button>
            </div>

            <div class="modal-body">
                <p class="homestay-name">Reviewing for: <strong><span id="homestayName"></span></strong></p>

                <form id="reviewForm" novalidate>
                    <input type="hidden" id="booking_id" name="booking_id">
                    <input type="hidden" id="homestay_id" name="homestay_id">

                    <style>
                        .star-rating {
                            display: inline-flex;
                            align-items: center;
                        }

                        .star-rating .stars {
                            display: inline-flex;
                            align-items: center;
                            gap: 0.5rem;
                            flex-direction: row-reverse;
                        }

                        .star-rating input[type="radio"] {
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            opacity: 0;
                            cursor: pointer;
                            z-index: 2;
                        }

                        .star-rating .star-label {
                            background-color: transparent;
                            border: 2px solid transparent;
                            transition: all 0.2s ease;
                            width: 36px;
                            height: 36px;
                            border-radius: 50%;
                            padding: 0.5rem;
                            position: relative;
                            z-index: 1;
                        }

                        .star-rating .star-label i {
                            transition: all 0.2s ease;
                            opacity: 0.5;
                            font-size: 1.25rem;
                            color: #6c757d;
                        }

                        .star-rating input:checked+.star-label i,
                        .star-rating input:checked~.star-wrapper .star-label i,
                        .star-rating .star-wrapper:hover .star-label i,
                        .star-rating .star-wrapper:hover~.star-wrapper .star-label i,
                        .star-rating .star-wrapper:has(input:checked)~.star-wrapper .star-label i {
                            opacity: 1;
                            color: #ffc107;
                        }

                        .rating-feedback {
                            font-size: 0.875rem;
                            opacity: 0;
                            transition: opacity 0.2s ease;
                            min-width: 90px;
                            margin-left: 1rem;
                        }

                        .rating-text {
                            font-size: 0.875rem;
                            color: #6c757d;
                            margin-top: 0.5rem;
                            min-height: 20px;
                        }

                        .star-rating .star-wrapper {
                            cursor: pointer;
                            transform: scale(1);
                            transition: transform 0.2s ease;
                            position: relative;
                        }
                    </style>

                    <div class="form-group mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <label id="ratingLabel" class="mb-0 fw-bold">Rating:</label>
                            <div class="star-rating d-flex align-items-center" role="group"
                                aria-labelledby="ratingLabel">
                                <div class="stars d-flex align-items-center gap-2">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <div class="star-wrapper">
                                            <input type="radio" id="modalStar<?php echo $i; ?>" name="ratings"
                                                value="<?php echo $i; ?>" required class="d-none"
                                                aria-label="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">
                                            <label for="modalStar<?php echo $i; ?>"
                                                class="star-label d-flex align-items-center justify-content-center m-0"
                                                data-bs-toggle="tooltip"
                                                title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">
                                                <i class="fas fa-star"></i>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-feedback ms-3 text-muted"></div>
                            </div>
                        </div>
                        <div class="rating-text small text-muted mt-2" aria-live="polite"></div>
                        <div class="invalid-feedback">Please select a rating.</div>
                    </div>

                    <div class="form-group">
                        <label for="modalComment">Your Review:</label>
                        <textarea id="modalComment" name="comment" placeholder="Share your experience..." required
                            minlength="10" aria-describedby="commentHelp"></textarea>
                        <small id="commentHelp" class="form-text text-muted">Minimum 10 characters required.</small>
                        <div class="invalid-feedback">Please write a review (minimum 10 characters).</div>
                    </div>

                    <div class="alert alert-success" style="display: none;" role="alert">
                        <i class="fas fa-check-circle"></i> Review submitted successfully!
                    </div>
                    <div class="alert alert-danger" style="display: none;" role="alert"></div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeReviewModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="js/main.php"></script>

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

            // Get the rating value
            const rating = document.querySelector('input[name="ratings"]:checked');
            const comment = document.getElementById('modalComment').value.trim();

            // Validate form
            let isValid = true;

            // Check rating
            const ratingFeedback = document.querySelector('.star-rating .invalid-feedback');
            if (!rating) {
                if (ratingFeedback) ratingFeedback.style.display = 'block';
                isValid = false;
            } else {
                if (ratingFeedback) ratingFeedback.style.display = 'none';
            }

            // Check comment
            const commentInput = document.querySelector('#modalComment');
            const commentFeedback = document.querySelector('#modalComment + .invalid-feedback');
            if (!comment || comment.length < 10) {
                if (commentInput) commentInput.classList.add('is-invalid');
                if (commentFeedback) commentFeedback.style.display = 'block';
                isValid = false;
            } else {
                if (commentInput) commentInput.classList.remove('is-invalid');
                if (commentFeedback) commentFeedback.style.display = 'none';
            }

            if (!isValid) {
                return;
            }

            // Create FormData and explicitly add the rating value
            const formData = new FormData();
            formData.append('booking_id', document.getElementById('booking_id').value);
            formData.append('homestay_id', document.getElementById('homestay_id').value);
            formData.append('ratings', rating.value); // Ensure rating is included
            formData.append('comment', comment);

            const submitButton = this.querySelector('button[type="submit"]');
            const alertSuccess = document.querySelector('.alert-success');
            const alertDanger = document.querySelector('.alert-danger');

            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            // Hide any existing alerts
            alertSuccess.style.display = 'none';
            alertDanger.style.display = 'none';

            fetch('submit_review.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertSuccess.style.display = 'block';
                        setTimeout(() => {
                            closeReviewModal();
                            location.reload(); // Reload page to update review status
                        }, 1500);
                    } else {
                        alertDanger.textContent = data.message || 'Failed to submit review';
                        alertDanger.style.display = 'block';
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Review';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alertDanger.textContent = 'An error occurred while submitting the review';
                    alertDanger.style.display = 'block';
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Review';
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