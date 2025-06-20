<?php
require_once 'include/config.php';

// Initialize variables
$user_id = $_SESSION['user']['user_id'] ?? 0;
$is_returning_customer = false;
$booked_dates = [];
$selected_homestay_id = isset($_GET['homestay_id']) ? intval($_GET['homestay_id']) : null;

// Check returning customer status if logged in
if ($user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE user_id = ? AND status != 'cancelled'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $is_returning_customer = $result['booking_count'] > 0;
    $stmt->close();
}

// Get booked dates for selected homestay
if ($selected_homestay_id) {
    $stmt = $conn->prepare("SELECT check_in_date, check_out_date FROM bookings WHERE status != 'cancelled' AND homestay_id = ?");
    $stmt->bind_param("i", $selected_homestay_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['check_in_date']);
        $end = new DateTime($row['check_out_date']);
        for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
            $booked_dates[] = $date->format('Y-m-d');
        }
    }
    $stmt->close();
}

// Get available homestays
$homestays = [];
$stmt = $conn->prepare("SELECT * FROM homestays WHERE status = 'available' ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $homestays[] = $row;
}
$stmt->close();
?>

<section id="booking" class="parallax-section">
    <div class="parallax-content">
        <div class="loyalty-card">
            <h3><i class="fas fa-tag"></i> Returning Customer Status</h3>
            <?php if ($user_id): ?>
                <div class="loyalty-benefits">
                    <h4>Your Benefits:</h4>
                    <ul>
                        <?php if ($is_returning_customer): ?>
                            <li><i class="fas fa-check"></i> Returning Customer: RM20 discount</li>
                        <?php else: ?>
                            <li><i class="fas fa-info-circle"></i> Book your first stay to qualify for returning customer
                                discount!</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p>Sign in to view your loyalty status and earn rewards!</p>
            <?php endif; ?>
        </div>

        <h1>Book Your Stay</h1>
        <p class="subtitle">Reserve Your Perfect Getaway</p>

        <?php if (!$user_id): ?>
            <div id="loginMessage" class="login-message alert alert-warning">
                <p><i class="fas fa-exclamation-circle"></i> Please login to complete your booking</p>
            </div>
        <?php endif; ?>

        <!-- Calendar and Homestay Selection -->
        <div class="calendar-container">
            <h3>Check Availability</h3>

            <!-- Homestay Selection -->
            <form method="get" class="homestay-selector">
                <div class="form-group">
                    <label for="homestay_id"><strong>Select Homestay:</strong></label>
                    <select name="homestay_id" id="homestay_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Choose a homestay...</option>
                        <?php foreach ($homestays as $homestay): ?>
                            <option value="<?= $homestay['homestay_id'] ?>"
                                <?= ($selected_homestay_id == $homestay['homestay_id']) ? 'selected' : '' ?>
                                data-price="<?= $homestay['price_per_night'] ?>"
                                data-max-guests="<?= $homestay['max_guests'] ?>">
                                <?= htmlspecialchars($homestay['name']) ?> -
                                RM<?= number_format($homestay['price_per_night'], 2) ?>/night
                                (Max <?= $homestay['max_guests'] ?> guests)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($selected_homestay_id): ?>
                <?php
                $today = new DateTime();
                $month = $today->format('F Y');
                $days_in_month = $today->format('t');
                $first_day = new DateTime($today->format('Y-m-01'));
                $starting_day = $first_day->format('w'); // 0-6 (Sun-Sat)
                ?>

                <!-- Calendar Grid -->
                <div id="calendar" class="mt-4">
                    <div class="calendar-grid">
                        <h4 class="text-center"><?= $month ?></h4>
                        <div class="calendar-header">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>

                        <div class="calendar-days">
                            <?php
                            // Empty cells for days before the first day of month
                            for ($i = 0; $i < $starting_day; $i++) {
                                echo "<div class='calendar-day empty'></div>";
                            }

                            // Calendar days
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $date_str = $today->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $current_date = new DateTime($date_str);
                                $is_booked = in_array($date_str, $booked_dates);
                                $is_past = $current_date < new DateTime('today');

                                $classes = ['calendar-day'];
                                if ($is_past) {
                                    $classes[] = 'past';
                                    $status = 'Past';
                                } elseif ($is_booked) {
                                    $classes[] = 'booked';
                                    $status = 'Booked';
                                } else {
                                    $classes[] = 'available';
                                    $status = 'Available';
                                }

                                echo "<div class='" . implode(' ', $classes) . "' 
                                          data-date='" . $date_str . "' 
                                          title='" . $status . "'>";
                                echo $day;
                                if ($is_booked && !$is_past) {
                                    echo "<span class='booked-label'>Booked</span>";
                                }
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Please select a homestay to view availability.
                </div>
            <?php endif; ?>


            <?php if ($user_id): ?>
                <!-- Booking Form -->
                <form id="bookingForm" class="booking-form" method="POST" action="process/process_booking.php"
                    enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Check-in Date <span class="text-danger">*</span></label>
                            <input type="date" name="check_in_date" id="checkInDate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Check-out Date <span class="text-danger">*</span></label>
                            <input type="date" name="check_out_date" id="checkOutDate" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Number of Guests</label>
                            <input type="number" name="total_guests" id="totalGuests" min="1" max="10" required
                                oninput="validateGuests(this)">
                            <div id="guestError" class="error-message"
                                style="display: none; color: red; font-size: 0.8em; margin-top: 5px;"></div>
                        </div>
                        <div class="form-group">
                            <label>Homestay</label>
                            <select name="homestay_id" id="homestaySelect" required>
                                <option value="" disabled selected>Select Homestay</option>
                                <?php
                                $homestaysQuery = "SELECT * FROM homestays WHERE status = 'available' ORDER BY name";
                                $homestaysResult = $conn->query($homestaysQuery);
                                while ($homestay = $homestaysResult->fetch_assoc()):
                                    ?>
                                    <option value="<?= $homestay['homestay_id'] ?>"
                                        data-price="<?= $homestay['price_per_night'] ?>"
                                        data-max-guests="<?= $homestay['max_guests'] ?>">
                                        <?= htmlspecialchars($homestay['name']) ?> -
                                        RM<?= number_format($homestay['price_per_night'], 2) ?>/night
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="price-breakdown">
                        <h3>Price Breakdown</h3>
                        <div class="price-row">
                            <span>Base Rate (per night):</span>
                            <span id="baseRate">RM 0.00</span>
                        </div>
                        <div class="price-row">
                            <span>Number of Nights:</span>
                            <span id="numberOfNights">0</span>
                        </div>

                        <div class="price-row subtotal-row">
                            <span>Subtotal:</span>
                            <span id="subtotal">RM 0.00</span>
                        </div>

                        <div class="price-row returning-discount-row" style="display: none;">
                            <span><i class="fas fa-tag"></i> Returning Customer Discount:</span>
                            <span id="returningDiscountAmount">-RM 20.00</span>
                        </div>

                        <div class="price-row total-row">
                            <span><strong>Total:</strong></span>
                            <span id="totalPrice"><strong>RM 0.00</strong></span>
                        </div>
                    </div>
                    <input type="hidden" name="total_price" id="totalPriceInput" value="0.00">

                    <div id="qrPaymentSection">
                        <h3>Scan QR Code to Pay</h3>
                        <div class="qr-code-container">
                            <img src="assets/images/payment_qr.jpg" alt="Payment QR Code" class="qr-code-image"
                                style="width: 200px; height: auto; border: 2px solid #4CAF50; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); transition: transform 0.2s;"
                                onmouseover="this.style.transform='scale(1.05)'"
                                onmouseout="this.style.transform='scale(1)'">

                        </div>
                        <div class="receipt-upload">
                            <h4>Upload Payment Receipt</h4>
                            <input type="file" name="payment_receipt" id="paymentReceipt" accept="image/*,.pdf"
                                class="receipt-input" required>
                            <div id="fileError" class="error-message"
                                style="display: none; color: red; font-size: 0.8em; margin-top: 5px;"></div>
                            <p class="receipt-note">Please upload your payment receipt (Image or PDF, max 5MB)</p>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Book Now</button>
                </form>
            <?php endif; ?>


            <!-- Include SweetAlert2 -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <!-- Include booking.js -->
            <script src="js/booking.js"></script>

            <script>
                function validateGuests(input) {
                    const maxGuests = parseInt(document.querySelector('#homestaySelect option:checked')?.dataset.maxGuests) || 10;
                    const guestCount = parseInt(input.value) || 0;
                    const errorDiv = document.getElementById('guestError');

                    if (guestCount < 1) {
                        errorDiv.textContent = 'Number of guests must be at least 1';
                        errorDiv.style.display = 'block';
                        return false;
                    } else if (guestCount > maxGuests) {
                        errorDiv.textContent = `Maximum ${maxGuests} guests allowed for this homestay`;
                        errorDiv.style.display = 'block';
                        return false;
                    } else {
                        errorDiv.style.display = 'none';
                        return true;
                    }
                }

                function calculateTotal() {
                    const baseRate = parseFloat(document.querySelector('#homestaySelect option:checked')?.dataset.price) || 0;
                    const checkIn = new Date(document.getElementById('checkInDate').value);
                    const checkOut = new Date(document.getElementById('checkOutDate').value);
                    const numberOfNights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));

                    if (numberOfNights <= 0) {
                        return;
                    }

                    const subtotal = baseRate * numberOfNights;
                    document.getElementById('baseRate').textContent = `RM ${baseRate.toFixed(2)}`;
                    document.getElementById('numberOfNights').textContent = numberOfNights;
                    document.getElementById('subtotal').textContent = `RM ${subtotal.toFixed(2)}`;

                    const isReturningCustomer = <?php echo isset($_SESSION['user']) && $is_returning_customer ? 'true' : 'false'; ?>;
                    const returningCustomerDiscount = isReturningCustomer ? 20 : 0;

                    const returningDiscountRow = document.querySelector('.returning-discount-row');
                    if (isReturningCustomer) {
                        returningDiscountRow.style.display = 'flex';
                        document.getElementById('returningDiscountAmount').textContent = `-RM ${returningCustomerDiscount.toFixed(2)}`;
                    } else {
                        returningDiscountRow.style.display = 'none';
                    }

                    const total = subtotal - returningCustomerDiscount;
                    document.getElementById('totalPrice').textContent = `RM ${total.toFixed(2)}`;
                    document.getElementById('totalPriceInput').value = total.toFixed(2);
                }

                document.getElementById('checkInDate').addEventListener('change', calculateTotal);
                document.getElementById('checkOutDate').addEventListener('change', calculateTotal);
                document.getElementById('homestaySelect').addEventListener('change', calculateTotal);
            </script>

            <?php if (isset($_GET['message'])): ?>
                <script>
                    swal({
                        title: "Notification",
                        text: "<?= htmlspecialchars($_GET['message']) ?>",
                        icon: "<?= strpos($_GET['message'], 'Error:') === 0 ? 'error' : 'success' ?>",
                        button: "OK",
                    });
                </script>
            <?php endif; ?>

        </div>
    </div>
</section>