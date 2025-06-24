<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized access');
}

// Get booking ID and status
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status');

if ($booking_id === false || $status === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Update booking status in the database
$query = "UPDATE bookings SET status = ? WHERE booking_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('si', $status, $booking_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    require 'vendor/autoload.php';

    // Fetch user email and name for sending notification
    $user_query = "SELECT u.email, u.name FROM users u JOIN bookings b ON u.user_id = b.user_id WHERE b.booking_id = ?";
    $user_stmt = $conn->prepare($user_query);
    if ($user_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $user_stmt->bind_param('i', $booking_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user = $user_result->fetch_assoc()) {
        $to = $user['email'];
        $name = $user['name'];
        $subject = '';
        $message = '';

        if ($status === 'confirmed') {
            // Fetch booking, payment, and homestay details for invoice
            $invoice_query = "SELECT b.booking_id, b.check_in_date, b.check_out_date, b.total_guests, h.name AS homestay_name, h.address, p.amount, p.payment_method, p.discount, p.payment_date FROM bookings b JOIN homestays h ON b.homestay_id = h.homestay_id LEFT JOIN payments p ON b.booking_id = p.booking_id WHERE b.booking_id = ? LIMIT 1";
            $invoice_stmt = $conn->prepare($invoice_query);
            $invoice_stmt->bind_param('i', $booking_id);
            $invoice_stmt->execute();
            $invoice = $invoice_stmt->get_result()->fetch_assoc();
            $invoice_stmt->close();

            $subject = 'Booking Payment Confirmed';
            $logo_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/image/logo.png';
            $message = '<div style="font-family:Segoe UI,Arial,sans-serif;background:#f4f6fb;padding:30px 0;">';
            $message .= '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.07);overflow:hidden;">';
            $message .= '<div style="background:#0d6efd;padding:24px 32px 16px 32px;text-align:center;">';
            $message .= '<img src="' . $logo_url . '" alt="EFZEE COTTAGE" style="height:60px;margin-bottom:10px;"><br>';
            $message .= '<span style="color:#fff;font-size:2em;font-weight:700;letter-spacing:2px;">EFZEE COTTAGE</span>';
            $message .= '</div>';
            $message .= '<div style="padding:32px;">';
            $message .= '<h2 style="color:#0d6efd;margin-top:0;margin-bottom:16px;font-size:1.5em;">Booking Invoice</h2>';
            $message .= '<p style="font-size:1.1em;color:#333;">Dear ' . htmlspecialchars($name) . ',<br>Your booking payment has been <b>confirmed</b>. Thank you for choosing our service!</p>';
            $message .= '<div style="margin:32px 0 24px 0;">';
            $message .= '<table style="width:100%;border-collapse:collapse;font-size:1em;">';
            $message .= '<tr><td colspan="2" style="padding:10px 0 4px 0;font-weight:600;color:#0d6efd;font-size:1.1em;">Booking Details</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;width:40%">Booking ID</td><td style="padding:4px 0;">#' . $invoice['booking_id'] . '</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Homestay</td><td style="padding:4px 0;">' . htmlspecialchars($invoice['homestay_name']) . '</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Address</td><td style="padding:4px 0;">' . htmlspecialchars($invoice['address']) . '</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Check-in</td><td style="padding:4px 0;">' . date('M j, Y', strtotime($invoice['check_in_date'])) . '</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Check-out</td><td style="padding:4px 0;">' . date('M j, Y', strtotime($invoice['check_out_date'])) . '</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Total Guests</td><td style="padding:4px 0;">' . $invoice['total_guests'] . '</td></tr>';
            $message .= '<tr><td colspan="2" style="padding:18px 0 4px 0;font-weight:600;color:#0d6efd;font-size:1.1em;">Payment Details</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Amount</td><td style="padding:4px 0;">RM ' . number_format($invoice['amount'], 2) . '</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Discount</td><td style="padding:4px 0;">RM ' . number_format($invoice['discount'], 2) . '</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Payment Method</td><td style="padding:4px 0;">' . ucfirst(str_replace('_', ' ', $invoice['payment_method'])) . '</td></tr>';
            $message .= '<tr><td style="color:#888;padding:4px 0;">Payment Date</td><td style="padding:4px 0;">' . ($invoice['payment_date'] ? date('M j, Y', strtotime($invoice['payment_date'])) : '-') . '</td></tr>';
            $message .= '<tr style="background:#f8f8f8;font-weight:700;font-size:1.1em;">';
            $message .= '<td style="padding:12px 0 8px 0;color:#0d6efd;">Total Paid</td>';
            $message .= '<td style="padding:12px 0 8px 0;color:#0d6efd;">RM ' . number_format($invoice['amount'] - $invoice['discount'], 2) . '</td>';
            $message .= '</tr>';
            $message .= '</table>';
            $message .= '</div>';
            $message .= '<div style="margin-top:32px;color:#888;font-size:0.97em;">If you have any questions, just reply to this email or contact us at <a href="mailto:noreplyefzeecottage@gmail.com" style="color:#0d6efd;text-decoration:none;">noreplyefzeecottage@gmail.com</a>.</div>';
            $message .= '<div style="margin-top:24px;color:#bbb;font-size:0.9em;text-align:center;">&copy; ' . date('Y') . ' EFZEE COTTAGE. All rights reserved.</div>';
            $message .= '</div></div></div>';
        } elseif ($status === 'cancelled') {
            $subject = 'Booking Payment Rejected';
            $message = "Dear $name,<br><br>Unfortunately, your booking payment has been rejected. Please contact us for further assistance.<br><br>Best regards,<br>EFZEE COTTAGE";
        }

        if ($subject && $message) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // e.g., smtp.gmail.com
                $mail->SMTPAuth = true;
                $mail->Username = 'noreplyefzeecottage@gmail.com'; // SMTP username
                $mail->Password = 'vujn kumi bfdl yntf'; // SMTP password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // or PHPMailer::ENCRYPTION_SMTPS
                $mail->Port = 587;

                //Recipients
                $mail->setFrom('noreplyefzeecottage@gmail.com', 'EFZEE COTTAGE');
                $mail->addAddress($to, $name);

                //Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();
            } catch (Exception $e) {
                error_log('Mailer Error: ' . $mail->ErrorInfo);
                echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
                exit;
            }
        }
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update booking status']);
}
?>