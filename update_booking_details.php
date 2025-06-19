<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized access');
}

// Get booking ID and status
$booking_id = $_POST['booking_id'] ?? 0;
$status = $_POST['status'] ?? 'pending';

// Update booking status in the database
$query = "UPDATE bookings SET status = ? WHERE booking_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('si', $status, $booking_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    require 'vendor/autoload.php';

    // Fetch user email and name for sending notification
    $user_query = "SELECT u.email, u.name FROM users u JOIN bookings b ON u.user_id = b.user_id WHERE b.booking_id = ?";
    $user_stmt = $conn->prepare($user_query);
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
        } else {
            // For other statuses, no email sent
            $subject = '';
            $message = '';
        }
        if ($subject && $message) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                // Update the following SMTP settings to your SMTP server details
                $mail->Host = 'smtp.gmail.com'; // e.g., smtp.gmail.com
                $mail->SMTPAuth = true;
                $mail->Username = 'noreplyefzeecottage@example.com'; // SMTP username
                $mail->Password = 'vujn kumi bfdl yntf'; // SMTP password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // or PHPMailer::ENCRYPTION_SMTPS
                $mail->Port = 587;

                //Recipients
                $mail->setFrom('noreplyefzeecottage@example.com', 'EFZEE COTTAGE');
                $mail->addAddress($to, $name);

                //Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();
            } catch (Exception $e) {
                error_log('Mailer Error: ' . $mail->ErrorInfo);
                // Optionally, you can return the error message in the JSON response for debugging
                // echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
            }
        }
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update booking status']);
}
?>