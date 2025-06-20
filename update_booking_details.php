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
            $subject = 'Booking Payment Confirmed';
            $message = "Dear $name,<br><br>Your booking payment has been confirmed. Thank you for choosing our service.<br><br>Best regards,<br>EFZEE COTTAGE";
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