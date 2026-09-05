<?php
require_once basePath('config/database.php');
require_once basePath('app/Models/ServiceModel.php');

global $pdo;

$serviceModel = new ServiceModel($pdo);
$activeServices = $serviceModel->getActiveServices();

// Map active DB services into the $xrayRates array structure
$xrayRates = [];
foreach ($activeServices as $service) {
    $xrayRates[] = [
        'name'     => $service['exam_type'],
        'category' => $service['category'],
        'price'    => (float)$service['price']
    ];
}

// Group rates by category for the carousel list groups
$groupedRates = [];
foreach ($xrayRates as $rate) {
    $groupedRates[$rate['category']][] = $rate;
}
$xrayCategories = array_keys($groupedRates);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Citilife Diagnostic Center - Access your radiology examination status, receive updates, and view your available radiology reports through the Patient Portal.">
    <title><?= htmlspecialchars(getSystemName()) ?> — Radiology Patient Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/css/landing-page-styles.css">

</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="landing-nav" id="landing-nav">
        <div class="nav-inner">
            <a href="#home" class="nav-brand">
                <img src="<?= getSystemLogoUrl() ?>" alt="<?= htmlspecialchars(getSystemName()) ?> Logo"
                    onerror="this.style.display='none'">
                <div class="nav-brand-text">
                    <span class="nav-brand-name"><?= htmlspecialchars(getSystemName()) ?></span>
                    <span class="nav-brand-sub">Diagnostic Center</span>
                </div>
            </a>

            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#features">Services</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
            </ul>

            <a href="#" onclick="openLoginModal(event)" class="nav-cta-btn">
                Log in
            </a>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <section class="hero" id="home">
        <div class="hero-content">
            <img src="<?= getSystemLogoUrl() ?>" alt="<?= htmlspecialchars(getSystemName()) ?> Logo" class="hero-logo" onerror="this.style.display='none'">
            <h1>
                <?= htmlspecialchars(getSystemName()) ?><br>
                <span>Radiology Patient Portal</span>
            </h1>
            <p class="hero-desc">
                Check your examination status, receive updates, and securely access your available radiology reports through the <?= htmlspecialchars(getSystemName()) ?>
                Patient Portal.
            </p>
            <div class="hero-actions">
                <a href="#" onclick="openLoginModal(event)" class="btn-primary">
                    Access Patient Portal
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="#how-it-works" class="btn-secondary">How it works</a>
            </div>
        </div>
    </section>

    <!-- ===== ABOUT ===== -->
    <section class="about-section" id="about">
        <div class="about-inner">
            <div class="about-header">
                <div class="about-label">About The System</div>
                <p class="about-desc">
                    Citilife Radiology Patient Portal connects you to our expansive network of diagnostic centers,
                    providing secure, real-time access to your radiology examinations and reports anytime, anywhere.
                </p>
            </div>

            <div class="about-stats-grid">
                <div class="about-stat-card">
                    <div class="stat-value">24/7</div>
                    <div class="stat-title">Accessibility</div>
                    <div class="stat-desc">Access your examination records anytime, anywhere</div>
                </div>
                <div class="about-stat-card">
                    <div class="stat-value">100<span>%</span></div>
                    <div class="stat-title">Digital Portal</div>
                    <div class="stat-desc">View official X-ray reports securely online</div>
                </div>
                <div class="about-stat-card">
                    <div class="stat-value">Live</div>
                    <div class="stat-title">Queue Tracking</div>
                    <div class="stat-desc">Monitor examination and radiologist reading status</div>
                </div>
                <div class="about-stat-card">
                    <div class="stat-value">7</div>
                    <div class="stat-title">Interconnected Branches</div>
                    <div class="stat-desc">Request and take your examinations at your most convenient Citilife branch
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES ===== -->
    <section class="features-section" id="features">
        <div class="section-header">
            <h2>Everything you need in one place</h2>
            <p>Our patient portal is designed to provide you with secure, convenient, and instant access to your
                radiology information.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon fi-blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <h3>Track Examination Status</h3>
                <p>Patients can monitor the progress and status of their radiology examination.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-indigo">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3>View Radiology Reports</h3>
                <p>Patients can access their available radiology reports through the Patient Portal.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-sky">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <h3>Receive Notifications</h3>
                <p>Patients can receive updates regarding their examination and report status.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h3>Secure Patient Records</h3>
                <p>Patient information and radiology records are securely managed within the system.</p>
            </div>
        </div>
    </section>

    <!-- ===== HOW IT WORKS ===== -->
    <section class="hiw-section" id="how-it-works">
        <div class="hiw-inner">
            <div class="hiw-header">
                <h2>How It Works</h2>
                <p>Your journey from registration to receiving your results, simplified.</p>
            </div>
            <div class="hiw-grid">
                <div class="hiw-step">
                    <div class="hiw-num">1</div>
                    <h3>Register or Login</h3>
                    <p>Access the Citilife Patient Portal using your credentials.</p>
                </div>
                <div class="hiw-step">
                    <div class="hiw-num">2</div>
                    <h3>Undergo Examination</h3>
                    <p>Complete your radiology examination at the diagnostic center.</p>
                </div>
                <div class="hiw-step">
                    <div class="hiw-num">3</div>
                    <h3>Track Your Examination</h3>
                    <p>Monitor the status of your examination through the Patient Portal.</p>
                </div>
                <div class="hiw-step">
                    <div class="hiw-num">4</div>
                    <h3>Access Your Report</h3>
                    <p>View your available radiology report once it has been released.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SERVICES & PRICING ===== -->
    <section class="pricing-section" id="pricing">
        <div class="pricing-inner">
            <div class="section-header">
                <h2>Services & Rates</h2>
                <p>Transparent pricing for all our diagnostic procedures. No hidden fees.</p>
            </div>
            
            <div class="pricing-carousel-container">
                <div class="pricing-carousel-track" id="pricingTrack">
                    <?php foreach($groupedRates as $category => $rates): ?>
                        <div class="pricing-slide">
                            <div class="list-group">
                                <div class="list-group-header"><?= htmlspecialchars($category) ?> X-Rays</div>
                                <?php foreach($rates as $rate): ?>
                                    <div class="list-group-item">
                                        <span class="exam-type"><?= htmlspecialchars($rate['name']) ?></span>
                                        <strong class="exam-price">₱ <?= number_format($rate['price']) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="carousel-indicators" id="pricingIndicators">
                    <?php foreach($xrayCategories as $index => $cat): ?>
                        <button class="indicator-dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)" aria-label="Go to slide <?= $index + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>

                <button class="carousel-nav-btn prev" onclick="moveCarousel(-1)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <button class="carousel-nav-btn next" onclick="moveCarousel(1)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="cta-section">
        <div class="cta-card">
            <h2>Ready to access your radiology information?</h2>
            <p>Log in to the Citilife Patient Portal to view your examination status and available reports.</p>
            <a href="#" onclick="openLoginModal(event)" class="cta-btn">
                Access Patient Portal
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="landing-footer">
        <div class="footer-inner" style="justify-content: center;">
            <div class="footer-copy">&copy; <?= date('Y') ?> Citilife Diagnostic Center. All rights reserved.</div>
        </div>
    </footer>

    <script>
        // Navbar scroll shadow
        window.addEventListener('scroll', function () {
            var nav = document.getElementById('landing-nav');
            if (window.scrollY > 10) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Modal Logic
        function openLoginModal(e) {
            if (e) e.preventDefault();
            const modal = document.getElementById('loginModal');
            modal.style.display = 'flex';
            // Slight delay for animation
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeLoginModal() {
            const modal = document.getElementById('loginModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300); // match transition duration
            document.body.style.overflow = '';
        }

        function openSignupModal(e) {
            if (e) e.preventDefault();
            closeLoginModal(); // Close login modal if open
            const modal = document.getElementById('signupModal');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeSignupModal() {
            const modal = document.getElementById('signupModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            document.body.style.overflow = '';
        }

        // Function to toggle password visibility
        function toggleModalPassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const svgPath = btn.querySelector('svg path.eye-path');
            const svgPathStrikethrough = btn.querySelector('svg path.eye-slash-path');
            
            if (input.type === 'password') {
                input.type = 'text';
                // Eye slash icon (hide)
                svgPath.setAttribute('d', 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21');
                svgPathStrikethrough.setAttribute('d', '');
            } else {
                input.type = 'password';
                // Eye icon (show)
                svgPath.setAttribute('d', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z');
                svgPathStrikethrough.setAttribute('d', 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z');
            }
        }

        // Close on outside click for both modals
        window.onclick = function(event) {
            const loginModal = document.getElementById('loginModal');
            const signupModal = document.getElementById('signupModal');
            
            if (event.target == loginModal) {
                closeLoginModal();
            }
            if (event.target == signupModal) {
                closeSignupModal();
            }
        }

        // Listen for messages from the iframe (e.g., to open login modal)
        window.addEventListener('message', function(event) {
            if (event.data === 'openLoginModal') {
                closeSignupModal();
                openLoginModal();
            } else if (event.data && event.data.type === 'resizeIframe') {
                const iframe = document.getElementById('signupIframe');
                if (iframe) {
                    iframe.style.height = event.data.height + 'px';
                }
            }
        });

        // Automatically open modal if ?login=1 or ?signup=1 is in URL
        window.addEventListener('DOMContentLoaded', (event) => {
            let shouldCleanURL = false;
            if (window.location.search.includes('login=1')) {
                openLoginModal();
                shouldCleanURL = true;
            } else if (window.location.search.includes('signup=1')) {
                openSignupModal();
                shouldCleanURL = true;
            }
            if (shouldCleanURL) {
                // Clean the URL without reloading the page
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        // Carousel Logic
        let currentSlide = 0;

        function getCarouselElements() {
            return {
                track: document.getElementById('pricingTrack'),
                dots: document.querySelectorAll('.indicator-dot'),
                totalSlides: document.querySelectorAll('.indicator-dot').length
            };
        }

        function updateCarousel() {
            const { track, dots } = getCarouselElements();
            if (!track) return;
            // Move track
            track.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            // Update dots
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function moveCarousel(direction) {
            const { totalSlides } = getCarouselElements();
            if (totalSlides === 0) return;
            currentSlide += direction;
            if (currentSlide < 0) currentSlide = totalSlides - 1;
            if (currentSlide >= totalSlides) currentSlide = 0;
            updateCarousel();
        }

        function goToSlide(index) {
            currentSlide = index;
            updateCarousel();
        }

        // Swipe Support for Carousel (Mobile)
        let startX = 0;
        let isDragging = false;
        
        const carouselContainer = document.querySelector('.pricing-carousel-container');
        if (carouselContainer) {
            carouselContainer.addEventListener('touchstart', (e) => {
                // Ensure touch is within the track area if needed, but container is fine
                startX = e.touches[0].clientX;
                isDragging = true;
            }, {passive: true});

            carouselContainer.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
            }, {passive: true});

            carouselContainer.addEventListener('touchend', (e) => {
                if (!isDragging) return;
                isDragging = false;
                
                const endX = e.changedTouches[0].clientX;
                const diffX = startX - endX;

                // If swiped left (next)
                if (diffX > 50) {
                    moveCarousel(1);
                } 
                // If swiped right (prev)
                else if (diffX < -50) {
                    moveCarousel(-1);
                }
            });
        }

        // Real-time Pricing Polling
        let isPricingPolling = false;
        async function pollLandingPricing() {
            if (isPricingPolling) return;
            isPricingPolling = true;
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('ajax_polling', '1');
                const response = await fetch(url.toString());
                if (!response.ok) throw new Error('Network error');
                const html = await response.text();
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newTrack = doc.getElementById('pricingTrack');
                const currentTrack = document.getElementById('pricingTrack');
                
                if (newTrack && currentTrack && newTrack.innerHTML !== currentTrack.innerHTML) {
                    currentTrack.innerHTML = newTrack.innerHTML;
                    
                    const newInd = doc.getElementById('pricingIndicators');
                    const currentInd = document.getElementById('pricingIndicators');
                    if (newInd && currentInd && newInd.innerHTML !== currentInd.innerHTML) {
                        currentInd.innerHTML = newInd.innerHTML;
                        
                        const { totalSlides } = getCarouselElements();
                        if (currentSlide >= totalSlides) currentSlide = 0;
                    }
                    updateCarousel();
                }
            } catch (e) {
                console.error('Polling error:', e);
            } finally {
                isPricingPolling = false;
            }
        }
        setInterval(pollLandingPricing, 5000);
    </script>

    <!-- ===== LOGIN MODAL ===== -->
    <div id="loginModal" class="modal-overlay">
        <div class="modal-card">
            <button class="modal-close" onclick="closeLoginModal()">&times;</button>
            <div class="modal-header">
                <div class="modal-logo-wrapper">
                    <img src="<?= getSystemLogoUrl() ?>" alt="<?= htmlspecialchars(getSystemName()) ?> Logo" class="modal-logo" onerror="this.style.display='none'">
                </div>
                <h2>Patient Portal</h2>
                <p>Welcome! Access your X-ray records.</p>
            </div>
            
            <form action="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>patient-login" method="POST" class="modal-form">
                <?php if (!empty($_GET['redirect']) || !empty($_SESSION['redirect_url'])): ?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? $_SESSION['redirect_url']) ?>">
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; text-align: center; border: 1px solid #fecaca;">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['locked'])): ?>
                    <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; text-align: center; border: 1px solid #fecaca;">
                        <strong>Access Locked</strong><br>
                        <?= htmlspecialchars($_GET['locked']) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['warning'])): ?>
                    <div style="background: #fffbeb; color: #92400e; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; text-align: center; border: 1px solid #fde68a;">
                        <?= htmlspecialchars($_GET['warning']) ?>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <label for="modal-email">Email Address</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                        <input type="email" id="modal-email" name="email" required placeholder="Please enter your email">
                    </div>
                </div>

                <div class="input-group">
                    <label for="modal-password">Password</label>
                    <div class="input-wrapper" style="position: relative;">
                        <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <input type="password" id="modal-password" name="password" required placeholder="••••••••" style="padding-right: 40px;">
                        <button type="button" onclick="toggleModalPassword('modal-password', this)" tabindex="-1" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; cursor: pointer; color: #9ca3af;">
                            <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path class="eye-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path class="eye-slash-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="modal-forgot">
                    <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>forgot-password">Forgot your password?</a>
                </div>

                <button type="submit" class="modal-submit-btn">Log in</button>
                
                <div class="modal-signup">
                    Don't have an account? <a href="#" onclick="openSignupModal(event)">Sign up here</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== SIGNUP MODAL (IFRAME) ===== -->
    <div id="signupModal" class="modal-overlay">
        <div class="modal-card modal-card-large" style="max-width: 750px; max-height: 90vh; display: flex; flex-direction: column; padding: 0;">
            <button class="modal-close" onclick="closeSignupModal()" style="position: absolute; top: 16px; right: 16px; z-index: 100; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">&times;</button>
            <iframe id="signupIframe" src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>patient-signup?iframe=1" style="width: 100%; height: 500px; border: none; border-radius: 24px; transition: height 0.3s ease;"></iframe>
        </div>
    </div>
    <script>
        <?php if (isset($_GET['login']) || isset($_GET['error']) || isset($_GET['locked'])): ?>
            document.addEventListener("DOMContentLoaded", () => {
                // Ensure the function exists before calling
                if (typeof openLoginModal === 'function') {
                    openLoginModal(new Event('click'));
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>