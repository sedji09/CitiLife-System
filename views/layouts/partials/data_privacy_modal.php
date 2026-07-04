<?php
// Only show if not accepted
if (empty($_SESSION['data_privacy_accepted'])):
    ?>
    <style>
        /* Premium CitiLife Theme Styles */
        #dpm-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-color: rgba(15, 23, 42, 0.75);
            /* Darker, slate-tinted overlay */
            backdrop-filter: blur(8px);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        #dpm-modal {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            width: 100%;
            max-width: 850px;
            height: 100%;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* 1. HEADER (Gradient Theme) */
        #dpm-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #ffffff;
            padding: 1.5rem 2rem 1rem 2rem;
            flex-shrink: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        #dpm-header-icon {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 0.75rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #dpm-header-text {
            flex-grow: 1;
        }

        #dpm-title {
            font-size: 1.4rem;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        #dpm-subtitle {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.85);
            margin: 0.25rem 0 0 0;
        }

        #dpm-badge {
            background-color: #ffffff;
            color: #dc2626;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* 2. SUB-HEADER (Consent Notice) */
        #dpm-sub-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            flex-shrink: 0;
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.6;
        }

        #dpm-sub-header strong {
            color: #dc2626;
            font-weight: 700;
        }

        /* 3. SCROLLABLE BODY */
        #dpm-scroll-area {
            flex-grow: 1;
            overflow-y: auto;
            background-color: #f1f5f9;
            padding: 2rem;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        #dpm-scroll-area::-webkit-scrollbar {
            width: 6px;
        }

        #dpm-scroll-area::-webkit-scrollbar-track {
            background: transparent;
        }

        #dpm-scroll-area::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        #dpm-scroll-area::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* HERO CARD (Dark Sleek Medical Vibe) */
        #dpm-hero-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 2rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.3);
            position: relative;
            overflow: hidden;
        }

        /* Decorative background accent */
        #dpm-hero-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(220, 38, 38, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            border-radius: 50%;
        }

        #dpm-hero-logo {
            background-color: #ffffff;
            padding: 0.5rem;
            border-radius: 50%;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
            z-index: 1;
        }

        #dpm-hero-logo img {
            height: 60px;
            width: 60px;
            object-fit: contain;
        }

        #dpm-hero-content {
            z-index: 1;
        }

        #dpm-hero-eyebrow {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #fca5a5;
            /* Light red */
            display: block;
            margin-bottom: 0.5rem;
        }

        #dpm-hero-title {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            line-height: 1.2;
        }

        #dpm-hero-text {
            font-size: 0.9rem;
            color: #cbd5e1;
            margin: 0;
            line-height: 1.6;
        }

        /* SECTION CARDS */
        .dpm-section-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .dpm-section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .dpm-section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .dpm-section-icon {
            color: #dc2626;
            background-color: #fef2f2;
            padding: 0.4rem;
            border-radius: 8px;
        }

        .dpm-section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .dpm-section-content {
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.6;
        }

        .dpm-section-content p {
            margin: 0 0 0.75rem 0;
        }

        .dpm-section-content p:last-child {
            margin-bottom: 0;
        }

        .dpm-email-link {
            color: #0056b3;
            font-weight: bold;
            text-decoration: none;
        }

        .dpm-email-link:hover {
            text-decoration: underline;
        }

        .dpm-mobile-break {
            display: none;
        }

        /* 4. FOOTER */
        #dpm-footer {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }

        .dpm-btn {
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
        }

        .dpm-btn-decline {
            background-color: #f1f5f9;
            color: #475569;
        }

        .dpm-btn-decline:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        .dpm-btn-accept {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        }

        .dpm-btn-accept:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 6px 8px -1px rgba(220, 38, 38, 0.4);
        }

        .dpm-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            #dpm-modal {
                max-width: 95%;
            }
            #dpm-header, #dpm-sub-header, #dpm-footer {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }
            #dpm-scroll-area {
                padding: 1.5rem;
            }
        }

        @media (max-width: 640px) {
            #dpm-modal {
                border-radius: 16px 16px 0 0;
                max-height: 98vh;
                margin-top: auto;
            }

            #dpm-overlay {
                padding: 1rem 0 0 0;
                align-items: flex-end;
            }

            #dpm-header {
                padding: 1rem;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            #dpm-header-icon {
                padding: 0.5rem;
            }

            #dpm-title {
                font-size: 1.15rem;
            }

            #dpm-subtitle {
                font-size: 0.75rem;
            }

            #dpm-badge {
                font-size: 0.6rem;
                padding: 0.2rem 0.5rem;
                margin-left: auto;
            }

            #dpm-sub-header {
                padding: 1rem;
                font-size: 0.75rem;
            }

            #dpm-scroll-area {
                padding: 1rem;
            }

            #dpm-hero-card {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem 1rem;
                gap: 1rem;
            }

            #dpm-hero-title {
                font-size: 1.4rem;
            }

            #dpm-hero-text {
                font-size: 0.85rem;
            }

            .dpm-section-card {
                padding: 1rem;
            }

            .dpm-section-title {
                font-size: 0.95rem;
            }

            .dpm-section-content {
                font-size: 0.8rem;
            }

            #dpm-footer {
                flex-direction: column-reverse;
                padding: 1rem;
                gap: 0.5rem;
            }

            .dpm-btn {
                width: 100%;
                padding: 0.85rem;
                font-size: 0.9rem;
            }

            .dpm-mobile-break {
                display: block;
            }
        }
    </style>

    <div id="dpm-overlay">
        <div id="dpm-modal">

            <!-- 1. HEADER -->
            <div id="dpm-header">
                <div id="dpm-header-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                </div>
                <div id="dpm-header-text">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patient'): ?>
                        <h2 id="dpm-title">Data Privacy Notice</h2>
                        <p id="dpm-subtitle">Your privacy is our top priority at CitiLife.</p>
                    <?php else: ?>
                        <h2 id="dpm-title">Confidentiality Agreement</h2>
                        <p id="dpm-subtitle">Protecting patient data is our top priority.</p>
                    <?php endif; ?>
                </div>
                <span id="dpm-badge">Required Notice</span>
            </div>

            <!-- 2. SUB-HEADER -->
            <div id="dpm-sub-header"
                style="text-align: justify; background-color: #fbe3e3ff; border-bottom: 1px solid #ff0000ff; border-top: 1px solid #ff0000ff">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patient'): ?>
                    <strong>Consent Notice:</strong> By clicking 'I Accept & Continue', you authorize CitiLife to securely collect and use your personal data. Your information is strictly used for medical records, diagnostic testing, and necessary healthcare operations, in full compliance with the Data Privacy Act of 2012.
                <?php else: ?>
                    <strong>Agreement Notice:</strong> By clicking 'I Accept & Continue', you agree to maintain the absolute confidentiality of all patient records and sensitive information you access during your duties, in strict compliance with the Data Privacy Act of 2012.
                <?php endif; ?>
            </div>

            <!-- 3. SCROLLABLE BODY -->
            <div id="dpm-scroll-area">

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patient'): ?>
                    <!-- Hero Card (Patient) -->
                    <div id="dpm-hero-card">
                        <div id="dpm-hero-logo">
                            <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/citilife-logo.png" alt="CitiLife Logo"
                                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utd2lkdGg9IjIiPjxyZWN0IHdpZHRoPSIxOCIgaGVpZ2h0PSIxOCIgeD0iMyIgeT0iMyIgcng9IjIiLz48cGF0aCBkPSJNOSA4aDZhMiAyIDAgMCAxIDIgMnY0YTIgMiAwIDAgMS0yIDJIOXoiLz48L3N2Zz4='">
                        </div>
                        <div id="dpm-hero-content">
                            <span id="dpm-hero-eyebrow">CitiLife Diagnostic Center</span>
                            <h3 id="dpm-hero-title">Protecting Your Data</h3>
                            <p id="dpm-hero-text">
                                At CitiLife Diagnostics, we hold the privacy and security of your personal information in the highest regard, ensuring that all data collected across our platforms remains strictly confidential.
                            </p>
                        </div>
                    </div>

                    <!-- Section 1 (Patient) -->
                    <div class="dpm-section-card">
                        <div class="dpm-section-header">
                            <div class="dpm-section-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                            <h4 class="dpm-section-title">How CitiLife collects and protects data</h4>
                        </div>
                        <div class="dpm-section-content">
                            <p>To deliver the highest quality of medical diagnostic services, CitiLife requires the collection of specific personal and medical information from our patients.</p>
                            <p>While handling your data, we guarantee the strict protection of your privacy and the absolute confidentiality of your records, adhering firmly to the mandates of the <strong>Data
                                    Privacy Act of 2012 (Republic Act No. 10173)</strong>.</p>
                            <p>Your sensitive information is exclusively accessible to authorized healthcare professionals and personnel. Any transmission of data occurs through highly encrypted web channels, and your records are safely archived in secure databases that meet all strict regulatory standards and government guidelines.
                            </p>
                        </div>
                    </div>

                    <!-- Section 2 (Patient) -->
                    <div class="dpm-section-card">
                        <div class="dpm-section-header">
                            <div class="dpm-section-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                            </div>
                            <h4 class="dpm-section-title">Your Rights as a Patient</h4>
                        </div>
                        <div class="dpm-section-content">
                            <p>As a valued patient, you are entitled to request a copy of any personal or medical records we maintain about you. Furthermore, you have the right to request the correction of any inaccurate information, or demand the deletion of your data given valid and lawful reasons.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Hero Card (Staff) -->
                    <div id="dpm-hero-card">
                        <div id="dpm-hero-logo">
                            <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/citilife-logo.png" alt="CitiLife Logo"
                                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utd2lkdGg9IjIiPjxyZWN0IHdpZHRoPSIxOCIgaGVpZ2h0PSIxOCIgeD0iMyIgeT0iMyIgcng9IjIiLz48cGF0aCBkPSJNOSA4aDZhMiAyIDAgMCAxIDIgMnY0YTIgMiAwIDAgMS0yIDJIOXoiLz48L3N2Zz4='">
                        </div>
                        <div id="dpm-hero-content">
                            <span id="dpm-hero-eyebrow">CitiLife Diagnostic Center</span>
                            <h3 id="dpm-hero-title">Staff Confidentiality Agreement</h3>
                            <p id="dpm-hero-text">
                                As an authorized personnel of CitiLife Diagnostics, you are entrusted with sensitive patient data and medical records. Strict confidentiality is expected at all times.
                            </p>
                        </div>
                    </div>

                    <!-- Section 1 (Staff) -->
                    <div class="dpm-section-card">
                        <div class="dpm-section-header">
                            <div class="dpm-section-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                            </div>
                            <h4 class="dpm-section-title">Your Responsibilities</h4>
                        </div>
                        <div class="dpm-section-content">
                            <p>You are required to maintain the absolute confidentiality of all patient records, test results, and personal information you access during your duties.</p>
                            <p>Handling of patient data must adhere strictly to the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>.</p>
                            <p>Unauthorized sharing, viewing, transmission, or mishandling of patient information is strictly prohibited and may result in immediate disciplinary action or legal consequences.</p>
                        </div>
                    </div>

                    <!-- Section 2 (Staff) -->
                    <div class="dpm-section-card">
                        <div class="dpm-section-header">
                            <div class="dpm-section-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6z"></path>
                                    <path d="M14 3v5h5M16 13H8M16 17H8M10 9H8"></path>
                                </svg>
                            </div>
                            <h4 class="dpm-section-title">Data Security Protocol</h4>
                        </div>
                        <div class="dpm-section-content">
                            <p>Ensure that you log out or lock your terminal when away from your workstation. Do not share your login credentials with anyone, and report any suspicious activities or data breaches to the IT Administrator immediately.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Contact Section -->
                <div style="text-align: center; margin-top: 2rem; padding: 0 0.5rem;">
                    <div
                        style="font-size: 0.85rem; color: #64748b; background: #e2e8f0; padding: 0.75rem 1rem; border-radius: 20px; display: inline-block; max-width: 100%; word-break: break-word; line-height: 1.5; box-sizing: border-box;">
                        For data privacy concerns, contact <br class="dpm-mobile-break"/><a href="mailto:citilifediagnosticcenter26@gmail.com"
                            class="dpm-email-link">citilifediagnosticcenter26@gmail.com</a>
                    </div>
                </div>

            </div>

            <!-- 4. FOOTER ACTIONS -->
            <div id="dpm-footer">
                <a href="/<?= PROJECT_DIR ?>/logout" class="dpm-btn dpm-btn-decline">
                    Decline & Logout
                </a>
                <button id="acceptPrivacyBtn" class="dpm-btn dpm-btn-accept">
                    I Accept & Continue
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('dpm-overlay');
            const acceptBtn = document.getElementById('acceptPrivacyBtn');

            if (overlay && acceptBtn) {
                // Prevent body scroll
                document.body.style.overflow = 'hidden';

                acceptBtn.addEventListener('click', function () {
                    acceptBtn.disabled = true;
                    acceptBtn.innerHTML = 'Processing...';

                    fetch('/<?= PROJECT_DIR ?>/accept-privacy', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                overlay.style.opacity = '0';
                                overlay.style.transition = 'opacity 0.3s ease-out';
                                setTimeout(() => {
                                    overlay.remove();
                                    document.body.style.overflow = '';
                                    window.location.reload(); // Reload to ensure full dashboard access
                                }, 300);
                            } else {
                                alert('An error occurred. Please try again.');
                                acceptBtn.disabled = false;
                                acceptBtn.innerHTML = 'I Accept & Continue';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                            acceptBtn.disabled = false;
                            acceptBtn.innerHTML = 'I Accept & Continue';
                        });
                });
            }
        });
    </script>
<?php endif; ?>