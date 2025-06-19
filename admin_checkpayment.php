<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Database connection
require_once 'config.php';

// Handle payment approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve_payment') {
        $booking_id = $_POST['booking_id'];
        $payment_id = $_POST['payment_id'];

        // Update payment status
        $stmt = $conn->prepare("UPDATE payments SET status = 'approved', approved_at = NOW() WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);

        if ($stmt->execute()) {
            // Update booking status
            $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);

            if ($stmt->execute()) {
                // Send email notification
                $query = "SELECT b.*, u.email, u.name as customer_name, h.name as homestay_name 
                          FROM bookings b 
                          JOIN users u ON b.user_id = u.user_id 
                          JOIN homestays h ON b.homestay_id = h.homestay_id 
                          WHERE b.booking_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $booking = $result->fetch_assoc();

                // Send confirmation email
                $to = $booking['email'];
                $subject = "Booking Confirmed - EFZEE COTTAGE";
                $message = "Dear {$booking['customer_name']},\n\n";
                $message .= "Your booking for {$booking['homestay_name']} has been confirmed.\n";
                $message .= "Booking Details:\n";
                $message .= "Check-in: {$booking['check_in_date']}\n";
                $message .= "Check-out: {$booking['check_out_date']}\n";
                $message .= "Total Amount: RM {$booking['total_price']}\n\n";
                $message .= "Thank you for choosing EFZEE COTTAGE!";

                mail($to, $subject, $message);

                $_SESSION['success_message'] = "Payment approved and booking confirmed successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to update booking status.";
            }
        } else {
            $_SESSION['error_message'] = "Failed to approve payment.";
        }

        header('Location: admin_checkpayment.php');
        exit();
    }
}

// Get pending payments with booking and user details
$query = "SELECT p.*, b.booking_id, b.check_in_date, b.check_out_date, b.total_price,
                 u.name as customer_name, h.name as homestay_name,
                 pr.file_path as receipt_path
          FROM payments p
          JOIN bookings b ON p.booking_id = b.booking_id
          JOIN users u ON b.user_id = u.user_id
          JOIN homestays h ON b.homestay_id = h.homestay_id
          LEFT JOIN payment_receipts pr ON p.payment_id = pr.payment_id
          WHERE p.status = 'pending'
          ORDER BY p.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Payments - EFZEE COTTAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }

        .nav-link {
            color: #fff;
        }

        .nav-link:hover {
            background-color: #495057;
        }

        .nav-link.active {
            background-color: #0d6efd;
        }

        .receipt-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="text-white mb-4">Admin Panel</h3>
                <div class="nav flex-column">
                    <a href="admin.php" class="nav-link mb-2">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="admin_bookings.php" class="nav-link mb-2">
                        <i class="fas fa-calendar-alt me-2"></i> Bookings
                    </a>
                    <a href="admin_homestays.php" class="nav-link mb-2">
                        <i class="fas fa-home me-2"></i> Homestays
                    </a>
                    <a href="admin_payments.php" class="nav-link mb-2">
                        <i class="fas fa-money-bill me-2"></i> Payments
                    </a>
                    <a href="admin_checkpayment.php" class="nav-link active mb-2">
                        <i class="fas fa-check-circle me-2"></i> Check Payments
                    </a>
                    <a href="admin_users.php" class="nav-link mb-2">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a href="logout.php" class="nav-link mt-4 text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2 class="mb-4">Check Payments</h2>

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

                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Homestay</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Amount</th>
                                        <th>Receipt</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['booking_id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['homestay_name']); ?></td>
                                                <td><?php echo $row['check_in_date']; ?></td>
                                                <td><?php echo $row['check_out_date']; ?></td>
                                                <td>RM <?php echo number_format($row['total_price'], 2); ?></td>
                                                <td>
                                                    <?php if ($row['receipt_path']): ?>
                                                        <a href="<?php echo $row['receipt_path']; ?>" target="_blank">
                                                            <img src="<?php echo $row['receipt_path']; ?>" class="receipt-image"
                                                                alt="Payment Receipt">
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No receipt uploaded</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form action="admin_checkpayment.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="approve_payment">
                                                        <input type="hidden" name="booking_id"
                                                            value="<?php echo $row['booking_id']; ?>">
                                                        <input type="hidden" name="payment_id"
                                                            value="<?php echo $row['payment_id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" <?php echo $row['receipt_path'] ? '' : 'disabled'; ?>>
                                                            <i class="fas fa-check me-1"></i> Approve
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No pending payments found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>