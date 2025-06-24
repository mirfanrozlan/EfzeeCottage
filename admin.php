<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get booking statistics
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_bookings,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings
FROM bookings");
$stmt->execute();
$booking_stats = $stmt->get_result()->fetch_assoc();

// Handle add homestay form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_homestay'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $price_per_night = floatval($_POST['price_per_night']);
    $max_guests = intval($_POST['max_guests']);
    $bedrooms = intval($_POST['bedrooms']);
    $bathrooms = intval($_POST['bathrooms']);
    $status = trim($_POST['status']);
    
    if ($name && $description && $address && $price_per_night > 0 && $max_guests > 0 && $bedrooms >= 0 && $bathrooms >= 0 && $status) {
        $stmt = $conn->prepare("INSERT INTO homestays (name, description, address, price_per_night, max_guests, bedrooms, bathrooms, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdiiss", $name, $description, $address, $price_per_night, $max_guests, $bedrooms, $bathrooms, $status);
        if ($stmt->execute()) {
            $homestay_success = "Homestay added successfully.";
        } else {
            $homestay_error = "Failed to add homestay.";
        }
        $stmt->close();
    } else {
        $homestay_error = "Please fill in all fields correctly.";
    }
}

// Fetch all homestays
$homestays = $conn->query("SELECT * FROM homestays ORDER BY homestay_id DESC");

// Get recent bookings for calendar
$bookings_query = "SELECT b.*, h.name as homestay_name, u.name as guest_name, p.status as payment_status 
                  FROM bookings b 
                  JOIN homestays h ON b.homestay_id = h.homestay_id 
                  JOIN users u ON b.user_id = u.user_id 
                  LEFT JOIN payments p ON b.booking_id = p.booking_id 
                  ORDER BY b.check_in_date DESC";
$bookings = $conn->query($bookings_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EFZEE COTTAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/admin.css">
</head>
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
</style>

<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="text-white mb-4">Admin Panel</h3>
                <div class="nav flex-column">
                    <a href="admin.php" class="nav-link active mb-2">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="admin_homestays.php" class="nav-link mb-2">
                        <i class="fas fa-home me-2"></i> Homestays
                    </a>
                    <a href="admin_users.php" class="nav-link mb-2">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a href="admin_reviews.php" class="nav-link mb-2">
                        <i class="fas fa-star me-2"></i> Reviews
                    </a>
                    <a href="logout.php" class="nav-link mt-4 text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Bookings</h5>
                                <h3><?php echo $booking_stats['total_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Confirmed</h5>
                                <h3><?php echo $booking_stats['confirmed_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">Pending</h5>
                                <h3><?php echo $booking_stats['pending_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Cancelled</h5>
                                <h3><?php echo $booking_stats['cancelled_bookings']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="calendar-container mb-4">
                    <div id="calendar"></div>
                </div>

                <!-- Recent Bookings -->
                <div class="booking-list">
                    <h4 class="mb-4">Recent Bookings</h4>
                    <div class="mb-3">
                        <input type="text" id="bookingSearchInput" class="form-control" placeholder="Search bookings...">
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="bookingTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Guest</th>
                                    <th>Homestay</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $booking['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['homestay_name']); ?></td>
                                        <td><?php echo $booking['check_in_date']; ?></td>
                                        <td><?php echo $booking['check_out_date']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            // Get user's phone number for WhatsApp button
                                            $phone_query = "SELECT phone FROM users WHERE user_id = ?";
                                            $phone_stmt = $conn->prepare($phone_query);
                                            $phone_stmt->bind_param("i", $booking['user_id']);
                                            $phone_stmt->execute();
                                            $phone_result = $phone_stmt->get_result()->fetch_assoc();
                                            $phone = $phone_result['phone'];

                                            if ($phone) {
                                                // Format phone number for WhatsApp
                                                $whatsapp_number = preg_replace('/[^0-9]/', '', $phone);
                                                if (substr($whatsapp_number, 0, 1) === '0') {
                                                    $whatsapp_number = '6' . $whatsapp_number; // Add Malaysia country code
                                                }
                                            }
                                            ?>
                                            <button class="btn btn-sm btn-primary me-1"
                                                onclick="viewBooking(<?php echo $booking['booking_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($phone): ?>
                                                <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank"
                                                    class="btn btn-sm btn-success">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bookingModalContent">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal btn-cancel" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Today',
                    month: 'Month',
                    week: 'Week',
                    day: 'Day'
                },
                events: [
                    <?php
                    $bookings->data_seek(0);
                    while ($booking = $bookings->fetch_assoc()):
                        $color = '';
                        switch ($booking['status']) {
                            case 'confirmed':
                                $color = '#2ecc71';
                                break;
                            case 'pending':
                                $color = '#f1c40f';
                                break;
                            case 'cancelled':
                                $color = '#e74c3c';
                                break;
                        }
                        ?>
                                                                            {
                            title: '<?php echo addslashes($booking['guest_name']) . " - " . addslashes($booking['homestay_name']); ?>',
                            start: '<?php echo $booking['check_in_date']; ?>',
                            end: '<?php echo $booking['check_out_date']; ?>',
                            backgroundColor: '<?php echo $color; ?>',
                            extendedProps: {
                                booking_id: <?php echo $booking['booking_id']; ?>
                            }
                        },
                    <?php endwhile; ?>
                ],
                eventClick: function (info) {
                    viewBooking(info.event.extendedProps.booking_id);
                }
            });
            calendar.render();
        });

        function viewBooking(bookingId) {
            // Load booking details via AJAX
            fetch(`get_booking_details.php?id=${bookingId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('bookingModalContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('bookingModal')).show();
                })
                .catch(error => console.error('Error loading booking details:', error));
        }

        function updateStatus(bookingId) {
            // Implement status update logic
        }

        function confirmPayment(bookingId) {
            Swal.fire({
                title: 'Confirm Payment',
                text: 'Are you sure you want to confirm this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, confirm it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateBookingStatus(bookingId, 'confirmed');
                }
            });
        }

        function rejectPayment(bookingId) {
            Swal.fire({
                title: 'Reject Payment',
                text: 'Are you sure you want to reject this payment?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reject it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateBookingStatus(bookingId, 'cancelled');
                }
            });
        }

        function updateBookingStatus(bookingId, status) {
            fetch('update_booking_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `booking_id=${bookingId}&status=${status}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: `Booking ${status === 'confirmed' ? 'confirmed' : 'rejected'} successfully`,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Close the modal
                            const bookingModal = bootstrap.Modal.getInstance(document.getElementById('bookingModal'));
                            if (bookingModal) {
                                bookingModal.hide();
                            }
                            // Reload the page to update the calendar and booking list
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to update booking status: ' + data.message,
                            icon: 'error'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while updating the booking status',
                        icon: 'error'
                    });
                });
        }

        // Booking table search filter
        document.getElementById('bookingSearchInput').addEventListener('keyup', function() {
            var input = this.value.toLowerCase();
            var rows = document.querySelectorAll('#bookingTable tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        });

    </script>

</body>

</html>