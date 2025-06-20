<?php
header('Content-Type: application/javascript');
session_start();
?>

// DOM Elements
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const navLinks = document.querySelector('.nav-links');
const sections = document.querySelectorAll('.parallax-section');
const navDots = document.querySelectorAll('.active-section a');
const loginModal = document.getElementById('loginModal');
const loginBtn = document.getElementById('loginBtn');
const closeBtn = document.querySelector('.close');
const tabBtns = document.querySelectorAll('.tab-btn');
const bookingForm = document.getElementById('bookingForm');
const homestaySelect = document.getElementById('homestaySelect');
const checkInDate = document.getElementById('checkInDate');
const checkOutDate = document.getElementById('checkOutDate');

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    initMobileMenu();
    initNavbarScroll();
    initActiveSection();
    initModal();
    initBooking();
    initDatePickers();
    checkLoginStatus();

    // Set initial dates
    const today = new Date();
    if (checkInDate) checkInDate.valueAsDate = today;
    if (checkOutDate) checkOutDate.valueAsDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
});

// Functions
function initMobileMenu() {
    if (!mobileMenuBtn || !navLinks) return;
    
    mobileMenuBtn.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
            '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
    });
}

function initNavbarScroll() {
    window.addEventListener('scroll', () => {
        document.querySelector('.navbar')?.classList.toggle('scrolled', window.scrollY > 50);
    });
}

function initActiveSection() {
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
            dot.classList.toggle('active', dot.getAttribute('href') === `#${current}`);
        });

        document.querySelectorAll('.nav-links a').forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
        });
    });
}

function initModal() {
    if (!loginModal) return;

    // Modal toggle
    loginBtn?.addEventListener('click', () => toggleModal(true));
    closeBtn?.addEventListener('click', () => toggleModal(false));
    window.addEventListener('click', (e) => e.target === loginModal && toggleModal(false));

    // Tab switching
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelector('.tab-btn.active')?.classList.remove('active');
            btn.classList.add('active');

            const tab = btn.getAttribute('data-tab');
            document.querySelectorAll('.auth-form').forEach(form => {
                form.style.display = form.id === `${tab}Form` ? 'flex' : 'none';
            });
        });
    });

    // Handle form submissions
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');

    loginForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(loginForm);
        
        try {
            const response = await fetch(loginForm.action, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                window.location.reload();
            } else {
                showAlert('Login Failed', 'Invalid email or password.', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            showAlert('Error', 'An error occurred during login.', 'error');
        }
    });

    signupForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(signupForm);
        
        try {
            const response = await fetch(signupForm.action, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                showAlert('Success', 'Account created successfully! Please log in.', 'success')
                    .then(() => {
                        // Switch to login tab
                        document.querySelector('[data-tab="login"]').click();
                    });
            } else {
                showAlert('Signup Failed', 'Please check your information and try again.', 'error');
            }
        } catch (error) {
            console.error('Signup error:', error);
            showAlert('Error', 'An error occurred during signup.', 'error');
        }
    });
}

function toggleModal(show) {
    if (!loginModal) return;
    loginModal.style.display = show ? 'flex' : 'none';
    document.body.style.overflow = show ? 'hidden' : 'auto';
}

function checkLoginStatus() {
    const isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;
    const loginBtn = document.getElementById('loginBtn');
    const loginMessage = document.getElementById('loginMessage');
    const bookingForm = document.getElementById('bookingForm');

    if (loginBtn) loginBtn.style.display = isLoggedIn ? 'none' : 'block';
    if (loginMessage) loginMessage.style.display = isLoggedIn ? 'none' : 'block';
    if (bookingForm) bookingForm.style.display = isLoggedIn ? 'block' : 'none';
}

function showAlert(title, text, icon) {
    return Swal.fire({
        title,
        text,
        icon,
        confirmButtonColor: getComputedStyle(document.documentElement)
            .getPropertyValue('--secondary-color').trim()
    });
}
