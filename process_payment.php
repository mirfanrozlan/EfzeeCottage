<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to make a payment']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';

    if (!$booking_id || !$amount || !in_array($payment_method, ['qr_code', 'bank_transfer', 'e_wallet'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment information']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create payment record
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, status, payment_date) VALUES (?, ?, ?, ?, 'pending', CURDATE())");
        $stmt->bind_param('iidss', $booking_id, $user_id, $amount, $payment_method);
        $stmt->execute();
        $payment_id = $conn->insert_id;

        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET status = 'pending_approval' WHERE booking_id = ?");
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();

        // Handle receipt upload if provided
        if (isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['payment_receipt'];
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG and PDF files are allowed.');
            }

            // Create upload directory if it doesn't exist
            $upload_dir = 'uploads/receipts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $filename = 'receipt_' . $payment_id . '_' . time() . '.' . $file_type;
            $file_path = $upload_dir . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Save receipt record
                $stmt = $conn->prepare("INSERT INTO payment_receipts (payment_id, file_path, file_type) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $payment_id, $file_path, $file_type);
                $stmt->execute();

                // Send email notification to admin
                $admin_query = "SELECT email FROM users WHERE role = 'admin' LIMIT 1";
                $admin_result = $conn->query($admin_query);
                if ($admin = $admin_result->fetch_assoc()) {
                    $to = $admin['email'];
                    $subject = "New Payment Receipt Submitted - EFZEE COTTAGE";
                    $message = "A new payment receipt has been submitted for booking #$booking_id.\n";
                    $message .= "Please review the payment in the admin panel.";

                    mail($to, $subject, $message);
                }

                // Create notification for admin
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) 
                                      SELECT user_id, 'admin_alert', 'New Payment Receipt', 
                                      CONCAT('A new payment receipt has been uploaded for booking #', ?) 
                                      FROM users WHERE role = 'admin' LIMIT 1");
                $stmt->bind_param('i', $booking_id);
                $stmt->execute();
            } else {
                throw new Exception('Failed to upload receipt file.');
            }
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully. Please wait for admin approval.',
            'payment_id' => $payment_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}