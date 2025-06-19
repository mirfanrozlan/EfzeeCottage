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

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Active Section Indicator
        const sections = document.querySelectorAll('.parallax-section');
        const navDots = document.querySelectorAll('.active-section a');

        window.addEventListener('scroll', () => {
            let current = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;

                if (pageYOffset >= (sectionTop - sectionHeight / 3)) {
                    current = section.getAttribute('id');
                }
            });

            navDots.forEach(dot => {
                dot.classList.remove('active');
                if (dot.getAttribute('href') === `#${current}`) {
                    dot.classList.add('active');
                }
            });

            // Update nav links
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });

        // Modal Functionality
        const loginModal = document.getElementById('loginModal');
        const loginBtn = document.getElementById('loginBtn');
        const closeBtn = document.querySelector('.close');
        const tabBtns = document.querySelectorAll('.tab-btn');

        loginBtn.addEventListener('click', () => {
            loginModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        closeBtn.addEventListener('click', () => {
            loginModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        window.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Switch tabs
                document.querySelector('.tab-btn.active').classList.remove('active');
                btn.classList.add('active');

                // Show corresponding form
                const tab = btn.getAttribute('data-tab');
                document.querySelectorAll('.auth-form').forEach(form => {
                    form.style.display = 'none';
                });
                document.getElementById(`${tab}Form`).style.display = 'flex';
            });
        });

        // Check login status on page load
        function checkLoginStatus() {
            const isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;

            if (isLoggedIn) {
                document.getElementById('loginBtn').style.display = 'none';
                document.getElementById('logoutBtn').style.display = 'block';
                document.getElementById('bookingForm').style.display = 'block';
                document.getElementById('loginMessage').style.display = 'none';
            } else {
                document.getElementById('loginBtn').style.display = 'block';
                document.getElementById('logoutBtn').style.display = 'none';
                document.getElementById('bookingForm').style.display = 'none';
                document.getElementById('loginMessage').style.display = 'block';
            }
        }

        // Initialize on page load
        checkLoginStatus();
    </script>
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
