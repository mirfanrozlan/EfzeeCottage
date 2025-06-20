<section id="reviews" class="parallax-section">
    <div class="parallax-content">
        <h1 class="section-title">Guest Reviews</h1>
        <p class="section-subtitle">What Our Guests Say About Us</p>

        <div class="reviews-container reviews-grid">
            <?php
            // Fetch approved reviews with user and homestay details
            $stmt = $conn->prepare("SELECT r.*, u.name as user_name, h.name as homestay_name 
                                      FROM reviews r 
                                      JOIN users u ON r.user_id = u.user_id 
                                      JOIN homestays h ON r.homestay_id = h.homestay_id 
                                      WHERE r.status = 'approved' 
                                      LIMIT 6");
            $stmt->execute();
            $reviews = $stmt->get_result();

            while ($review = $reviews->fetch_assoc()):
                $rating_stars = str_repeat('<i class="fas fa-star"></i>', $review['ratings']) .
                    str_repeat('<i class="far fa-star"></i>', 5 - $review['ratings']);
                ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="user-info">
                            <div class="user-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="user-details">
                                <h4 class="user-name"><?php echo htmlspecialchars($review['user_name']); ?></h4>
                                <div class="homestay-name"><i class="fas fa-home"></i>
                                    <?php echo htmlspecialchars($review['homestay_name']); ?></div>
                            </div>
                        </div>
                        <div class="ratings"><?php echo $rating_stars; ?></div>
                    </div>
                    <div class="review-content">
                        <p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <div class="review-date">
                        </div>
                    </div>
                <?php endwhile;
            $stmt->close(); ?>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="write-review">
                    <h3>Write a Review</h3>
                    <form id="reviewForm" class="review-form">
                        <div class="form-group">
                            <label for="reviewHomestay">Select Homestay:</label>
                            <select name="homestay_id" id="reviewHomestay" required>
                                <?php
                                // Fetch homestays where the user has completed bookings
                                $stmt = $conn->prepare("SELECT DISTINCT h.homestay_id, h.name 
                                                  FROM homestays h 
                                                  JOIN bookings b ON h.homestay_id = b.homestay_id 
                                                  WHERE b.user_id = ? AND b.status = 'completed'");
                                $stmt->bind_param('i', $_SESSION['user_id']);
                                $stmt->execute();
                                $homestays = $stmt->get_result();

                                while ($homestay = $homestays->fetch_assoc()):
                                    echo "<option value='{$homestay['homestay_id']}'>{$homestay['name']}</option>";
                                endwhile;
                                $stmt->close();
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Rating:</label>
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="ratings" value="<?php echo $i; ?>" id="star<?php echo $i; ?>"
                                        required>
                                    <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reviewComment">Your Review:</label>
                            <textarea name="comment" id="reviewComment" rows="4" required></textarea>
                        </div>

                        <button type="submit" class="submit-review-btn">Submit Review</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
</section>