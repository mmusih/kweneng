<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kweneng International Secondary School - Cambridge IGCSE School</title>
    <meta name="description"
        content="Kweneng International Secondary School offers a strong Cambridge IGCSE learning environment with academic excellence, discipline, and global-minded preparation.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        .home-whatsapp-button {
            position: fixed;
            right: calc(1.25rem + env(safe-area-inset-right, 0px));
            bottom: calc(1.25rem + env(safe-area-inset-bottom, 0px));
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            background: #15803d;
            color: #fff;
            font-size: 1.75rem;
            text-decoration: none;
            box-shadow: 0 4px 16px rgb(0 0 0 / 22%);
        }

        .home-whatsapp-button:hover {
            background: #166534;
            color: #fff;
        }

        .home-whatsapp-button:focus-visible {
            outline: 3px solid #0f172a;
            outline-offset: 4px;
        }

        [x-cloak] {
            display: none !important;
        }

        :root {
            --brand-blue: #2baffc;
            --brand-green: #55c360;
            --brand-dark: #0f172a;
            --brand-slate: #475569;
            --brand-light: #f8fbff;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #ffffff;
            color: #0f172a;
        }

        #navbar-spacer {
            transition: height .3s ease;
        }

        .admissions-alert {
            background: linear-gradient(90deg, #0f172a, #123f59 52%, #0f172a);
            color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .admissions-alert-inner {
            min-height: 4.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .admissions-alert-copy {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .admissions-alert-label {
            flex: none;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.75rem;
            border-radius: 9999px;
            background: #facc15;
            color: #713f12;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .admissions-alert-text {
            margin: 0;
            color: #e2e8f0;
            font-weight: 600;
        }

        .admissions-alert-text strong {
            color: #ffffff;
        }

        .admissions-alert-link {
            flex: none;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.7rem 1rem;
            border-radius: 0.75rem;
            background: #55c360;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 800;
            text-decoration: none;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .admissions-alert-link:hover {
            background: #44af50;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .page-section {
            padding: 4.5rem 0;
        }

        .section-heading {
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 800;
            color: var(--brand-dark);
            line-height: 1.15;
        }

        .section-subtext {
            color: #64748b;
            max-width: 760px;
            margin: 0.9rem auto 0;
        }

        .hero-shell {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(43, 175, 252, 0.12), transparent 35%),
                radial-gradient(circle at bottom right, rgba(85, 195, 96, 0.14), transparent 35%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding-top: 3rem;
            padding-bottom: 3rem;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 3rem;
            align-items: center;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.9rem;
            border-radius: 9999px;
            background: rgba(43, 175, 252, 0.12);
            color: #0369a1;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .hero-kicker .admissions-live-dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 9999px;
            background: #22c55e;
            box-shadow: 0 0 0 0.3rem rgba(34, 197, 94, 0.14);
        }

        .hero-title {
            line-height: 1.02;
            letter-spacing: -0.03em;
            margin-bottom: 1.2rem;
        }

        .hero-text {
            font-size: 1.08rem;
            color: var(--brand-slate);
            max-width: 700px;
        }

        .hero-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
            margin: 1.6rem 0 2rem;
        }

        .hero-feature {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            background: white;
            border: 1px solid #dbeafe;
            color: #1e293b;
            padding: 0.7rem 1rem;
            border-radius: 9999px;
            font-weight: 600;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
        }

        .btn-main {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.9rem 1.4rem;
            border-radius: 0.9rem;
            font-weight: 700;
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn-main:hover {
            transform: translateY(-2px);
        }

        .btn-blue {
            background: var(--brand-blue);
            color: white;
            box-shadow: 0 14px 30px rgba(43, 175, 252, 0.22);
        }

        .btn-blue:hover {
            background: #199fe9;
            color: white;
        }

        .btn-green {
            background: var(--brand-green);
            color: white;
            box-shadow: 0 14px 30px rgba(85, 195, 96, 0.22);
        }

        .btn-green:hover {
            background: #44af50;
            color: white;
        }

        .btn-outline {
            background: white;
            color: var(--brand-dark);
            border: 1px solid #cbd5e1;
        }

        .btn-outline:hover {
            border-color: var(--brand-blue);
            color: var(--brand-blue);
        }

        .hero-visual {
            position: relative;
        }

        .hero-image-frame {
            position: relative;
            border-radius: 2rem;
            overflow: hidden;
            box-shadow: 0 25px 55px rgba(15, 23, 42, 0.16);
            background: white;
        }

        .hero-image-frame img {
            width: 100%;
            display: block;
            height: auto;
        }

        .hero-badge {
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            background: rgba(15, 23, 42, 0.78);
            color: white;
            padding: 0.7rem 1rem;
            border-radius: 9999px;
            font-size: 0.88rem;
            font-weight: 700;
            backdrop-filter: blur(6px);
        }

        html.dark .hero-shell {
            background:
                radial-gradient(circle at top left, rgba(43, 175, 252, 0.18), transparent 38%),
                radial-gradient(circle at bottom right, rgba(85, 195, 96, 0.14), transparent 36%),
                linear-gradient(135deg, #0b1117, #17252d 52%, #101a20);
            border-bottom: 1px solid #2e4650;
        }

        html.dark .hero-kicker {
            background: rgba(56, 189, 248, 0.14);
            border: 1px solid rgba(125, 211, 252, 0.28);
            color: #bae6fd;
        }

        html.dark .hero-school-name {
            color: #f8fafc !important;
        }

        html.dark .hero-school-type {
            color: #7dd3fc !important;
        }

        html.dark .hero-tagline {
            color: #dbe7ea !important;
        }

        html.dark .hero-text {
            color: #cbd5e1;
        }

        html.dark .hero-feature {
            background: rgba(33, 42, 49, 0.9);
            border-color: #3d5d69;
            color: #f1f5f9;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.24);
        }

        html.dark .hero-feature i {
            color: #86efac;
        }

        html.dark .btn-outline {
            background: #212a31;
            border-color: #526b75;
            color: #f8fafc;
        }

        html.dark .btn-outline:hover {
            background: #2e3944;
            border-color: #38bdf8;
            color: #bae6fd;
        }

        html.dark .hero-image-frame {
            background: #212a31;
            border: 1px solid #3d5d69;
            box-shadow: 0 25px 55px rgba(0, 0, 0, 0.38);
        }

        html.dark .home-light-section {
            background: #182026 !important;
        }

        html.dark .home-section-intro {
            background: #212a31;
            border: 1px solid #3d5d69;
            border-radius: 1.5rem;
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.22);
            padding: 2rem;
        }

        html.dark .home-section-intro .section-heading {
            color: #f8fafc !important;
        }

        html.dark .home-section-intro .section-subtext {
            color: #d3d9d4 !important;
        }

        html.dark .feature-card {
            background: #212a31;
            border-color: #3d5d69;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.24);
        }

        html.dark .feature-card:hover {
            background: #26333c;
            border-color: #5e7b86;
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.30);
        }

        html.dark .feature-card h3 {
            color: #f8fafc !important;
        }

        html.dark .feature-card p {
            color: #cbd5e1 !important;
        }

        html.dark .feature-icon {
            background: #2e3944;
        }

        html.dark .transport-pill {
            background: #212a31;
            border-color: #3d5d69;
            color: #f8fafc;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.22);
        }

        .trust-band {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .trust-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        .trust-item {
            text-align: center;
            font-weight: 700;
            color: #334155;
            padding: 1rem 0.75rem;
        }

        .trust-item span {
            color: var(--brand-green);
            margin-right: 0.35rem;
        }

        .feature-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 1.2rem;
            padding: 1.6rem;
            transition: all 0.25s ease;
            height: 100%;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: #bae6fd;
            box-shadow: 0 16px 35px rgba(15, 23, 42, 0.09);
        }

        .feature-icon {
            width: 3.4rem;
            height: 3.4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            margin-bottom: 1rem;
            font-size: 1.45rem;
            background: #eff6ff;
            color: var(--brand-blue);
        }

        .achievement-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #2563eb 100%);
            color: white;
        }

        .achievement-panel img {
            width: 100%;
            max-width: 520px;
            max-height: 420px;
            object-fit: cover;
            border-radius: 1.25rem;
            box-shadow: 0 24px 45px rgba(0, 0, 0, 0.25);
        }

        .achievement-pill {
            display: inline-block;
            background: #facc15;
            color: #713f12;
            font-weight: 800;
            font-size: 0.85rem;
            border-radius: 9999px;
            padding: 0.45rem 0.95rem;
            margin-bottom: 1rem;
        }

        .stats-section {
            background: var(--brand-light);
        }

        .stat-card {
            background: white;
            border-radius: 1.2rem;
            padding: 1.8rem 1.2rem;
            text-align: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.05);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand-blue);
            line-height: 1;
        }

        .transport-pill {
            background: white;
            padding: 0.9rem 1.35rem;
            border-radius: 9999px;
            border: 1px solid #dbeafe;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            font-weight: 600;
            color: #1e293b;
        }

        .admission-section {
            position: relative;
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        .admission-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.82), rgba(30, 58, 138, 0.75));
        }

        .admission-content {
            position: relative;
            z-index: 1;
        }

        .yearbook-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid #dbeafe;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 16px 35px rgba(15, 23, 42, 0.06);
        }

        .yearbook-icon {
            width: 4rem;
            height: 4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: rgba(43, 175, 252, 0.12);
            color: var(--brand-blue);
            font-size: 1.7rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-features,
            .hero-buttons {
                justify-content: center;
            }

            .trust-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .admissions-alert-inner,
            .admissions-alert-copy {
                align-items: stretch;
                flex-direction: column;
            }

            .admissions-alert-inner {
                gap: 0.75rem;
            }

            .admissions-alert-copy {
                gap: 0.6rem;
            }

            .admissions-alert-label {
                align-self: flex-start;
            }

            .admissions-alert-link {
                justify-content: center;
            }

            .hero-shell {
                padding-top: 2rem;
            }

            .trust-grid {
                grid-template-columns: 1fr;
            }

            .section-heading {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body class="font-sans antialiased bg-white text-gray-900">

    @include('layouts.navigation')
    <div id="navbar-spacer"></div>

    <aside class="admissions-alert" aria-label="2027 admissions notice">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="admissions-alert-inner">
                <div class="admissions-alert-copy">
                    <span class="admissions-alert-label">
                        <i class="bi bi-megaphone-fill" aria-hidden="true"></i>
                        Now open
                    </span>
                    <p class="admissions-alert-text">
                        <strong>2027 admissions:</strong> Register your child’s interest early while places are available.
                    </p>
                </div>
                <a href="https://wa.me/26777738838?text=Hello%2C%20I%20would%20like%20to%20register%20my%20child%27s%20interest%20for%202027%20admission%20at%20Kweneng%20International%20Secondary%20School."
                    class="admissions-alert-link" target="_blank" rel="noopener">
                    Register interest
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </aside>

    <section class="hero-shell">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="hero-grid">
                <div>
                    <div class="hero-kicker">
                        <span class="admissions-live-dot" aria-hidden="true"></span>
                        2027 admissions now open
                    </div>

                    <h1 class="hero-title">
                        <span
                            class="hero-school-name block text-slate-900 text-[clamp(2.4rem,6vw,5rem)] font-extrabold tracking-tight leading-none">
                            Kweneng International
                        </span>
                        <span
                            class="hero-school-type block text-sky-600 text-[clamp(1.05rem,2.2vw,1.75rem)] font-bold mt-2">
                            Secondary School
                        </span>
                        <span
                            class="hero-tagline block text-slate-700 text-[clamp(1.35rem,3vw,2.2rem)] font-semibold mt-5 leading-tight">
                            Secure your child’s place for 2027
                        </span>
                    </h1>

                    <p class="hero-text mt-5">
                        Give your child a strong Cambridge IGCSE learning environment, experienced teachers, and a
                        disciplined academic culture. Applications for 2027 are open now, with a limited number of
                        places available.
                    </p>

                    <div class="hero-features">
                        <div class="hero-feature">
                            <i class="bi bi-mortarboard-fill"></i>
                            Cambridge IGCSE
                        </div>
                        <div class="hero-feature">
                            <i class="bi bi-trophy-fill"></i>
                            Proven Results
                        </div>
                    </div>

                    <div class="hero-buttons">
                        <a href="https://wa.me/26777738838?text=Hello%2C%20I%20would%20like%20to%20register%20my%20child%27s%20interest%20for%202027%20admission%20at%20Kweneng%20International%20Secondary%20School."
                            class="btn-main btn-green" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp"></i>
                            Register for 2027
                        </a>

                        <a href="{{ route('admissions') }}" class="btn-main btn-blue">
                            View admission details
                        </a>

                        <a href="{{ asset('documents/KISS-Student-Application-Form.pdf') }}"
                            class="btn-main btn-outline" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i>
                            Application form
                        </a>

                        <a href="{{ route('login') }}" class="btn-main btn-outline">
                            Academic Portal
                        </a>

                        <a href="{{ asset('documents/KISS-2025-Yearbook.pdf') }}" class="btn-main btn-outline"
                            target="_blank">
                            KISS 2025 Yearbook
                        </a>

                    </div>
                </div>

                <div class="hero-visual">
                    <div class="hero-image-frame">
                        <img src="{{ asset('images/06.png') }}"
                            alt="Students at Kweneng International Secondary School">
                        <div class="hero-badge">
                            <i class="fas fa-school mr-1"></i> Honour First
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="trust-band">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="trust-grid">
                <div class="trust-item"><span>✔</span> 95% Pass Rate</div>
                <div class="trust-item"><span>✔</span> 20 Years of Excellence</div>
                <div class="trust-item"><span>✔</span> 3× Top in World</div>
                <div class="trust-item"><span>✔</span> Honour First</div>
                <div class="trust-item"><span>✔</span> Cambridge IGCSE</div>
            </div>
        </div>
    </section>

    <section class="page-section bg-white home-light-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="home-section-intro text-center mb-12">
                <h2 class="section-heading">Why choose Kweneng International</h2>
                <p class="section-subtext">
                    A school environment built around academic quality, student support, discipline, and preparation
                    for success beyond the classroom.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Academic Excellence</h3>
                    <p class="text-slate-600">
                        Strong Cambridge teaching and structured support that helps learners perform at a high level.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">World-Class Achievement</h3>
                    <p class="text-slate-600">
                        A track record of outstanding international results that reflects discipline and academic focus.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">👩‍🏫</div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Qualified Teachers</h3>
                    <p class="text-slate-600">
                        Dedicated educators committed to developing each learner’s academic potential and confidence.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛡</div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Safe Learning Environment</h3>
                    <p class="text-slate-600">
                        A school culture that values discipline, respect, student welfare, and purposeful learning.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🚐</div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Accessible Location</h3>
                    <p class="text-slate-600">
                        The school is accessible through reliable public transportation serving major surrounding
                        communities.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3 class="text-xl font-bold mb-2 text-slate-900">Global Outlook</h3>
                    <p class="text-slate-600">
                        International academic standards combined with values that shape responsible, capable young
                        people.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="page-section achievement-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="achievement-panel flex justify-center lg:justify-start">
                    <img src="{{ asset('images/07.png') }}" alt="Student academic achievement"
                        class="w-full max-w-md lg:max-w-lg h-auto max-h-[420px] object-cover">
                </div>

                <div>
                    <div class="achievement-pill">TOP IN THE WORLD</div>
                    <h2 class="text-3xl md:text-4xl font-extrabold mb-5">
                        Consistent academic excellence on the international stage
                    </h2>
                    <p class="text-lg text-slate-200 mb-5">
                        Our students have achieved exceptional Cambridge results, with a strong record of top-level
                        performance and international recognition.
                    </p>
                    <p class="text-slate-300 mb-0">
                        This reflects our commitment to disciplined teaching, quality preparation, and a school culture
                        that expects excellence.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="page-section relative overflow-hidden">
        <div class="absolute inset-0">
            <img src="{{ asset('images/yearbook-bg.jpg') }}" alt="KISS Yearbook Background"
                class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-slate-900/70"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-900/80 via-slate-900/60 to-sky-900/70"></div>
        </div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center text-white py-10">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm mb-5">
                    <i class="bi bi-journal-richtext text-3xl text-white"></i>
                </div>

                <h2 class="section-heading mb-4 !text-white">Our 2025 Yearbook is here</h2>

                <p class="text-lg text-slate-200 max-w-3xl mx-auto mb-8 leading-relaxed">
                    Explore the highlights, memories, school life, and achievements that shaped 2025 at Kweneng
                    International Secondary School — including a special look back at our 20-year milestone journey.
                </p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ asset('documents/KISS-2025-Yearbook.pdf') }}" class="btn-main btn-blue"
                        target="_blank">
                        <i class="bi bi-download"></i>
                        Download Yearbook 2025
                    </a>
                </div>
            </div>
        </div>
    </section>>

    <section class="page-section stats-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="section-heading">A school built on results and growth</h2>
                <p class="section-subtext">
                    A quick snapshot of the values and outcomes that define the Kweneng International experience.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="stat-card">
                    <div class="stat-number">95%</div>
                    <p class="mt-3 font-semibold text-slate-700">Pass Rate</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">20</div>
                    <p class="mt-3 font-semibold text-slate-700">Years of Excellence</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3×</div>
                    <p class="mt-3 font-semibold text-slate-700">Top in the World</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number">IGCSE</div>
                    <p class="mt-3 font-semibold text-slate-700">Cambridge Programme</p>
                </div>
            </div>
        </div>
    </section>

    <section class="page-section admission-section" style="background-image: url('{{ asset('images/08.png') }}')">
        <div class="admission-overlay"></div>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 admission-content text-center py-10">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-5">
                2027 admissions are now open
            </h2>
            <p class="text-lg md:text-xl text-slate-200 mb-10">
                Give your child the opportunity to learn in a school community focused on academic excellence,
                discipline, and growth. Register early—places are limited.
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="https://wa.me/26777738838?text=Hello%2C%20I%20would%20like%20to%20register%20my%20child%27s%20interest%20for%202027%20admission%20at%20Kweneng%20International%20Secondary%20School."
                    class="btn-main btn-green" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp"></i>
                    Register interest for 2027
                </a>

                <a href="{{ route('admissions') }}"
                    class="btn-main bg-yellow-500 text-white hover:text-white">
                    View admission details
                </a>

                <a href="{{ route('contact') }}" class="btn-main bg-white text-green-700 hover:text-green-700">
                    Speak to Admissions
                </a>
            </div>
        </div>
    </section>

    <section class="page-section bg-white home-light-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="home-section-intro text-center mb-10">
                <h2 class="section-heading">School accessibility</h2>
                <p class="section-subtext">
                    Families can access the school through reliable public transportation from major surrounding
                    communities.
                </p>
            </div>

            <div class="flex flex-wrap justify-center gap-4">
                <div class="transport-pill">Molepolole</div>
                <div class="transport-pill">Gaborone</div>
                <div class="transport-pill">Mogoditshane</div>
                <div class="transport-pill">Metsimotlhabe</div>
                <div class="transport-pill">Thamaga</div>
            </div>
        </div>
    </section>

    @include('layouts.footer')

    <a href="https://wa.me/26777738838" class="home-whatsapp-button" target="_blank" rel="noopener noreferrer"
        aria-label="Chat with Kweneng International on WhatsApp (opens in a new tab)">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
    </a>
</body>

</html>
