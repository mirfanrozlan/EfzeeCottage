document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.querySelector('.user-dropdown');
    const dropdownContent = document.querySelector('.dropdown-content');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    // Mobile menu toggle
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            // Update ARIA attributes
            const isExpanded = navLinks.classList.contains('active');
            this.setAttribute('aria-expanded', isExpanded);
            // Toggle icon between bars and times
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuBtn.contains(event.target) && !navLinks.contains(event.target)) {
                navLinks.classList.remove('active');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
                const icon = mobileMenuBtn.querySelector('i');
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            }
        });
    }

    if (userDropdown && dropdownContent) {
        // Handle click outside to close dropdown
        document.addEventListener('click', function(event) {
            if (!userDropdown.contains(event.target)) {
                dropdownContent.style.display = 'none';
            }
        });

        // Toggle dropdown on button click
        const userButton = userDropdown.querySelector('.user-button');
        if (userButton) {
            userButton.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const isVisible = dropdownContent.style.display === 'block';
                dropdownContent.style.display = isVisible ? 'none' : 'block';
            });
        }

        // Prevent dropdown from closing when clicking inside
        dropdownContent.addEventListener('click', function(event) {
            // Allow event propagation for form submissions
            if (!event.target.closest('form')) {
                event.stopPropagation();
            }
        });

        // Add hover effect for dropdown items
        const dropdownItems = dropdownContent.querySelectorAll('.dropdown-item, .dropdown-btn');
        dropdownItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });

        // Handle keyboard navigation
        userButton.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                dropdownContent.style.display = 'block';
                const firstItem = dropdownContent.querySelector('a, button');
                if (firstItem) firstItem.focus();
            }
        });

        // Close dropdown when pressing Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && dropdownContent.style.display === 'block') {
                dropdownContent.style.display = 'none';
                userButton.focus();
            }
        });
    }
});