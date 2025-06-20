<?php
require_once 'include/config.php';
require_once 'include/login_signup.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EFZEE COTTAGE - Luxury Retreat in Batu Pahat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Navigation Bar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Home Section -->
    <?php include 'templates/home.php'; ?>

    <!-- About Section -->
    <?php include 'templates/about.php'; ?>

    <!-- Gallery Section -->
    <?php include 'templates/gallery.php'; ?>

    <!-- Booking Section -->
    <?php include 'templates/booking.php'; ?>

    <!-- Reviews Section -->
    <!-- <?php include 'templates/reviews.php'; ?> -->

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- Login Modal -->
    <?php include 'components/login_modal.php'; ?>

    <!-- All Scripts Here -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/main.php"></script>
</body>

</html>

<?php
if (isset($_SESSION['login_success'])) {
    echo "<script>
    Swal.fire({
        title: 'Login Successful!',
        text: " . json_encode($_SESSION['login_success']) . ",
        icon: 'success',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['login_success']);
}

if (isset($_SESSION['login_error'])) {
    echo "<script>
    Swal.fire({
        title: 'Login Failed',
        text: " . json_encode($_SESSION['login_error']) . ",
        icon: 'error',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['signup_success'])) {
    echo "<script>
    Swal.fire({
        title: 'Account Created!',
        text: " . json_encode($_SESSION['signup_success']) . ",
        icon: 'success',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['signup_success']);
}

if (isset($_SESSION['signup_error'])) {
    echo "<script>
    Swal.fire({
        title: 'Signup Failed',
        text: " . json_encode($_SESSION['signup_error']) . ",
        icon: 'error',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['signup_error']);
}
?>
