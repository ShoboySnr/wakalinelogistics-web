<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Waka Line Logistics - Starting from ₦1,500 Delivery Across Lagos | Same Day Delivery</title>
    <meta name="description" content="Professional delivery service across Lagos. Starting from ₦1,500 for all deliveries. Same-day delivery guaranteed for order before 12pm. Order now via WhatsApp!">
    <meta name="author" content="Waka Line Logistics">
    
    <meta property="og:title" content="Waka Line Logistics - Starting from ₦1,500 Delivery Across Lagos">
    <meta property="og:description" content="Same-day bike delivery across Lagos. Starting from ₦1,500. Skip the traffic, save time, and never worry about late deliveries again.">
    <meta property="og:type" content="website">
    
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-P45WNKMZ20"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-P45WNKMZ20');
    </script>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="{{ route('home') }}" class="nav-logo">
                <img src="{{ asset('assets/img/mywakawaka-logo-white.png') }}" alt="Waka Line Logistics">
            </a>
            <a href="{{ route('metter.index') }}" class="nav-cta">Meet Metter 1.0</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-bg">
            <img src="{{ asset('assets/img/hero-delivery.jpg') }}" alt="Waka Line Logistics delivery rider in Lagos">
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-inner">
            <span class="hero-label">RAMADAN PROMO - Limited Time Offer</span>
            <h1>Starting from ₦1,500 Delivery Anywhere in Lagos</h1>
            <p class="hero-sub">We deliver your packages anywhere in Lagos, same day.</p>
            <div class="hero-actions">
                <a href="https://wa.me/2348100665758?text=Hi,%20I%20want%20to%20make%20a%20delivery%20order" class="btn btn-primary">Order via WhatsApp</a>
                <a href="tel:+2348100665758" class="btn btn-outline">Call Us</a>
            </div>
            <p class="hero-form-link">Prefer a form instead? <a href="#" id="openOrderForm">Place your order here</a></p>
            <p class="hero-note">Order before 12 pm for same-day delivery</p>
        </div>
    </section>

    <!-- Order Form Modal -->
    <div class="modal-overlay" id="orderModal">
        <div class="modal">
            <button class="modal-close" id="closeModal">&times;</button>

            <!-- Fixed Header -->
            <div class="modal-header">
                <h2 class="modal-title">
                    <span>Place a Delivery Order</span>
                </h2>
                <div class="form-progress">
                    <div class="progress-step active" data-step="1">
                        <span>1</span>
                        <span>Pickup</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="2">
                        <span>2</span>
                        <span>Dropoff</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="3">
                        <span>3</span>
                        <span>Package</span>
                    </div>
                </div>
            </div>

            <form id="deliveryOrderForm">
                @csrf
                <!-- Scrollable Body -->
                <div class="modal-body">
                    <!-- Step 1: Pickup -->
                    <div class="form-step active" data-step="1">
                        <h3>Pickup Information</h3>
                        <div class="form-group">
                            <label for="senderName">Your Name</label>
                            <input type="text" id="senderName" name="senderName" required placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label for="senderPhone">Your Phone Number</label>
                            <input type="tel" id="senderPhone" name="senderPhone" required placeholder="e.g. 0810 000 0000">
                        </div>
                        <div class="form-group">
                            <label for="senderEmail">Your Email</label>
                            <input type="email" id="senderEmail" name="senderEmail" required placeholder="email@example.com">
                        </div>
                        <div class="form-group">
                            <label for="pickupAddress">Pickup Address</label>
                            <textarea id="pickupAddress" name="pickupAddress" required placeholder="Full address where we pick up the package" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="pickupArea">Area / Landmark</label>
                            <input type="text" id="pickupArea" name="pickupArea" placeholder="e.g. Ikeja, near City Mall">
                        </div>
                    </div>

                    <!-- Step 2: Delivery -->
                    <div class="form-step" data-step="2">
                        <h3>Delivery Information</h3>
                        <div class="form-group">
                            <label for="recipientName">Recipient's Name</label>
                            <input type="text" id="recipientName" name="recipientName" required placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label for="recipientPhone">Recipient's Phone Number</label>
                            <input type="tel" id="recipientPhone" name="recipientPhone" required placeholder="e.g. 0810 000 0000">
                        </div>
                        <div class="form-group">
                            <label for="deliveryAddress">Delivery Address</label>
                            <textarea id="deliveryAddress" name="deliveryAddress" required placeholder="Full address where the package should be delivered" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="deliveryArea">Area / Landmark</label>
                            <input type="text" id="deliveryArea" name="deliveryArea" placeholder="e.g. Lekki Phase 1, near Shoprite">
                        </div>
                        <div class="form-group">
                            <label for="deliveryNotes">Any Other Information (optional)</label>
                            <textarea id="deliveryNotes" name="deliveryNotes" placeholder="e.g. Gate code, call before delivery, fragile item, etc." rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Step 3: Package -->
                    <div class="form-step" data-step="3">
                        <h3>Package Details & Delivery Fee</h3>
                        
                        <!-- Delivery Fee Display -->
                        <div id="deliveryFeeSection" style="background: linear-gradient(135deg, #C1666B 0%, #a85559 100%); padding: 20px; border-radius: 12px; margin-bottom: 24px; display: none;">
                            <div style="text-align: center; color: white;">
                                <p style="font-size: 14px; margin: 0 0 8px 0; opacity: 0.9;">Your Delivery Fee</p>
                                <p style="font-size: 36px; font-weight: bold; margin: 0;" id="calculatedFee">₦0</p>
                                <p style="font-size: 12px; margin: 8px 0 0 0; opacity: 0.8;" id="deliveryDistance"></p>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div id="feeLoadingSection" style="background: #f3f4f6; padding: 20px; border-radius: 12px; margin-bottom: 24px; text-align: center; display: none;">
                            <div style="display: inline-block; width: 24px; height: 24px; border: 3px solid #C1666B; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            <p style="margin: 12px 0 0 0; color: #6b7280; font-size: 14px;">Calculating your delivery fee...</p>
                        </div>

                        <!-- Error State -->
                        <div id="feeErrorSection" style="background: #fef2f2; border: 1px solid #fecaca; padding: 16px; border-radius: 12px; margin-bottom: 24px; display: none;">
                            <p style="margin: 0; color: #dc2626; font-size: 14px;" id="feeErrorMessage"></p>
                        </div>

                        <div class="form-group">
                            <label for="packageDescription">What are you sending?</label>
                            <input type="text" id="packageDescription" name="packageDescription" required placeholder="e.g. Documents, Food, Electronics">
                        </div>
                        <div class="form-group">
                            <label for="packageSize">Package Size</label>
                            <select id="packageSize" name="packageSize" required>
                                <option value="">Select size</option>
                                <option value="Small (fits in hand)">Small (fits in hand)</option>
                                <option value="Medium (fits in a bag)">Medium (fits in a bag)</option>
                                <option value="Large (needs a box)">Large (needs a box)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="preferredTime">Preferred Pickup Time</label>
                            <select id="preferredTime" name="preferredTime" required>
                                <option value="">Select time</option>
                                <option value="Morning (8am - 11am)">Morning (8am - 11am)</option>
                                <option value="Midday (11am - 2pm)">Midday (11am - 2pm)</option>
                                <option value="Afternoon (2pm - 5pm)">Afternoon (2pm - 5pm)</option>
                                <option value="As soon as possible">As soon as possible</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="additionalNotes">Additional Notes (optional)</label>
                            <textarea id="additionalNotes" name="additionalNotes" placeholder="Any special instructions" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <div class="form-success" id="formSuccess" style="display:none;">
                        <div class="success-icon">&#10003;</div>
                        <h3>Order Placed Successfully!</h3>
                        <p>We've received your delivery order. A confirmation email has been sent to you. Our team will contact you shortly to confirm pickup.</p>
                        <button type="button" class="btn btn-primary" id="closeSuccess">Done</button>
                    </div>

                    <!-- Error Message -->
                    <div class="form-error" id="formError" style="display:none;">
                        <p id="formErrorMsg">Something went wrong. Please try again.</p>
                    </div>
                </div>

                <!-- Fixed Bottom Actions -->
                <div class="form-actions" id="formActions">
                    <button type="button" class="btn btn-outline-dark btn-prev" data-prev="0" id="btnBack" style="display:none;">Back</button>
                    <span id="actionSpacer"></span>
                    <button type="button" class="btn btn-primary btn-next" data-next="2" id="btnNext">Next</button>
                    <button type="submit" class="btn btn-primary" id="submitOrder" style="display:none;">Submit Order</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Social Proof -->
    <section class="social-proof">
        <div class="container">
            <div class="section-heading">
                <span class="section-tag">Our Promise</span>
                <h2>Why you can trust us with your deliveries</h2>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-icon">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h3>Verified & Insured Riders</h3>
                    <p class="testimonial-text">Every rider is background-checked and trained. Your packages are in safe, reliable hands from pickup to delivery.</p>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-icon">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h3>Same-Day, Every Time</h3>
                    <p class="testimonial-text">We don't do "maybe tomorrow." Place your order before 2 pm and your package arrives the same day. That's our guarantee.</p>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-icon">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <h3>No Surprise Charges</h3>
                    <p class="testimonial-text">Starting from ₦1,500 — whether it's Ikeja to Lekki or Surulere to Victoria Island. One price, no distance fees, no surge pricing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section class="services" id="services">
        <div class="container">
            <div class="services-layout">
                <div class="services-intro">
                    <span class="section-tag">What We Do</span>
                    <h2>Delivery solutions that<br>work for you</h2>
                    <p>Whether you're sending a single package or running a business that ships daily, we've got you covered.</p>
                    <a href="#" class="btn btn-primary open-order-form">Get Started</a>
                </div>
                <div class="services-list">
                    <div class="service-card">
                        <div class="service-number">01</div>
                        <div class="service-body">
                            <h3>Same-Day Delivery</h3>
                            <p>Order before 12 pm, delivered the same day. Our bike riders cut through Lagos traffic so your package arrives on time.</p>
                        </div>
                    </div>
                    <div class="service-card">
                        <div class="service-number">02</div>
                        <div class="service-body">
                            <h3>Business Logistics</h3>
                            <p>Bulk discounts, dedicated account manager, and scheduled pickups for e-commerce stores, restaurants, and vendors.</p>
                        </div>
                    </div>
                    <div class="service-card">
                        <div class="service-number">03</div>
                        <div class="service-body">
                            <h3>Pickup &amp; Drop-off</h3>
                            <p>We come to you. No need to leave your home or office — we pick up and deliver door to door.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-heading">
                <span class="section-tag">Simple Process</span>
                <h2>Three steps to get your package delivered</h2>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-card-top">
                        <span class="step-num">01</span>
                        <div class="step-arrow">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                    <h3>Place your order</h3>
                    <p>Use our quick order form or send a WhatsApp message with pickup and delivery details.</p>
                </div>
                <div class="step-card">
                    <div class="step-card-top">
                        <span class="step-num">02</span>
                        <div class="step-arrow">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                    <h3>We pick it up</h3>
                    <p>A verified rider is assigned and arrives at your location to collect the package.</p>
                </div>
                <div class="step-card">
                    <div class="step-card-top">
                        <span class="step-num">03</span>
                    </div>
                    <h3>Delivered!</h3>
                    <p>Your package reaches its destination. You get a confirmation once it's done.</p>
                    <a href="#" class="btn btn-primary open-order-form" style="margin-top:16px;">Start Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Banner -->
    <section class="pricing-banner">
        <div class="container">
            <div class="pricing-card">
                <div class="pricing-left">
                    <span class="section-tag" style="color:#fcd34d;background:rgba(252,211,77,0.15);border-color:rgba(252,211,77,0.3);">Transparent Pricing</span>
                    <h2><span class="pricing-thin">Starting from</span>₦1,500 <span class="pricing-thin">per delivery</span></h2>
                    <p>Mainland to Island. Island to Mainland. Anywhere in Lagos. One flat rate, zero surprises.</p>
                </div>
                <div class="pricing-right">
                    <div class="pricing-includes">
                        <div class="pricing-check">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Same-day delivery</span>
                        </div>
                        <div class="pricing-check">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Door-to-door pickup</span>
                        </div>
                        <div class="pricing-check">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Delivery confirmation</span>
                        </div>
                        <div class="pricing-check">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>No hidden fees</span>
                        </div>
                    </div>
                    <a href="#" class="btn btn-white open-order-form">Place an Order</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Mobile App -->
    <section class="app-section">
        <div class="container">
            <div class="app-content">
                <div class="app-text">
                    <span class="section-tag">Coming Soon</span>
                    <h2>The Waka Line Logistics App called "Metter" — Delivery at your fingertips</h2>
                    <p>We're building something exciting. "Metter" will put you in full control of your deliveries — from booking to tracking to payment, all in one place.</p>
                    <div class="app-features">
                        <div class="app-feature">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Real-time GPS tracking</span>
                        </div>
                        <div class="app-feature">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <span>In-app payments</span>
                        </div>
                        <div class="app-feature">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            <span>Instant delivery notifications</span>
                        </div>
                        <div class="app-feature">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span>Schedule pickups in advance</span>
                        </div>
                        <div class="app-feature">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>Dedicated business dashboard</span>
                        </div>
                        <div class="app-feature">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#C1666B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                            <span>...and many more exciting features</span>
                        </div>
                    </div>
                    <div class="app-badges">
                        <div class="app-badge">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
                            <div>
                                <small>Coming soon on</small>
                                <strong>App Store</strong>
                            </div>
                        </div>
                        <div class="app-badge">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M3.61 1.814L13.793 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .61-.92zm.503-.386l11.27 6.356L12.1 11.07 4.113 1.428zM4.113 22.572L12.1 12.93l3.283 3.286L4.113 22.572zM16.653 15.486L20.36 12l-3.707-3.486-3.875 3.486 3.875 3.486z"/></svg>
                            <div>
                                <small>Coming soon on</small>
                                <strong>Google Play</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="app-visual">
                    <div class="app-phone">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            <div class="phone-header">
                                <img src="{{ asset('assets/img/mywakawaka-logo.png') }}" alt="Waka Line" height="24" style="height:24px;width:auto;">
                            </div>
                            <div class="phone-content">
                                <div class="phone-greeting">Good morning! 👋</div>
                                <div class="phone-card-mini">
                                    <span class="phone-label">Track your delivery</span>
                                    <div class="phone-progress-bar"><div class="phone-progress-fill"></div></div>
                                    <span class="phone-status">Rider en route to pickup</span>
                                </div>
                                <div class="phone-btn-mini">+ New Delivery</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta">
        <div class="container">
            <div class="cta-card">
                <h2>Ready to send a package?</h2>
                <p>Place your order in under 2 minutes. Fill out our form or message us on WhatsApp — whatever works for you.</p>
                <div class="final-cta-buttons">
                    <a href="#" class="btn btn-primary open-order-form">Place an Order</a>
                    <a href="https://wa.me/2348100665758?text=Hi,%20I%20want%20to%20make%20a%20delivery%20order" class="btn btn-outline">WhatsApp Us</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <div class="footer-brand">
                    <img src="{{ asset('assets/img/mywakawaka-logo-white.png') }}" alt="Waka Line Logistics" class="footer-logo">
                    <p class="footer-tagline">Your Delivery, Done Better.</p>
                </div>
                <div class="footer-contact">
                    <a href="https://wa.me/2348100665758">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        0810 066 5758
                    </a>
                    <a href="mailto:hello@mywakawaka.com">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        hello@mywakawaka.com
                    </a>
                    <span>
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        Kay Farm Estates, Iju Ishaga, Lagos
                    </span>
                </div>
            </div>
            <div class="footer-socials">
                <a href="https://facebook.com/wakalinelogistics" target="_blank" rel="noopener" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <link href="https://fonts.googleapis.com/css2?family=Tahoma&display=swap" rel="stylesheet">
                <a href="https://instagram.com/wakalinelogistics" target="_blank" rel="noopener" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                </a>
                <a href="https://tiktok.com/@wakalinelogistics" target="_blank" rel="noopener" aria-label="TikTok">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.51a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.28 8.28 0 0 0 4.76 1.5V6.8a4.83 4.83 0 0 1-1-.11z"/></svg>
                </a>
            </div>
            <div class="footer-bottom">
                <p>&copy; <span id="year"></span> Waka Line Logistics. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script src="{{ asset('assets/js/landing.js') }}"></script>
</body>
</html>
