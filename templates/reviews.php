<section id="reviews" class="efzee-reviews-section">
    <style>
        .efzee-reviews-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            overflow: hidden;
        }

        .efzee-reviews-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .efzee-reviews-title {
            font-size: 2.5rem;
            color:rgb(236, 236, 236);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .efzee-reviews-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 3px;
        }

        .efzee-reviews-subtitle {
            font-size: 1.2rem;
            color:rgb(157, 158, 158);
            margin-bottom: 40px;
        }

        .efzee-carousel-wrapper {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 60px;
        }

        .efzee-carousel-container {
            overflow: hidden;
            position: relative;
        }

        .efzee-carousel-track {
            display: flex;
            transition: transform 0.5s ease-in-out;
            gap: 30px;
            padding: 20px 0;
        }

        .efzee-carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 45px;
            height: 45px;
            background: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .efzee-carousel-nav:hover {
            background: #3498db;
            color: white;
            transform: translateY(-50%) scale(1.1);
        }

        .efzee-carousel-prev {
            left: 0;
        }

        .efzee-carousel-next {
            right: 0;
        }

        .efzee-carousel-indicators {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .efzee-carousel-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #cbd5e0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .efzee-carousel-dot.active {
            background: #3498db;
            transform: scale(1.3);
        }

        @media (max-width: 768px) {
            .efzee-reviews-section {
                padding: 60px 0;
            }

            .efzee-reviews-title {
                font-size: 2rem;
            }

            .efzee-reviews-subtitle {
                font-size: 1rem;
            }

            .efzee-carousel-wrapper {
                padding: 0 40px;
            }
        }
    </style>

    <div class="efzee-reviews-header">
        <h1 class="efzee-reviews-title">Guest Reviews</h1>
        <p class="efzee-reviews-subtitle">What Our Guests Say About Us</p>
    </div>

    <div class="efzee-carousel-wrapper">
        <button class="efzee-carousel-nav efzee-carousel-prev" aria-label="Previous reviews">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="efzee-carousel-nav efzee-carousel-next" aria-label="Next reviews">
            <i class="fas fa-chevron-right"></i>
        </button>

        <div class="efzee-carousel-container">
            <div class="efzee-carousel-track" id="reviewsTrack">
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
                    <div class="efzee-review-card">
                        <style>
                            .efzee-review-card {
                                background: white;
                                border-radius: 15px;
                                padding: 25px;
                                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                                min-width: 300px;
                                max-width: 400px;
                                transition: all 0.3s ease;
                                flex-shrink: 0;
                            }

                            .efzee-review-card:hover {
                                transform: translateY(-5px);
                                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
                            }

                            .efzee-review-header {
                                display: flex;
                                justify-content: space-between;
                                align-items: flex-start;
                                margin-bottom: 20px;
                            }

                            .efzee-user-info {
                                display: flex;
                                align-items: center;
                                gap: 15px;
                            }

                            .efzee-user-avatar {
                                width: 50px;
                                height: 50px;
                                background: linear-gradient(45deg, #3498db, #2ecc71);
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                font-size: 1.5rem;
                            }

                            .efzee-user-details {
                                display: flex;
                                flex-direction: column;
                                gap: 8px;
                            }

                            .efzee-user-name {
                                font-size: 1.1rem;
                                font-weight: 600;
                                color: #2c3e50;
                                margin: 0;
                                margin-bottom: 2px;
                            }

                            .efzee-ratings {
                                color: #f1c40f;
                                font-size: 1rem;
                                line-height: 1;
                                margin-bottom: 5px;
                            }

                            .efzee-homestay-name {
                                font-size: 0.9rem;
                                color: #7f8c8d;
                                display: flex;
                                align-items: center;
                                gap: 5px;
                            }

                            .efzee-review-content {
                                margin-top: 15px;
                            }

                            .efzee-review-text {
                                color: #34495e;
                                line-height: 1.6;
                                margin-bottom: 15px;
                            }

                            .efzee-review-date {
                                font-size: 0.9rem;
                                color: #95a5a6;
                                display: flex;
                                align-items: center;
                                gap: 5px;
                            }
                        </style>
                        <div class="efzee-review-header">
                            <div class="efzee-user-info">
                                <div class="efzee-user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="efzee-user-details">
                                    <h4 class="efzee-user-name"><?php echo htmlspecialchars($review['user_name']); ?></h4>
                                    <div class="efzee-ratings"><?php echo $rating_stars; ?></div>
                                    <div class="efzee-homestay-name">
                                        <i class="fas fa-home"></i>
                                        <?php echo htmlspecialchars($review['homestay_name']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="efzee-review-content">
                            <p class="efzee-review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                        </div>
                    </div>
                <?php endwhile;
                $stmt->close(); ?>
            </div>
        </div>
        <div class="efzee-carousel-indicators" id="carouselIndicators"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const track = document.getElementById('reviewsTrack');
            const container = track.parentElement;
            const prevButton = document.querySelector('.efzee-carousel-prev');
            const nextButton = document.querySelector('.efzee-carousel-next');
            const indicatorsContainer = document.getElementById('carouselIndicators');

            let currentIndex = 0;
            let startX;
            let scrollLeft;
            let isDragging = false;

            function updateCarouselMetrics() {
                const cards = track.querySelectorAll('.efzee-review-card');
                const cardWidth = cards[0].offsetWidth;
                const containerWidth = container.offsetWidth;
                const visibleCards = Math.floor(containerWidth / cardWidth);
                const maxIndex = Math.max(0, cards.length - visibleCards);

                // Update indicators
                indicatorsContainer.innerHTML = '';
                for (let i = 0; i <= maxIndex; i++) {
                    const dot = document.createElement('div');
                    dot.className = `efzee-carousel-dot ${i === currentIndex ? 'active' : ''}`;
                    dot.addEventListener('click', () => moveToIndex(i));
                    indicatorsContainer.appendChild(dot);
                }

                return { cardWidth, maxIndex };
            }

            function moveToIndex(index) {
                const { cardWidth, maxIndex } = updateCarouselMetrics();
                currentIndex = Math.max(0, Math.min(index, maxIndex));
                moveCarousel();
            }

            function moveCarousel() {
                const { cardWidth } = updateCarouselMetrics();
                const translateX = -currentIndex * (cardWidth + 30); // 30px is the gap between cards
                track.style.transform = `translateX(${translateX}px)`;

                // Update indicator dots
                document.querySelectorAll('.efzee-carousel-dot').forEach((dot, i) => {
                    dot.classList.toggle('active', i === currentIndex);
                });

                // Update button states
                const { maxIndex } = updateCarouselMetrics();
                prevButton.style.opacity = currentIndex === 0 ? '0.5' : '1';
                nextButton.style.opacity = currentIndex === maxIndex ? '0.5' : '1';
            }

            // Event Listeners
            prevButton.addEventListener('click', () => moveToIndex(currentIndex - 1));
            nextButton.addEventListener('click', () => moveToIndex(currentIndex + 1));

            // Touch events for mobile swipe
            track.addEventListener('touchstart', (e) => {
                startX = e.touches[0].pageX - track.offsetLeft;
                scrollLeft = track.scrollLeft;
                isDragging = true;
            });

            track.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                e.preventDefault();
                const x = e.touches[0].pageX - track.offsetLeft;
                const walk = (x - startX) * 2;
                const { cardWidth } = updateCarouselMetrics();
                if (Math.abs(walk) > cardWidth / 3) {
                    if (walk > 0 && currentIndex > 0) {
                        moveToIndex(currentIndex - 1);
                    } else if (walk < 0) {
                        moveToIndex(currentIndex + 1);
                    }
                    isDragging = false;
                }
            });

            track.addEventListener('touchend', () => {
                isDragging = false;
            });

            // Auto-scroll functionality
            let autoScrollInterval;
            const SCROLL_INTERVAL = 5000; // 5 seconds

            function startAutoScroll() {
                stopAutoScroll(); // Clear any existing interval
                autoScrollInterval = setInterval(() => {
                    const { maxIndex } = updateCarouselMetrics();
                    if (currentIndex >= maxIndex) {
                        moveToIndex(0);
                    } else {
                        moveToIndex(currentIndex + 1);
                    }
                }, SCROLL_INTERVAL);
            }

            function stopAutoScroll() {
                if (autoScrollInterval) {
                    clearInterval(autoScrollInterval);
                    autoScrollInterval = null;
                }
            }

            // Pause auto-scroll on hover
            container.addEventListener('mouseenter', stopAutoScroll);
            container.addEventListener('mouseleave', startAutoScroll);

            // Initial setup
            updateCarouselMetrics();
            moveCarousel();
            startAutoScroll();

            // Update on window resize
            window.addEventListener('resize', () => {
                updateCarouselMetrics();
                moveCarousel();
            });
        });
    </script>

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
                            <input type="radio" name="ratings" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
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