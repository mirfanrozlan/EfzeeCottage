<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    header('Location: index.php');
    exit();
}

// Get booking details
$query = "SELECT b.*, h.name as homestay_name, p.status as payment_status, p.payment_method,
                 pr.file_path as receipt_path
          FROM bookings b
          JOIN homestays h ON b.homestay_id = h.homestay_id
          LEFT JOIN payments p ON b.booking_id = p.booking_id
          LEFT JOIN payment_receipts pr ON p.payment_id = pr.payment_id
          WHERE b.booking_id = ? AND b.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $booking_id, $_SESSION['user']['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - EFZEE COTTAGE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 1.1em;
            padding: 8px 15px;
            border-radius: 20px;
        }

        .status-pending {
            background-color: #ffd700;
            color: #000;
        }

        .status-confirmed {
            background-color: #28a745;
            color: #fff;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
        }

        .booking-details {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .receipt-section {
            margin-top: 30px;
            text-align: center;
        }

        .receipt-image {
            max-width: 300px;
            max-height: 300px;
            object-fit: contain;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <?php include 'components/navbar.php'; ?>

    <div class="confirmation-container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <h2 class="text-center mb-4">Booking Confirmation</h2>

        <div class="text-center mb-4">
            <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                <?php echo ucfirst($booking['status']); ?>
            </span>
        </div>

        <div class="booking-details">
            <h4>Booking Details</h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Booking ID:</strong> #<?php echo $booking['booking_id']; ?></p>
                    <p><strong>Homestay:</strong> <?php echo htmlspecialchars($booking['homestay_name']); ?></p>
                    <p><strong>Check-in:</strong> <?php echo $booking['check_in_date']; ?></p>
                    <p><strong>Check-out:</strong> <?php echo $booking['check_out_date']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Total Amount:</strong> RM <?php echo number_format($booking['total_price'], 2); ?></p>
                    <p><strong>Payment Method:</strong>
                        <?php echo $booking['payment_method'] ? ucfirst(str_replace('_', ' ', $booking['payment_method'])) : 'Not selected'; ?>
                    </p>
                    <p><strong>Payment Status:</strong>
                        <?php echo $booking['payment_status'] ? ucfirst($booking['payment_status']) : 'Not paid'; ?></p>
                </div>
            </div>
        </div>

        <?php if ($booking['payment_status'] === 'pending'): ?>
            <div class="receipt-section">
                <?php if ($booking['receipt_path']): ?>
                    <h4>Payment Receipt</h4>
                    <img src="<?php echo $booking['receipt_path']; ?>" class="receipt-image" alt="Payment Receipt">
                    <p class="text-muted">Your payment is being reviewed by our admin team.</p>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Please upload your payment receipt to confirm your booking.
                    </div>
                    <form action="process_payment.php" method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        <input type="hidden" name="amount" value="<?php echo $booking['total_price']; ?>">
                        <input type="hidden" name="payment_method" value="<?php echo $booking['payment_method']; ?>">
                        <div class="mb-3">
                            <label for="payment_receipt" class="form-label">Upload Receipt</label>
                            <input type="file" class="form-control" id="payment_receipt" name="payment_receipt"
                                accept="image/*,.pdf" required>
                            <div class="form-text">Accepted formats: JPG, PNG, PDF</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Receipt
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>