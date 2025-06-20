<!-- Login Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
            <img src="image/logo.png" alt="EFZEE COTTAGE" class="modal-logo">
            <div class="tabs">
                <button class="tab-btn active" data-tab="login">Login</button>
                <button class="tab-btn" data-tab="signup">Sign Up</button>
            </div>
        </div>


        <!-- Login Form -->
        <form id="loginForm" class="auth-form" method="POST" action="">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="#" class="forgot-password">Forgot Password?</a>
            </div>
            <input type="hidden" name="action" value="login">
            <button type="submit" class="btn-submit">Login</button>
        </form>

        <!-- Sign Up Form -->
        <form id="signupForm" class="auth-form" style="display: none;" method="POST" action="">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Create Password" required>
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <div class="form-group">
                <i class="fas fa-phone"></i>
                <input type="tel" name="phone" placeholder="Phone Number" required>
            </div>
            <input type="hidden" name="action" value="signup">
            <button type="submit" class="btn-submit">Create Account</button>
            <p class="terms-text">By signing up, you agree to our <a href="#">Terms of Service</a> and <a
                    href="#">Privacy Policy</a></p>
        </form>
    </div>
</div>