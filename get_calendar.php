<?php
require_once 'include/config.php';

if (!isset($_GET['homestay_id'])) {
    echo json_encode(['error' => 'Homestay ID is required']);
    exit;
}

$selected_homestay_id = intval($_GET['homestay_id']);
$booked_dates = [];

// Get booked dates for selected homestay
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

// Generate calendar HTML
$today = new DateTime();
$month = $today->format('F Y');
$days_in_month = $today->format('t');
$first_day = new DateTime($today->format('Y-m-01'));
$starting_day = $first_day->format('w'); // 0-6 (Sun-Sat)

$calendar_html = "";
$calendar_html .= "<div class='calendar-grid'>";
$calendar_html .= "<h4 class='text-center'>{$month}</h4>";
$calendar_html .= "<div class='calendar-header'>";
$calendar_html .= "<div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>";
$calendar_html .= "</div>";
$calendar_html .= "<div class='calendar-days'>";

// Empty cells for days before the first day of month
for ($i = 0; $i < $starting_day; $i++) {
    $calendar_html .= "<div class='calendar-day empty'></div>";
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

    $calendar_html .= "<div class='" . implode(' ', $classes) . "' data-date='" . $date_str . "' title='" . $status . "'>";
    $calendar_html .= $day;
    if ($is_booked && !$is_past) {
        $calendar_html .= "<span class='booked-label'>Booked</span>";
    }
    $calendar_html .= "</div>";
}

$calendar_html .= "</div></div>";

echo json_encode([
    'calendar' => $calendar_html,
    'booked_dates' => $booked_dates
]);