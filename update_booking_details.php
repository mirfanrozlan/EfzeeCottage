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
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update booking status']);
}
?>