<?php
session_start();

// Database connection (using same config as mybooking.php)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'efzeecottage';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit a review']);
    exit;
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $homestay_id = filter_input(INPUT_POST, 'homestay_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'ratings', FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user']['user_id'];

    // Debug: Log received data
    error_log("Received data - homestay_id: $homestay_id, ratings: $rating, comment: $comment, user_id: $user_id");

    if (!$homestay_id || !$rating || !$comment) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
        exit;
    }

    // Check if user has completed a booking for this homestay
    $bookingCheck = $conn->prepare("SELECT booking_id 
        FROM bookings 
        WHERE user_id = ? 
        AND homestay_id = ? 
        AND (status = 'completed' OR status = 'confirmed' OR check_out_date <= NOW())
        LIMIT 1");

    if (!$bookingCheck) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $bookingCheck->bind_param('ii', $user_id, $homestay_id);
    $bookingCheck->execute();
    $bookingResult = $bookingCheck->get_result();

    if ($bookingResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You can only review homestays after your stay is completed.'
        ]);
        exit;
    }

    $booking = $bookingResult->fetch_assoc();
    $booking_id = $booking['booking_id'];

    // Check if user has already reviewed this booking
    $reviewCheck = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ? LIMIT 1");
    if (!$reviewCheck) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $reviewCheck->bind_param('i', $booking_id);
    $reviewCheck->execute();

    if ($reviewCheck->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted a review for this booking']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert the review - make sure column names match exactly
        $insertQuery = "INSERT INTO reviews (booking_id, user_id, homestay_id, ratings, comment, status) VALUES (?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($insertQuery);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param('iiiis', $booking_id, $user_id, $homestay_id, $rating, $comment);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $review_id = $stmt->insert_id;

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Review submitted successfully and is pending approval',
            'review_id' => $review_id
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();

        error_log('Error submitting review: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit review: ' . $e->getMessage()
        ]);
    }

    if (isset($stmt)) {
        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>