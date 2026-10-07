<?php
// Only show if not accepted
if (empty($_SESSION['data_privacy_accepted'])):
    ?>
        <style>
            /* CitiLife Red Theme + NEUST Layout & Responsive Design */
            #dpm-overlay {
                position: fixed;
                inset: 0;
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem 1rem;
                background-color: rgba(15, 23, 42, 0.78);
                backdrop-filter: blur(8px);
                font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            #dpm-modal {
                background-color: #ffffff;
                border-radius: 18px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
                width: 100%;
                max-width: 860px;
                height: auto;
                max-height: 88vh;
                max-height: 88dvh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            /* 1. TOP RED HEADER */
            #dpm-header {
                background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
                color: #ffffff;
                padding: 1.25rem 2rem;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }

            #dpm-badge {
                display: inline-block;
                background: rgba(255, 255, 255, 0.18);
                border: 1px solid rgba(255, 255, 255, 0.35);
                color: #ffffff;
                padding: 3px 12px;
                border-radius: 50px;
                font-size: 0.68rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                margin-bottom: 0.35rem;
            }

            #dpm-title {
                font-size: 1.4rem;
                font-weight: 800;
                margin: 0;
                color: #ffffff;
                letter-spacing: -0.01em;
                line-height: 1.2;
            }

            #dpm-subtitle {
                font-size: 0.85rem;
                color: rgba(255, 255, 255, 0.9);
                margin: 0.3rem 0 0 0;
                line-height: 1.35;
            }

            /* 2. SUB-HEADER (CONSENT NOTICE BANNER) */
            #dpm-sub-header {
                background-color: #fffbeb;
                border-bottom: 1px solid #fef3c7;
                border-top: 1px solid rgba(0, 0, 0, 0.05);
                padding: 0.9rem 2rem;
                flex-shrink: 0;
                font-size: 0.84rem;
                color: #78350f;
                line-height: 1.55;
            }

            #dpm-sub-header strong {
                color: #92400e;
                font-weight: 700;
            }

            /* 3. SCROLLABLE BODY */
            #dpm-scroll-area {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                background-color: #f8fafc;
                padding: 1.5rem 2rem;
                scrollbar-width: thin;
                scrollbar-color: #cbd5e1 #f8fafc;
            }

            #dpm-scroll-area::-webkit-scrollbar {
                width: 6px;
            }

            #dpm-scroll-area::-webkit-scrollbar-track {
                background: #f8fafc;
            }

            #dpm-scroll-area::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            #dpm-scroll-area::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* HERO BANNER CARD (CITILIFE RED STYLE) */
            #dpm-hero-card {
                background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);
                border-radius: 18px;
                padding: 1.5rem 1.85rem;
                color: white;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1.5rem;
                margin-bottom: 1.25rem;
                box-shadow: 0 10px 25px -5px rgba(185, 28, 28, 0.35);
                position: relative;
                overflow: hidden;
            }

            #dpm-hero-card::after {
                content: '';
                position: absolute;
                top: -30%;
                right: 18%;
                width: 260px;
                height: 260px;
                background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(0, 0, 0, 0) 70%);
                border-radius: 50%;
                pointer-events: none;
            }

            #dpm-hero-left {
                display: flex;
                align-items: flex-start;
                gap: 1.25rem;
                flex-grow: 1;
                z-index: 1;
            }

            #dpm-hero-logo {
                background-color: #ffffff;
                padding: 0.35rem;
                border-radius: 12px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-top: 2px;
            }

            #dpm-hero-logo img {
                height: 52px;
                width: 52px;
                object-fit: contain;
                display: block;
            }

            #dpm-hero-content {
                flex-grow: 1;
                z-index: 1;
            }

            #dpm-hero-eyebrow {
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                background: rgba(255, 255, 255, 0.18);
                backdrop-filter: blur(4px);
                border: 1px solid rgba(255, 255, 255, 0.3);
                color: #ffffff;
                padding: 3px 12px;
                border-radius: 50px;
                display: inline-block;
                margin-bottom: 0.4rem;
            }

            #dpm-hero-title {
                font-size: 1.75rem;
                font-weight: 800;
                margin: 0 0 0.35rem 0;
                line-height: 1.2;
                color: #ffffff;
            }

            #dpm-hero-text {
                font-size: 0.88rem;
                color: #fef2f2;
                margin: 0;
                line-height: 1.5;
            }

            /* WHITE SEAL CARD ON THE RIGHT */
            #dpm-hero-seal-card {
                background: #ffffff;
                border-radius: 14px;
                padding: 8px 12px 10px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
                text-align: center;
                flex-shrink: 0;
                z-index: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                min-width: 105px;
            }

            .dpm-seal-card-header {
                font-size: 8px;
                font-weight: 800;
                color: #991b1b;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                line-height: 1.2;
                margin-bottom: 4px;
            }

            .dpm-seal-card-img {
                height: 90px;
                width: auto;
                object-fit: contain;
                display: block;
            }

            /* MAIN SECTION CONTAINER CARD */
            .dpm-main-card {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 1.5rem;
                margin-bottom: 1.15rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            }

            /* TITLE ROW WITH RED BAR ACCENT */
            .dpm-title-row {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 1.15rem;
            }

            .dpm-bar-accent {
                display: inline-block;
                width: 4px;
                height: 20px;
                background-color: #dc2626;
                border-radius: 2px;
                flex-shrink: 0;
            }

            .dpm-card-title {
                color: #0f172a;
                font-size: 1.15rem;
                font-weight: 700;
                margin: 0;
                line-height: 1.3;
            }

            /* INNER HIGHLIGHT BOX */
            .dpm-inner-box {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 1.25rem;
                font-size: 0.865rem;
                color: #334155;
                line-height: 1.65;
            }

            .dpm-inner-box p {
                margin: 0 0 0.85rem 0;
            }

            .dpm-inner-box p:last-child {
                margin-bottom: 0;
            }

            /* RED LEFT-BORDER CARDS */
            .dpm-accent-card {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-left: 4px solid #dc2626;
                border-radius: 8px;
                padding: 1rem 1.25rem;
                margin-bottom: 1rem;
                font-size: 0.865rem;
                line-height: 1.6;
                color: #334155;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            }

            .dpm-accent-card strong {
                color: #0f172a;
                font-weight: 700;
            }

            /* CONTACT BAR PILL */
            .dpm-contact-box {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.85rem 1.25rem;
                text-align: center;
                color: #475569;
                font-size: 0.85rem;
                margin-top: 1.25rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            }

            .dpm-contact-box a {
                color: #dc2626;
                font-weight: 700;
                text-decoration: none;
            }

            .dpm-contact-box a:hover {
                text-decoration: underline;
            }

            /* 4. FOOTER */
            #dpm-footer {
                background-color: #ffffff;
                border-top: 1px solid #e2e8f0;
                padding: 1rem 2rem;
                display: flex;
                justify-content: flex-end;
                align-items: center;
                gap: 1rem;
                flex-shrink: 0;
                position: sticky;
                bottom: 0;
                z-index: 20;
            }

            .dpm-btn-decline {
                background: transparent;
                color: #991b1b;
                font-weight: 700;
                font-size: 0.875rem;
                padding: 0.65rem 1.35rem;
                border-radius: 10px;
                text-decoration: none;
                transition: background 0.2s, color 0.2s;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .dpm-btn-decline:hover {
                background-color: #fee2e2;
                color: #7f1d1d;
            }

            .dpm-btn-accept {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: #ffffff;
                font-weight: 700;
                font-size: 0.875rem;
                padding: 0.65rem 1.75rem;
                border-radius: 25px;
                border: none;
                cursor: pointer;
                transition: all 0.2s;
                box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }

            .dpm-btn-accept:hover {
                background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
                box-shadow: 0 6px 14px rgba(220, 38, 38, 0.4);
            }

            .dpm-btn-accept:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }

            @media (max-width: 768px) {
                #dpm-modal {
                    max-width: 95%;
                }
                #dpm-header, #dpm-sub-header, #dpm-footer {
                    padding-left: 1.25rem;
                    padding-right: 1.25rem;
                }
                #dpm-scroll-area {
                    padding: 1.25rem;
                }
            }

            /* SMARTPHONE / MOBILE ADAPTIVE LAYOUT (MATCHING NEUST MOBILE) */
            @media (max-width: 640px) {
                #dpm-overlay {
                    padding: 0.75rem 0.65rem;
                    align-items: center;
                    justify-content: center;
                }

                #dpm-modal {
                    border-radius: 18px;
                    max-height: 92vh;
                    max-height: 92dvh;
                    height: auto;
                    width: 100%;
                    max-width: 100%;
                    margin: auto;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35);
                }

                #dpm-header {
                    padding: 1.15rem 1.25rem 1rem;
                    text-align: left;
                }

                #dpm-badge {
                    font-size: 0.62rem;
                    padding: 2px 9px;
                    margin-bottom: 0.25rem;
                }

                #dpm-title {
                    font-size: 1.25rem;
                    line-height: 1.25;
                }

                #dpm-subtitle {
                    font-size: 0.78rem;
                    margin-top: 0.25rem;
                    line-height: 1.4;
                }

                #dpm-sub-header {
                    padding: 0.85rem 1.15rem;
                    font-size: 0.78rem;
                    line-height: 1.5;
                }

                #dpm-scroll-area {
                    padding: 1rem 1.15rem;
                }

                /* Mobile Hero Card: Stacked Center Layout */
                #dpm-hero-card {
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    padding: 1.5rem 1.15rem;
                    border-radius: 16px;
                    gap: 1.15rem;
                    margin-bottom: 1rem;
                }

                #dpm-hero-left {
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    gap: 0.85rem;
                    width: 100%;
                }

                #dpm-hero-logo {
                    padding: 0.4rem;
                    border-radius: 14px;
                    margin: 0 auto;
                }

                #dpm-hero-logo img {
                    height: 52px;
                    width: 52px;
                }

                #dpm-hero-content {
                    text-align: center;
                    width: 100%;
                }

                #dpm-hero-eyebrow {
                    font-size: 0.65rem;
                    padding: 4px 12px;
                    margin-bottom: 0.5rem;
                }

                #dpm-hero-title {
                    font-size: 1.45rem;
                    margin-bottom: 0.4rem;
                }

                #dpm-hero-text {
                    font-size: 0.82rem;
                    line-height: 1.55;
                    max-width: 100%;
                }

                #dpm-hero-seal-card {
                    padding: 10px 16px;
                    min-width: 130px;
                    width: fit-content;
                    margin: 0 auto;
                    border-radius: 14px;
                    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
                }

                .dpm-seal-card-header {
                    font-size: 8px;
                    font-weight: 800;
                    margin-bottom: 6px;
                }

                .dpm-seal-card-img {
                    height: 105px;
                }

                .dpm-main-card {
                    padding: 1.15rem 1rem;
                    margin-bottom: 0.85rem;
                    border-radius: 12px;
                }

                .dpm-title-row {
                    gap: 8px;
                    margin-bottom: 0.85rem;
                }

                .dpm-card-title {
                    font-size: 1.05rem;
                }

                .dpm-inner-box {
                    padding: 0.95rem 1rem;
                    font-size: 0.82rem;
                    line-height: 1.6;
                    border-radius: 8px;
                }

                .dpm-accent-card {
                    padding: 0.9rem 1rem;
                    font-size: 0.82rem;
                    line-height: 1.6;
                    border-radius: 8px;
                    margin-bottom: 0.85rem;
                }

                .dpm-contact-box {
                    padding: 0.85rem 1rem;
                    font-size: 0.8rem;
                    border-radius: 10px;
                    margin-top: 1rem;
                }

                /* Mobile Footer: Full-Width Stacked Buttons (Accept on Top, Decline Below) */
                #dpm-footer {
                    flex-direction: column;
                    padding: 1rem 1.15rem;
                    padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
                    gap: 0.65rem;
                    background-color: #ffffff;
                }

                .dpm-btn-accept {
                    width: 100%;
                    padding: 0.8rem 1rem;
                    font-size: 0.88rem;
                    border-radius: 12px;
                    text-align: center;
                    order: 1;
                }

                .dpm-btn-decline {
                    width: 100%;
                    padding: 0.75rem 1rem;
                    font-size: 0.85rem;
                    border-radius: 12px;
                    border: 1px solid #fecaca;
                    background-color: #ffffff;
                    color: #991b1b;
                    text-align: center;
                    order: 2;
                }
            }
        </style>

        <div id="dpm-overlay">
            <div id="dpm-modal">

                <!-- 1. MODAL TOP RED HEADER -->
                <div id="dpm-header">
                    <div>
                        <div><span id="dpm-badge">Required Notice</span></div>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patient'): ?>
                                <h2 id="dpm-title">Data Privacy Notice</h2>
                                <p id="dpm-subtitle">Please review the CitiLife Data Privacy Notice. You must accept it to continue using the Patient Portal.</p>
                        <?php else: ?>
                                <h2 id="dpm-title">Staff Confidentiality Agreement</h2>
                                <p id="dpm-subtitle">Please review the CitiLife Confidentiality Agreement. You must accept it to continue using the Portal.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. SUB-HEADER (CONSENT NOTICE BANNER) -->
                <div id="dpm-sub-header">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patient'): ?>
                            <strong>Consent Notice:</strong> By clicking 'I Accept and Continue', you authorize CitiLife Diagnostic Center to collect, store, and process your personal and medical information exclusively for diagnostic imaging, examination tracking, official report delivery, and legitimate healthcare operations in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173).
                    <?php else: ?>
                            <strong>Agreement Notice:</strong> By clicking 'I Accept and Continue', you pledge to maintain strict confidentiality regarding all patient medical records and sensitive healthcare information you handle, in full compliance with the Data Privacy Act of 2012 (Republic Act No. 10173) and CitiLife clinical security policies.
                    <?php endif; ?>
                </div>

                <!-- 3. SCROLLABLE BODY -->
                <div id="dpm-scroll-area">

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'patient'): ?>
                            <!-- Hero Banner Card (CitiLife Red Theme + Logo + NPC Seal) -->
                            <div id="dpm-hero-card">
                                <div id="dpm-hero-left">
                                    <div id="dpm-hero-logo" title="<?= htmlspecialchars(getSystemName()) ?>">
                                        <img src="<?= getSystemLogoUrl() ?>" alt="<?= htmlspecialchars(getSystemName()) ?> Logo"
                                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGMyNjI2IiBzdHJva2Utd2lkdGg9IjIiPjxyZWN0IHdpZHRoPSIxOCIgaGVpZ2h0PSIxOCIgeD0iMyIgeT0iMyIgcng9IjIiLz48cGF0aCBkPSJNOSA4aDZhMiAyIDAgMCAxIDIgMnY0YTIgMiAwIDAgMS0yIDJIOXoiLz48L3N2Zz4='">
                                    </div>
                                    <div id="dpm-hero-content">
                                        <span id="dpm-hero-eyebrow"><?= htmlspecialchars(getSystemName()) ?></span>
                                        <h3 id="dpm-hero-title">Data Privacy Notice</h3>
                                        <p id="dpm-hero-text">
                                            <?= htmlspecialchars(getSystemName()) ?> is committed to protecting the privacy, confidentiality, and security of patient personal and medical information collected across all diagnostic services and online portals.
                                        </p>
                                    </div>
                                </div>
                                <div id="dpm-hero-seal-card">
                                    <div class="dpm-seal-card-header">DPO/DPS REGISTERED</div>
                                    <img src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/images/npc-seal.png" alt="NPC DPO/DPS Registered Seal" class="dpm-seal-card-img">
                                </div>
                            </div>

                            <!-- Main Section Card: How CitiLife Protects Health Data -->
                            <div class="dpm-main-card">
                                <div class="dpm-title-row">
                                    <span class="dpm-bar-accent"></span>
                                    <h3 class="dpm-card-title">How CitiLife Protects Your Health Data</h3>
                                </div>
                                <div class="dpm-inner-box">
                                    <p>To provide high-quality medical diagnostic imaging and laboratory services, <?= htmlspecialchars(getSystemName()) ?> collects necessary personal identification, contact details, and pertinent medical history from patients.</p>
                                    <p>We uphold the strict mandates of the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>, ensuring that all health records, imaging results, and personal data are treated with utmost confidentiality and protected against unauthorized access, disclosure, or misuse.</p>
                                    <p>Access to your diagnostic examinations and radiographic reports is restricted exclusively to authorized healthcare professionals, licensed radiologic technologists, interpreting radiologists, and designated clinical personnel directly involved in your care.</p>
                                    <p>All data exchanged through our web application is transmitted using industry-standard encrypted channels and stored in secure healthcare databases compliant with regulatory health guidelines and data privacy standards.</p>
                                </div>
                            </div>

                            <!-- Data Usage Accent Card -->
                            <div class="dpm-accent-card">
                                <strong>Data Processing Purpose:</strong> Your information is processed exclusively for scheduling, conducting diagnostic procedures, quality assurance, releasing official medical reports, and maintaining secure patient records within <?= htmlspecialchars(getSystemName()) ?>.
                            </div>

                            <!-- Patient Rights Accent Card -->
                            <div class="dpm-accent-card">
                                <strong>Your Patient Privacy Rights:</strong> Under RA 10173, you have the right to request a copy of your medical examination records, seek corrections for inaccurate details, and inquire about how your health data is handled and protected.
                            </div>

                    <?php else: ?>
                            <!-- Hero Banner Card (Staff) -->
                            <div id="dpm-hero-card">
                                <div id="dpm-hero-left">
                                    <div id="dpm-hero-logo" title="<?= htmlspecialchars(getSystemName()) ?>">
                                        <img src="<?= getSystemLogoUrl() ?>" alt="<?= htmlspecialchars(getSystemName()) ?> Logo"
                                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjZGMyNjI2IiBzdHJva2Utd2lkdGg9IjIiPjxyZWN0IHdpZHRoPSIxOCIgaGVpZ2h0PSIxOCIgeD0iMyIgeT0iMyIgcng9IjIiLz48cGF0aCBkPSJNOSA4aDZhMiAyIDAgMCAxIDIgMnY0YTIgMiAwIDAgMS0yIDJIOXoiLz48L3N2Zz4='">
                                    </div>
                                    <div id="dpm-hero-content">
                                        <span id="dpm-hero-eyebrow"><?= htmlspecialchars(getSystemName()) ?></span>
                                        <h3 id="dpm-hero-title">Staff Confidentiality Agreement</h3>
                                        <p id="dpm-hero-text">
                                            As authorized clinical personnel of <?= htmlspecialchars(getSystemName()) ?>, you are bound by duty to maintain the strict confidentiality and security of all patient medical records.
                                        </p>
                                    </div>
                                </div>
                                <div id="dpm-hero-seal-card">
                                    <div class="dpm-seal-card-header">DPO/DPS REGISTERED</div>
                                    <img src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/images/npc-seal.png" alt="NPC DPO/DPS Registered Seal" class="dpm-seal-card-img">
                                </div>
                            </div>

                            <!-- Staff Section Card -->
                            <div class="dpm-main-card">
                                <div class="dpm-title-row">
                                    <span class="dpm-bar-accent"></span>
                                    <h3 class="dpm-card-title">Staff Confidentiality & Health Information Security</h3>
                                </div>
                                <div class="dpm-inner-box">
                                    <p>Authorized staff members and healthcare professionals are entrusted with confidential patient health information and diagnostic test results.</p>
                                    <p>All handling, processing, and recording of patient data must strictly conform with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> and <?= htmlspecialchars(getSystemName()) ?> clinical security policies.</p>
                                    <p>Accessing, copying, capturing, transmitting, or discussing patient information outside legitimate clinical workflows is strictly forbidden and constitutes a serious violation subject to disciplinary and legal penalties.</p>
                                    <p>All user activities within this system are monitored and securely logged to protect patient data integrity and guarantee compliance with statutory health regulations.</p>
                                </div>
                            </div>

                            <!-- Staff Agreement Notice Accent Card -->
                            <div class="dpm-accent-card">
                                <strong>Staff Confidentiality Obligation:</strong> You agree to protect patient records against unauthorized access, safeguard your account credentials, and immediately notify management of any suspected data security breach.
                            </div>

                            <!-- Staff Security Protocols Accent Card -->
                            <div class="dpm-accent-card">
                                <strong>Workstation Protocols:</strong> Always lock or log out of your workstation when away, never share system login credentials, and handle all physical and digital medical documents with strict discretion.
                            </div>
                    <?php endif; ?>

                    <!-- Contact Bar Card -->
                    <div class="dpm-contact-box">
                        For data privacy inquiries or record requests, you may contact our Data Protection Officer at <a href="mailto:citilifediagnosticcenter26@gmail.com">citilifediagnosticcenter26@gmail.com</a>.
                    </div>

                </div>

                <!-- 4. FOOTER ACTIONS -->
                <div id="dpm-footer">
                    <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>logout" class="dpm-btn-decline">
                        Decline and Logout
                    </a>
                    <button id="acceptPrivacyBtn" class="dpm-btn-accept">
                        I Accept and Continue
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

                        fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>accept-privacy', {
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
                                    acceptBtn.innerHTML = 'I Accept and Continue';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred. Please try again.');
                                acceptBtn.disabled = false;
                                acceptBtn.innerHTML = 'I Accept and Continue';
                            });
                    });
                }
            });
        </script>
<?php endif; ?>