<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Include Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<!-- Include login modal styles -->
<link rel="stylesheet" href="css/login_modal.css">

<nav class="navbar" style="background-color:rgb(19 90 116 / 90%)">
    <div class="nav-brand">
        <img src="image/logo.png" alt="EFZEE COTTAGE" class="nav-logo" style="width: 15%; height: 15%">
    </div>
    <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>
    <div class="nav-links">
        <a href="homepage.php">Home</a>
        <a href="homepage.php#about">About Us</a>
        <a href="homepage.php#gallery">Gallery</a>
        <a href="homepage.php#booking">Book Now</a>
        <a href="homepage.php#reviews">Reviews</a>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="mybooking.php" class="active">My Bookings</a>
        <?php endif; ?>
        <!-- <a href="#reviews">Reviews</a> -->
        <div class="nav-user-menu">
            <?php if (!isset($_SESSION['user'])): ?>
                <button id="loginBtn" class="nav-button"><i class="fas fa-user"></i> Login / Sign Up</button>
            <?php else: ?>
                <div class="user-dropdown">
                    <button class="nav-button user-button">
                        <i class="fas fa-user-circle"></i>Account
                    </button>
                    <div class="dropdown-content">
                        <div class="user-info">
                            <i class="fas fa-user-circle profile-icon"></i>
                            <div class="user-details">
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                                <span class="user-email"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></span>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-divider"></div>
                        <form id="logoutForm" action="logout.php" method="POST">
                            <button type="submit" class="dropdown-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                                <span class="item-description">Sign out of your account</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Include login modal -->
<?php include 'components/login_modal.php'; ?>
<!-- Include scripts -->
<script src="js/login_modal.js"></script>
<script src="js/navbar.js"></script>

<!-- Add ARIA labels and roles for accessibility -->
<script>
    // Add ARIA attributes for accessibility
    document.addEventListener('DOMContentLoaded', function () {
        const userButton = document.querySelector('.user-button');
        const dropdownContent = document.querySelector('.dropdown-content');

        if (userButton && dropdownContent) {
            userButton.setAttribute('aria-haspopup', 'true');
            userButton.setAttribute('aria-expanded', 'false');
            dropdownContent.setAttribute('role', 'menu');

            const menuItems = dropdownContent.querySelectorAll('a, button');
            menuItems.forEach(item => {
                item.setAttribute('role', 'menuitem');
                item.setAttribute('tabindex', '-1');
            });

            // Update ARIA states on dropdown toggle
            userButton.addEventListener('click', function () {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
            });
        }
    });
</script>
<!-- Active Section Indicator -->
<div class="active-section">
    <a href="#home" class="active" data-section="Home"></a>
    <a href="#about" data-section="About"></a>
    <a href="#gallery" data-section="Gallery"></a>
    <a href="#booking" data-section="Booking"></a>
</div>