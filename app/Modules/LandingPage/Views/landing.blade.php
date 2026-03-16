<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reach Us Anywhere, We Are Closer Than You Think | Waka Line Logistics</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <style>
        /* CSS Variables - Brand Colors */
        :root {
            --primary: #C1666B;
            --primary-dark: #2F3437;
            --primary-foreground: #FFFFFF;
            --secondary: #F5F5F5;
            --accent-warm: #C1666B;
            --accent-warm-hover: #A85559;
            --accent-warm-light: rgba(193, 102, 107, 0.1);
            --background: #FFFFFF;
            --foreground: #2F3437;
            --muted-foreground: #6B7280;
            --border: #E5E7EB;
            --whatsapp: #25D366;
            --phone: #4A90E2;
            --email: #EA4335;
            --instagram: #E4405F;
            --facebook: #1877F2;
            --twitter: #1DA1F2;
            --tiktok: #000000;
            --whatsapp-channel: #128C7E;
            --location: #34A853;
            --website: #7C3AED;
        }

        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Tahoma, Geneva, sans-serif;
            color: var(--foreground);
            background-image: url('{{ asset('assets/img/hero-delivery.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            line-height: 1.6;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(193, 102, 107, 0.85), rgba(47, 52, 55, 0.85));
            z-index: -1;
        }

        .container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            background: var(--background);
            border-radius: 2rem;
            padding: 3rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        @media (max-width: 640px) {
            .container {
                padding: 2rem 1.5rem;
                border-radius: 1.5rem;
            }
        }

        /* Profile Section */
        .profile-section {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: fadeInDown 0.6s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-image {
            width: auto;
            height: 100px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 0.5rem;
        }

        .logo-image {
            width: 100%;
            height: 80px;
            object-fit: contain;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 800;
            color: var(--foreground);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .profile-tagline {
            font-size: 1rem;
            color: var(--muted-foreground);
            font-weight: 500;
        }

        /* Links Section */
        .links-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .contact-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: var(--background);
            border: 2px solid var(--border);
            border-radius: 1rem;
            text-decoration: none;
            color: var(--foreground);
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            animation: fadeInUp 0.6s ease-out backwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Stagger animation for links */
        .contact-link:nth-child(1) { animation-delay: 0.1s; }
        .contact-link:nth-child(2) { animation-delay: 0.15s; }
        .contact-link:nth-child(3) { animation-delay: 0.2s; }
        .contact-link:nth-child(4) { animation-delay: 0.25s; }
        .contact-link:nth-child(5) { animation-delay: 0.3s; }
        .contact-link:nth-child(6) { animation-delay: 0.35s; }
        .contact-link:nth-child(7) { animation-delay: 0.4s; }
        .contact-link:nth-child(8) { animation-delay: 0.45s; }

        .contact-link:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            border-color: var(--accent-warm);
        }

        .contact-link:active {
            transform: translateY(-2px);
        }

        .link-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .contact-link:hover .link-icon {
            transform: scale(1.1);
        }

        .link-icon svg {
            width: 24px;
            height: 24px;
            stroke: var(--primary-foreground);
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Icon Colors */
        .link-icon.whatsapp {
            background: linear-gradient(135deg, var(--whatsapp), #1EBE57);
        }

        .link-icon.phone {
            background: linear-gradient(135deg, var(--phone), #357ABD);
        }

        .link-icon.email {
            background: linear-gradient(135deg, var(--email), #C5221F);
        }

        .link-icon.instagram {
            background: linear-gradient(135deg, #F58529, #DD2A7B, #8134AF);
        }

        .link-icon.facebook {
            background: linear-gradient(135deg, var(--facebook), #0C63D4);
        }

        .link-icon.twitter {
            background: linear-gradient(135deg, var(--twitter), #0C8DE4);
        }

        .link-icon.tiktok {
            background: linear-gradient(135deg, var(--tiktok), #333333);
        }

        .link-icon.whatsapp-channel {
            background: linear-gradient(135deg, var(--whatsapp-channel), #0D7A6F);
        }

        .link-icon.location {
            background: linear-gradient(135deg, var(--location), #2D8E47);
        }

        .link-icon.website {
            background: linear-gradient(135deg, var(--website), #6D28D9);
        }

        .link-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .link-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--foreground);
        }

        .link-subtitle {
            font-size: 0.875rem;
            color: var(--muted-foreground);
            font-weight: 500;
        }

        .link-arrow {
            flex-shrink: 0;
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .contact-link:hover .link-arrow {
            opacity: 1;
            transform: translateX(4px);
        }

        .link-arrow svg {
            width: 20px;
            height: 20px;
            stroke: var(--accent-warm);
            fill: none;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 2rem 0 1rem;
            animation: fadeIn 0.8s ease-out 0.5s backwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .footer p {
            color: var(--muted-foreground);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            body {
                padding: 1.5rem 1rem;
            }

            .profile-name {
                font-size: 1.75rem;
            }

            .profile-tagline {
                font-size: 0.95rem;
            }

            .logo-icon {
                width: 80px;
                height: 80px;
            }

            .logo-icon svg {
                width: 40px;
                height: 40px;
            }

            .contact-link {
                padding: 1rem 1.25rem;
            }

            .link-icon {
                width: 48px;
                height: 48px;
            }

            .link-title {
                font-size: 1rem;
            }

            .link-subtitle {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .profile-name {
                font-size: 1.5rem;
            }

            .contact-link {
                padding: 0.875rem 1rem;
                gap: 0.875rem;
            }

            .link-icon {
                width: 44px;
                height: 44px;
            }

            .link-icon svg {
                width: 22px;
                height: 22px;
            }
        }

        /* WhatsApp Floating Button */
        .whatsapp-float {
            position: fixed;
            width: 60px;
            height: 60px;
            bottom: 30px;
            right: 30px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            animation: pulse-whatsapp 2s infinite;
        }

        .whatsapp-float:hover {
            background-color: #20ba5a;
            transform: scale(1.1);
            box-shadow: 2px 2px 15px rgba(0, 0, 0, 0.4);
        }

        .whatsapp-float svg {
            width: 35px;
            height: 35px;
        }

        .whatsapp-float::before {
            content: "Chat with us!";
            position: absolute;
            right: 70px;
            background-color: var(--primary-dark);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            white-space: nowrap;
            font-size: 14px;
            font-family: Tahoma, Geneva, sans-serif;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .whatsapp-float:hover::before {
            opacity: 1;
        }

        @keyframes pulse-whatsapp {
            0% {
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(37, 211, 102, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
            }
        }

        @media (max-width: 640px) {
            .whatsapp-float {
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
            }

            .whatsapp-float svg {
                width: 28px;
                height: 28px;
            }

            .whatsapp-float::before {
                display: none;
            }
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .contact-link {
                page-break-inside: avoid;
            }

            .whatsapp-float {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profile Section -->
        <div class="profile-section">
            <div class="profile-image">
                <a href="{{ route('home') }}" class="nav-logo">
                    <img src="{{ asset('assets/img/wakalinelogistics-logo.png') }}" alt="Waka Line Logistics Logo" class="logo-image">
                </a>
            </div>
            <h1 class="profile-name">Waka Line Logistics</h1>
            <p class="profile-tagline">Your Delivery, Done Better.</p>
        </div>

        <!-- Contact Links -->
        <div class="links-section">
            <!-- WhatsApp -->
            <a href="https://wa.me/2348100665758?text=Hi%20Waka%20Line%20Logistics!%20I%20need%20help%20with%20" class="contact-link" target="_blank" rel="noopener noreferrer">
                <div class="link-icon whatsapp">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                </div>
                <div class="link-content">
                    <span class="link-title">WhatsApp</span>
                    <span class="link-subtitle">+234 810 066 5758</span>
                </div>
                <div class="link-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <!-- Instagram -->
            <a href="https://instagram.com/wakalinelogistics" class="contact-link" target="_blank" rel="noopener noreferrer">
                <div class="link-icon instagram">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </div>
                <div class="link-content">
                    <span class="link-title">Instagram</span>
                    <span class="link-subtitle">@wakalinelogistics</span>
                </div>
                <div class="link-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <!-- Facebook -->
            <a href="https://facebook.com/wakalinelogistics" class="contact-link" target="_blank" rel="noopener noreferrer">
                <div class="link-icon facebook">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                    </svg>
                </div>
                <div class="link-content">
                    <span class="link-title">Facebook</span>
                    <span class="link-subtitle">@wakalinelogistics</span>
                </div>
                <div class="link-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <!-- TikTok -->
            <a href="https://tiktok.com/@wakalinelogistics" class="contact-link" target="_blank" rel="noopener noreferrer">
                <div class="link-icon tiktok">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path>
                    </svg>
                </div>
                <div class="link-content">
                    <span class="link-title">TikTok</span>
                    <span class="link-subtitle">@wakalinelogistics</span>
                </div>
                <div class="link-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <!-- WhatsApp Channel -->
            <a href="https://whatsapp.com/channel/0029Vb6zwcOInlqV0oXbCw23" class="contact-link" target="_blank" rel="noopener noreferrer">
                <div class="link-icon whatsapp-channel">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                </div>
                <div class="link-content">
                    <span class="link-title">WhatsApp Channel</span>
                    <span class="link-subtitle">Join our broadcast channel</span>
                </div>
                <div class="link-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <!-- Website -->
            <a href="https://www.wakalinelogistics.com" class="contact-link" target="_blank" rel="noopener noreferrer">
                <div class="link-icon website">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                </div>
                <div class="link-content">
                    <span class="link-title">Visit Our Website</span>
                    <span class="link-subtitle">www.wakalinelogistics.com</span>
                </div>
                <div class="link-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <!-- Email -->
            <a href="mailto:hello@wakalinelogistics.com?subject=Inquiry%20from%20Website&body=Hi%20Waka%20Line%20Logistics,%0D%0A%0D%0AI%20would%20like%20to%20inquire%20about%20" class="contact-link">
                <div class="link-icon email">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div class="link-content">
                    <span class="link-title">Email Us</span>
                    <span class="link-subtitle">hello@wakalinelogistics.com</span>
                </div>
                <div class="link-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <!-- Phone -->
            <a href="tel:+2348100665758" class="contact-link">
                <div class="link-icon phone">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </div>
                <div class="link-content">
                    <span class="link-title">Call Us</span>
                    <span class="link-subtitle">+234 810 066 5758</span>
                </div>
                <div class="link-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; {{ date('Y') }}. Waka Line Logistics. All rights reserved.</p>
        </footer>
    </div>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/2348100665758?text=Hi%20Waka%20Line%20Logistics!%20I%20need%20help%20with " 
       class="whatsapp-float" 
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Chat on WhatsApp">
        <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 0c-8.837 0-16 7.163-16 16 0 2.825 0.737 5.607 2.137 8.048l-2.137 7.952 7.933-2.127c2.42 1.37 5.173 2.127 8.067 2.127 8.837 0 16-7.163 16-16s-7.163-16-16-16zM16 29.467c-2.482 0-4.908-0.646-7.07-1.87l-0.507-0.292-4.713 1.262 1.262-4.669-0.292-0.508c-1.207-2.100-1.847-4.507-1.847-6.924 0-7.435 6.050-13.485 13.485-13.485s13.485 6.050 13.485 13.485c0 7.435-6.050 13.485-13.485 13.485zM21.960 18.828c-0.31-0.155-1.828-0.902-2.111-1.005-0.283-0.103-0.489-0.155-0.695 0.155s-0.798 1.005-0.978 1.211c-0.18 0.206-0.36 0.232-0.67 0.077s-1.308-0.482-2.490-1.536c-0.921-0.821-1.542-1.834-1.723-2.144s-0.019-0.476 0.136-0.631c0.139-0.139 0.31-0.36 0.464-0.541 0.155-0.18 0.206-0.31 0.31-0.515 0.103-0.206 0.052-0.386-0.026-0.541s-0.695-1.675-0.953-2.293c-0.251-0.6-0.506-0.519-0.695-0.529-0.18-0.009-0.386-0.011-0.592-0.011s-0.541 0.077-0.824 0.386c-0.283 0.31-1.080 1.057-1.080 2.576s1.106 2.988 1.26 3.194c0.155 0.206 2.185 3.337 5.293 4.679 0.739 0.319 1.316 0.51 1.766 0.653 0.743 0.232 1.418 0.199 1.952 0.121 0.595-0.089 1.828-0.748 2.086-1.469s0.258-1.341 0.18-1.469c-0.077-0.129-0.283-0.206-0.592-0.36z" fill="white"/>
        </svg>
    </a>
</body>
</html>
