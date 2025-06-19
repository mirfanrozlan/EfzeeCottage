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

                    <?php if ($booking['status'] !== 'cancelled'):
                        $check_in_date = new DateTime($booking['check_in_date']);
                        $current_date = new DateTime();
                        $hours_difference = ($check_in_date->getTimestamp() - $current_date->getTimestamp()) / 3600;
                        $can_cancel = $hours_difference >= 24;
                        ?>
                        <div class="booking-actions">
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

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Simple confirmation for cancellation
        document.querySelectorAll('.btn-danger').forEach(button => {
            button.addEventListener('click', function (e) {
                if (!confirm('Are you sure you want to cancel this booking?')) {
                    e.preventDefault();
                }
            });
        });

        // Close mobile menu when clicking on links
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            });
        });
    </script>
</body>

</html>