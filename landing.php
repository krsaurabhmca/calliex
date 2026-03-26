<?php
// landing.php
require_once 'config/db.php';
require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calldesk CRM | Professional Sales & Call Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-soft: #f5f7ff;
            --secondary: #6366f1;
            --accent: #f59e0b;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #f1f5f9;
            --glass: rgba(255, 255, 255, 0.8);
            --gradient-1: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --gradient-2: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--white);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        h1, h2, h3, h4, .logo {
            font-family: 'Outfit', sans-serif;
        }

        /* Glass Navbar Refined */
        nav {
            position: fixed;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1400px;
            height: 70px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            z-index: 1000;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        nav.scrolled {
            top: 0;
            width: 100%;
            max-width: 100%;
            border-radius: 0;
            background: rgba(255, 255, 255, 0.95);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.02em;
        }

        .logo i {
            background: var(--gradient-1);
            color: white;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            opacity: 0.8;
        }

        .nav-links a:hover {
            color: var(--primary);
            opacity: 1;
        }

        .btn-auth {
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.875rem;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-login {
            color: var(--text-main);
            background: transparent;
            border: 1px solid var(--border);
        }

        .btn-login:hover {
            background: var(--border);
        }

        .btn-signup {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(79, 70, 229, 0.3);
        }

        /* Hero Redesign */
        .hero {
            padding: 200px 8% 120px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 100vh;
            background: 
                radial-gradient(circle at 10% 10%, rgba(79, 70, 229, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(124, 58, 237, 0.08) 0%, transparent 40%),
                radial-gradient(ellipse at 50% 50%, rgba(255, 255, 255, 0) 0%, #ffffff 100%),
                #ffffff;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://www.transparenttextures.com/patterns/cubes.png');
            opacity: 0.03;
            pointer-events: none;
        }

        /* Floating Decoration elements */
        .float-blob {
            position: absolute;
            width: 300px;
            height: 300px;
            background: var(--primary-soft);
            filter: blur(80px);
            border-radius: 50%;
            z-index: 1;
            opacity: 0.4;
            animation: move 20s infinite alternate linear;
        }

        @keyframes move {
            from { transform: translate(-10%, -10%); }
            to { transform: translate(100%, 100%); }
        }

        .hero-content {
            flex: 1.2;
            max-width: 650px;
            z-index: 10;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: #eef2ff;
            color: var(--primary);
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 800;
            margin-bottom: 28px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            border: 1px solid rgba(79, 70, 229, 0.15);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08);
        }

        .hero h1 {
            font-size: 4.85rem;
            font-weight: 800;
            line-height: 1.0;
            color: var(--text-main);
            letter-spacing: -0.05em;
            margin-bottom: 28px;
        }

        .hero h1 span {
            background: linear-gradient(to right, #4f46e5, #9333ea, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-size: 200% auto;
            animation: shine 5s linear infinite;
        }

        @keyframes shine {
            to { background-position: 200% center; }
        }

        .hero p {
            font-size: 1.35rem;
            color: var(--text-muted);
            margin-bottom: 48px;
            line-height: 1.65;
            max-width: 580px;
        }

        .hero-visual {
            flex: 1;
            position: relative;
            display: flex;
            justify-content: flex-end;
            z-index: 10;
        }

        .hero-mockup {
            width: 120%;
            max-width: 900px;
            filter: drop-shadow(0 40px 80px rgba(0,0,0,0.18));
            animation: float 6s ease-in-out infinite;
            transform: perspective(1000px) rotateY(-5deg);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Trusted By */
        .trusted {
            padding: 40px 8%;
            text-align: center;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 60px;
            flex-wrap: wrap;
            opacity: 0.6;
            background: #fafafa;
        }

        .trusted span { font-weight: 700; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; }
        .trusted i { font-size: 1.5rem; }

        /* Feature Cards Premium */
        section {
            padding: 120px 8%;
            position: relative;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 80px;
        }

        .section-tag {
            color: var(--primary);
            font-weight: 800;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 12px;
            display: block;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 20px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            border: 1px solid var(--border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(79, 70, 229, 0.05);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 0; height: 0;
            border-style: solid;
            border-width: 0 100px 100px 0;
            border-color: transparent var(--primary-soft) transparent transparent;
            opacity: 0;
            transition: all 0.3s;
        }

        .feature-card:hover::before {
            opacity: 0.3;
        }

        .feat-icon {
            width: 56px;
            height: 56px;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 28px;
            transition: all 0.3s;
        }

        .feature-card:hover .feat-icon {
            background: var(--primary);
            color: white;
            transform: scale(1.1) rotate(5deg);
        }

        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats-section {
            background: var(--text-main);
            color: white;
            padding: 80px 8%;
            display: flex;
            justify-content: space-between;
            border-radius: 40px;
            margin: 0 4%;
            text-align: center;
        }

        .stat-item h2 { font-size: 3.5rem; font-weight: 800; margin-bottom: 5px; color: var(--secondary); }
        .stat-item p { font-size: 0.9rem; opacity: 0.7; font-weight: 600; text-transform: uppercase; }

        /* Workflow Timeline */
        .workflow-timeline {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
        }

        .workflow-step {
            display: flex;
            gap: 40px;
            padding-bottom: 50px;
            position: relative;
        }

        .workflow-step::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 40px;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }

        .workflow-step:last-child::after { display: none; }

        .step-num {
            width: 40px;
            height: 40px;
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            z-index: 1;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .step-content h3 { font-size: 1.4rem; margin-bottom: 8px; }
        .step-content p { color: var(--text-muted); font-size: 1.05rem; }

        /* Pricing Card */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .pricing-card {
            background: white;
            padding: 50px 40px;
            border-radius: 30px;
            border: 1px solid var(--border);
            text-align: center;
            transition: all 0.3s;
        }

        .pricing-card.featured {
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(79, 70, 229, 0.1);
            transform: scale(1.05);
            position: relative;
        }

        .featured-tag {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent);
            color: white;
            padding: 4px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .price { font-size: 3rem; font-weight: 800; margin: 20px 0; }
        .price span { font-size: 1rem; color: var(--text-muted); }

        .pricing-btn {
            display: block;
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            background: var(--border);
            color: var(--text-main);
            text-decoration: none;
            font-weight: 800;
            margin-top: 30px;
            transition: 0.3s;
        }

        .pricing-card.featured .pricing-btn {
            background: var(--primary);
            color: white;
        }

        .pricing-list {
            list-style: none;
            text-align: left;
            margin-top: 30px;
        }

        .pricing-list li {
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pricing-list li i { color: #10b981; }

        /* CTA Section */
        .cta {
            background: var(--gradient-1);
            padding: 80px 8%;
            border-radius: 40px;
            margin: 0 4% 100px;
            text-align: center;
            color: white;
        }

        .cta h2 { font-size: 3.5rem; margin-bottom: 20px; }
        .cta p { font-size: 1.25rem; opacity: 0.9; margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto; }

        /* Footer */
        .footer {
            padding: 100px 8% 50px;
            background: #fafafa;
            border-top: 1px solid var(--border);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 50px;
            margin-bottom: 80px;
        }

        .footer-logo {
            font-size: 1.8rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
        }

        .footer-desc { color: var(--text-muted); font-size: 0.95rem; line-height: 1.8; max-width: 320px; }
        .footer-col h4 { margin-bottom: 25px; font-weight: 800; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 12px; }
        .footer-col ul li a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .footer-col ul li a:hover { color: var(--primary); padding-left: 5px; }

        .footer-bottom {
            padding-top: 30px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .hero h1 { font-size: 3.5rem; }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
            .pricing-grid { grid-template-columns: repeat(2, 1fr); }
            .pricing-card.featured { transform: none; margin-bottom: 30px; }
        }

        @media (max-width: 900px) {
            nav { padding: 0 20px; }
            .hero { flex-direction: column; text-align: center; padding-top: 140px; }
            .hero-visual { width: 100%; margin-top: 50px; justify-content: center; }
            .hero-mockup { width: 100%; }
            .stats-section { flex-direction: column; gap: 40px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 600px) {
            .hero h1 { font-size: 2.8rem; }
            .feature-grid { grid-template-columns: 1fr; }
            .pricing-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
            .section-title { font-size: 2.2rem; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>

    <nav id="navbar">
        <a href="#" class="logo">
            <i class="fas fa-headset"></i> Calldesk
        </a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#how-it-works">How it Works</a>
            <a href="#pricing">Pricing</a>
            <div style="display: flex; gap: 15px; margin-left: 20px;">
                <a href="login.php" class="btn-auth btn-login">Login</a>
                <a href="signup.php" class="btn-auth btn-signup">Get Started</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="float-blob" style="top: 10%; left: 10%; background: #6366f1;"></div>
        <div class="float-blob" style="bottom: 10%; right: 10%; background: #a855f7; width: 400px; height: 400px;"></div>
        
        <div class="hero-content" data-aos="fade-right">
            <div class="hero-badge">
                <i class="fas fa-magic" style="color: #f59e0b;"></i> Your Sales Team's Best Friend
            </div>
            <h1>Stop Losing Leads in <span>Phone Logs</span> Forever.</h1>
            <p>Most sales are lost because follow-ups are too slow or forgotten. Calldesk bridges the gap between your phone and your dashboard automatically.</p>
            <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                <a href="signup.php" class="btn-auth btn-signup" style="padding: 20px 48px; font-size: 1.2rem; border-radius: 16px;">Try it Free for 30 Days</a>
                <a href="#problem" class="btn-auth btn-login" style="padding: 18px 30px; border: 2px solid #334155; border-radius: 16px;">See How it Works</a>
            </div>
        </div>
        <div class="hero-visual" data-aos="zoom-in" data-aos-delay="200">
            <img src="assets/mockup_v2.png" alt="Premium Calldesk CRM Dashboard" class="hero-mockup">
        </div>
    </section>

    <!-- The Problem Section -->
    <section id="problem" style="background: #fff5f5; border-radius: 40px; margin: 40px 4%; padding: 80px 8%;">
        <div class="section-header" style="margin-bottom: 50px;">
            <span class="section-tag" style="color: #ef4444;">The Invisible Leak</span>
            <h2 class="section-title">Is your phone hiding missed revenue?</h2>
            <p>Every lead that calls and doesn't get a prompt follow-up is a lead walking away to your competitor. Manual entry is slow, painful, and error-prone.</p>
        </div>
        <div class="feature-grid">
            <div style="background: #fff; padding: 30px; border-radius: 20px; text-align: center; border: 1px solid #fee2e2;">
                <div style="font-size: 2rem; color: #ef4444; margin-bottom: 15px;"><i class="fas fa-skull-crossbones"></i></div>
                <h4 style="margin-bottom: 10px;">The "Forgotten" Call</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Agents get 20+ calls a day, and 5 leads are never added to the CRM. They are lost in the phone history.</p>
            </div>
            <div style="background: #fff; padding: 30px; border-radius: 20px; text-align: center; border: 1px solid #fee2e2;">
                <div style="font-size: 2rem; color: #ef4444; margin-bottom: 15px;"><i class="fas fa-hourglass-end"></i></div>
                <h4 style="margin-bottom: 10px;">The "Lazy" Data Entry</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Hours wasted every week just typing numbers into a computer. Hours that should be spent talking to clients.</p>
            </div>
            <div style="background: #fff; padding: 30px; border-radius: 20px; text-align: center; border: 1px solid #fee2e2;">
                <div style="font-size: 2rem; color: #ef4444; margin-bottom: 15px;"><i class="fas fa-unlink"></i></div>
                <h4 style="margin-bottom: 10px;">The "Broken" Follow-up</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">When a lead calls back, the agent doesn't have the context or history. You look unprofessional.</p>
            </div>
        </div>
    </section>

    <!-- The Solution Section -->
    <section id="features">
        <div class="section-header">
            <span class="section-tag">How We Solve It</span>
            <h2 class="section-title">The Automatic Bridge.</h2>
            <p>Calldesk makes sure no lead is ever left behind. It works in the background so you can work on the goal.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card" data-aos="fade-up">
                <div class="feat-icon"><i class="fas fa-magic"></i></div>
                <h3>Hands-Free Sync</h3>
                <p>You call from your phone, and it appears on your dashboard instantly. No typing, no forgetting, just magic.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feat-icon"><i class="fab fa-whatsapp"></i></div>
                <h3>Personalized Greeting</h3>
                <p>Send an automated "Thank you for calling" message on WhatsApp immediately after the call. Be remembered.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feat-icon"><i class="fas fa-layer-group"></i></div>
                <h3>One-Click Pipeline</h3>
                <p>Move leads from 'Intersted' to 'Done' with a single tap. Organized team, happy customers.</p>
            </div>
        </div>
    </section>

    <!-- Who can use it? -->
    <section style="background: #f8fafc; border-radius: 40px; margin: 40px 4%;">
        <div class="section-header">
            <span class="section-tag">Ideal For You</span>
            <h2 class="section-title">Who is this for?</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px;">
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-building"></i></div>
                <div>
                    <h3 style="margin-bottom: 8px;">Real Estate Agencies</h3>
                    <p style="color: var(--text-muted);">Manage dozens of inquiries for your properties without missing a single buyer call.</p>
                </div>
            </div>
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-graduation-cap"></i></div>
                <div>
                    <h3 style="margin-bottom: 8px;">Education Counsellors</h3>
                    <p style="color: var(--text-muted);">Keep your student inquiries organized and ensure every student gets a callback.</p>
                </div>
            </div>
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #f59e0b; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-user-tie"></i></div>
                <div>
                    <h3 style="margin-bottom: 8px;">Sales & Debt Recovery</h3>
                    <p style="color: var(--text-muted);">Track agent productivity and call durations to ensure your team is always active.</p>
                </div>
            </div>
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #ec4899; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fas fa-concierge-bell"></i></div>
                <div>
                    <h3 style="margin-bottom: 8px;">Service Businesses</h3>
                    <p style="color: var(--text-muted);">Interior designers, cleaning services, or event planners managing daily inquiries.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="stats-section" data-aos="fade-up">
        <div class="stat-item">
            <h2>2x</h2>
            <p>Faster Follow-ups</p>
        </div>
        <div class="stat-item">
            <h2>15%</h2>
            <p>Sales Conversion Boost</p>
        </div>
        <div class="stat-item">
            <h2>0%</h2>
            <p>Manual Entry Error</p>
        </div>
        <div class="stat-item">
            <h2>1hr+</h2>
            <p>Saved per agent daily</p>
        </div>
    </div>

    <section id="how-it-works" style="background: #fafbff;">
        <div class="section-header">
            <span class="section-tag">Simplifying Success</span>
            <h2 class="section-title">The Calldesk Workflow</h2>
        </div>
        <div class="workflow-timeline">
            <div class="workflow-step" data-aos="fade-left">
                <div class="step-num">1</div>
                <div class="step-content">
                    <h3>Connect Your Device</h3>
                    <p>Download the <a href="https://play.google.com/store/apps/details?id=com.offerplant.calldeskapp" target="_blank" style="color: var(--primary); font-weight: 700; text-decoration: none;">Calldesk Android App</a> and link your business phone. Our system sets up a secure bridge to your organization.</p>
                </div>
            </div>
            <div class="workflow-step" data-aos="fade-left" data-aos-delay="100">
                <div class="step-num">2</div>
                <div class="step-content">
                    <h3>Call & Interact</h3>
                    <p>Handle your business calls as usual. Calldesk works in the background to track duration, frequency, and contact details.</p>
                </div>
            </div>
            <div class="workflow-step" data-aos="fade-left" data-aos-delay="200">
                <div class="step-num">3</div>
                <div class="step-content">
                    <h3>Sync to Dashboard</h3>
                    <p>Tap 'Sync' to push data to the web portal. Automate WhatsApp follow-ups and add remarks in seconds.</p>
                </div>
            </div>
            <div class="workflow-step" data-aos="fade-left" data-aos-delay="300">
                <div class="step-num">4</div>
                <div class="step-content">
                    <h3>Analyze & Scale</h3>
                    <p>Use visual reports to identify top performers and optimize your sales script. Scale your revenue with data-driven insights.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing">
        <div class="section-header">
            <span class="section-tag">Flexible Pricing</span>
            <h2 class="section-title">Choose the right plan for you.</h2>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card" data-aos="fade-up">
                <h3>Free Plan</h3>
                <div class="price">₹0 <span>/ 30 Days</span></div>
                <p>30 Day Free Trial for up to 3 Users</p>
                <ul class="pricing-list">
                    <li><i class="fas fa-check"></i> Up to 3 User Accounts</li>
                    <li><i class="fas fa-check"></i> 30 Days Experience</li>
                    <li><i class="fas fa-check"></i> Basic Call Logs</li>
                    <li><i class="fas fa-check"></i> WhatsApp Direct</li>
                </ul>
                <a href="signup.php" class="pricing-btn">Start Free Trial</a>
            </div>
            <div class="pricing-card featured" data-aos="fade-up" data-aos-delay="100">
                <div class="featured-tag">MOST POPULAR</div>
                <h3>Pro Team</h3>
                <div class="price">₹199 <span>/ user / month</span></div>
                <p>Advanced features for growing teams.</p>
                <ul class="pricing-list">
                    <li><i class="fas fa-check"></i> Unlimited Team Members</li>
                    <li><i class="fas fa-check"></i> Advanced Analytics</li>
                    <li><i class="fas fa-check"></i> Call Recordings (10GB)</li>
                    <li><i class="fas fa-check"></i> Geography Manager</li>
                    <li><i class="fas fa-check"></i> Priority Support</li>
                </ul>
                <a href="signup.php" class="pricing-btn">Upgrade Now</a>
            </div>
            <div class="pricing-card" data-aos="fade-up" data-aos-delay="200">
                <h3>Enterprise</h3>
                <div class="price">Custom</div>
                <p>For large scale operations.</p>
                <ul class="pricing-list">
                    <li><i class="fas fa-check"></i> Unlimited Users</li>
                    <li><i class="fas fa-check"></i> API Integration</li>
                    <li><i class="fas fa-check"></i> Dedicated Manager</li>
                    <li><i class="fas fa-check"></i> White-labeling</li>
                </ul>
                <a href="signup.php" class="pricing-btn">Contact Sales</a>
            </div>
        </div>
    </section>

    <div class="cta" data-aos="zoom-in">
        <h2 style="font-size: 2.5rem; margin-bottom: 30px;">Download Calldesk Today</h2>
        <div style="display: flex; gap: 30px; justify-content: center; flex-wrap: wrap; align-items: center;">
            <a href="https://play.google.com/store/apps/details?id=com.offerplant.calldeskapp" target="_blank" style="display: block;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Get it on Google Play" style="height: 60px;">
            </a>
            <div style="width: 1px; height: 50px; background: rgba(255,255,255,0.2);"></div>
            <a href="signup.php" class="btn-auth btn-signup" style="background: white; color: var(--text-main); padding: 20px 50px; font-size: 1.2rem;">Create Your Free Account</a>
        </div>
        <p style="margin-top: 40px; font-size: 0.9rem; opacity: 0.7;">No credit card required to start your 30-day trial for up to 3 users.</p>
    </div>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="#" class="footer-logo">
                    <i class="fas fa-headset"></i> Calldesk
                </a>
                <p class="footer-desc">The ultimate mobile-first CRM for calling teams. Built with love for real estate, education, and sales agencies.</p>
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <a href="#" style="color:var(--text-muted); font-size:1.2rem;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="color:var(--text-muted); font-size:1.2rem;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color:var(--text-muted); font-size:1.2rem;"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" style="color:var(--text-muted); font-size:1.2rem;"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Product</h4>
                <ul>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#">Security</a></li>
                    <li><a href="#">Roadmap</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="docs.php">Documentation</a></li>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="delete-account.php">Delete Account</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Calldesk CRM. All rights reserved.</p>
            <p>Made by <a href="https://digital-seal.io" style="color:var(--primary); text-decoration:none; font-weight:700;">Digital Seal</a></p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            offset: 100,
            once: true,
            easing: 'ease-out-cubic'
        });

        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
