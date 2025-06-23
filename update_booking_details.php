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
            $subject = 'Booking Payment Confirmed ✅';
            $message = "
            <html>
            <body style='font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa;'>
                <div style='max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <img src='image/logo.png' alt='EFZEE COTTAGE' style='max-width: 200px; height: auto;'>
                    </div>
                    <div style='border-left: 4px solid #4caf50; padding-left: 15px; margin-bottom: 30px;'>
                        <h2 style='color: #2e7d32; margin: 0; font-size: 24px;'>Booking Confirmed</h2>
                        <p style='color: #666; margin: 5px 0 0;'>Thank you for choosing EFZEE COTTAGE</p>
                    </div>
                    <p style='color: #333; line-height: 1.6; margin-bottom: 20px;'>Dear <strong>$name</strong>,</p>
                    <p style='color: #333; line-height: 1.6; margin-bottom: 20px;'>Great news! 🎉 Your booking payment has been <strong style='color: #2e7d32;'>successfully confirmed</strong>. We are excited to welcome you to EFZEE COTTAGE!</p>
                    <div style='background-color: #f8fdf9; border: 1px solid #e8f5e9; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                        <p style='color: #2e7d32; margin: 0; font-size: 16px;'>✨ What's Next?</p>
                        <p style='color: #666; margin: 10px 0 0; font-size: 14px;'>If you have any questions or special requests, our team is here to help make your stay memorable.</p>
                    </div>
                    <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;'>
                        <p style='color: #666; margin: 0; font-size: 14px;'>Best regards,<br><strong>EFZEE COTTAGE Team</strong></p>
                    </div>
                    <div style='margin-top: 30px; text-align: center; font-size: 12px; color: #999;'>
                        <p style='margin: 5px 0;'>This is an automated message, please do not reply directly to this email.</p>
                        <p style='margin: 5px 0;'>© 2024 EFZEE COTTAGE. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
        } elseif ($status === 'cancelled') {
            $subject = 'Booking Payment Status Update ❗';
            $message = "
            <html>
            <body style='font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa;'>
                <div style='max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        // <img src='https://efzeecottage.com/image/logo.png' alt='EFZEE COTTAGE' style='max-width: 200px; height: auto;'>
                    </div>
                    <div style='border-left: 4px solid #f44336; padding-left: 15px; margin-bottom: 30px;'>
                        <h2 style='color: #c62828; margin: 0; font-size: 24px;'>Payment Status Update</h2>
                        <p style='color: #666; margin: 5px 0 0;'>Important Information About Your Booking</p>
                    </div>
                    <p style='color: #333; line-height: 1.6; margin-bottom: 20px;'>Dear <strong>$name</strong>,</p>
                    <p style='color: #333; line-height: 1.6; margin-bottom: 20px;'>We regret to inform you that your booking payment could not be confirmed at this time. Our team is ready to assist you with resolving any payment issues.</p>
                    <div style='background-color: #fef8f8; border: 1px solid #ffebee; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                        <p style='color: #c62828; margin: 0; font-size: 16px;'>📞 Contact Us</p>
                        <p style='color: #666; margin: 10px 0 0; font-size: 14px;'>Phone: +60 12-345 6789<br>Email: support@efzeecottage.com</p>
                    </div>
                    <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;'>
                        <p style='color: #666; margin: 0; font-size: 14px;'>Best regards,<br><strong>EFZEE COTTAGE Team</strong></p>
                    </div>
                    <div style='margin-top: 30px; text-align: center; font-size: 12px; color: #999;'>
                        <p style='margin: 5px 0;'>This is an automated message, please do not reply directly to this email.</p>
                        <p style='margin: 5px 0;'>© 2024 EFZEE COTTAGE. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
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