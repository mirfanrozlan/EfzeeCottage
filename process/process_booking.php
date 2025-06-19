<?php
session_start();
require_once '../include/config.php';

// Check login
if (!isset($_SESSION['user'])) {
    header('Location: ../booking.php?message=User not logged in');
    exit;
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $required_fields = ['homestay_id', 'check_in_date', 'check_out_date', 'total_guests', 'payment_receipt'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field]) && empty($_FILES[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Use user_id from session (secure)
    $user_id = $_SESSION['user']['user_id'];

    // Validate and sanitize input data
    $homestay_id = filter_var($_POST['homestay_id'], FILTER_VALIDATE_INT);
    $check_in_date = date('Y-m-d', strtotime($_POST['check_in_date']));
    $check_out_date = date('Y-m-d', strtotime($_POST['check_out_date']));
    $total_guests = filter_var($_POST['total_guests'], FILTER_VALIDATE_INT);

    // Validate dates
    if ($check_in_date >= $check_out_date) {
        throw new Exception("Check-out date must be after check-in date");
    }

    // Start transaction
    $conn->begin_transaction();

    // Get returning customer status
    $returning_customer_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE user_id = ? AND status != 'cancelled'";
    $stmt = $conn->prepare($returning_customer_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $booking_count = $result['booking_count'];

    // Determine if the user is a first-time booker
    $is_first_time_booker = $booking_count === 0;

    // Calculate final price with discount if returning customer
    $final_price = $total_price;
    $discount = 0; // Initialize discount
    if (!$is_first_time_booker) {
        $discount = 20; // Apply RM20 discount for returning customers
        $final_price = $total_price - $discount; // Adjust final price
    }

    // Insert booking with final price
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, homestay_id, check_in_date, check_out_date, total_guests, status) 
                           VALUES (?, ?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iissid", $user_id, $homestay_id, $check_in_date, $check_out_date, $total_guests);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $booking_id = $conn->insert_id;

    // Insert payment with discount
    $stmt2 = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, status, payment_date, discount) 
                            VALUES (?, ?, 'qr_code', 'pending', CURDATE(), ?)");
    if (!$stmt2) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind only the necessary parameters
    $stmt2->bind_param("idi", $booking_id, $final_price, $discount); // Include discount

    if (!$stmt2->execute()) {
        throw new Exception("Execute failed: " . $stmt2->error);
    }

    $payment_id = $conn->insert_id;

    // Handle payment receipt upload
    if (isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_receipt'];
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG and PDF files are allowed.');
        }

        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            throw new Exception('File is too large. Maximum size is 5MB.');
        }

        // Create upload directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/payment_receipts/' . date('Y/m/');
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $filename = uniqid('receipt_') . '_' . time() . '.' . $file_type;
        $file_path = $upload_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception('Failed to upload file.');
        }

        // Store file information in database
        $relative_path = 'uploads/payment_receipts/' . date('Y/m/') . $filename;
        $stmt3 = $conn->prepare("INSERT INTO payment_receipts (payment_id, file_path, file_type, upload_date) 
                                VALUES (?, ?, ?, CURDATE())");
        if (!$stmt3) {
            throw new Exception("Prepare failed for receipt: " . $conn->error);
        }

        $stmt3->bind_param("iss", $payment_id, $relative_path, $file_type);
        if (!$stmt3->execute()) {
            throw new Exception("Failed to store receipt information: " . $stmt3->error);
        }
    } else {
        throw new Exception('Payment receipt is required.');
    }

    // Commit transaction
    $conn->commit();

    // Redirect back with success message
    header('Location: ../mybooking.php');
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) {
        $conn->rollback();
    }

    // Redirect back with error message
    header('Location: ../mybooking.php?message=Error: ' . urlencode($e->getMessage()));
    exit;
}
?>