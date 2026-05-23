<!DOCTYPE html>
<html>

<head>
    <title>Student Login Slips</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            background: #e9ecef;
            padding: 1rem;
        }

        .no-print {
            margin-bottom: 1rem;
            text-align: center;
        }

        button {
            padding: 0.5rem 1.25rem;
            background: #0b2b40;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #1a4a63;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Each slip card - compact height */
        .card {
            width: calc(50% - 0.4rem);
            min-width: 350px;
            flex: 1 1 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Compact header */
        .card-header {
            background: #0a2b3e;
            color: #ffffff;
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .school-name {
            font-size: 0.85rem;
            font-weight: 700;
        }

        .slip-label {
            font-size: 0.6rem;
            font-weight: 500;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Compact body */
        .card-body {
            padding: 0.8rem 1rem 0.6rem;
        }

        .student-name {
            font-size: 1rem;
            font-weight: 700;
            color: #0a2b3e;
            margin-bottom: 0.1rem;
        }

        .student-meta {
            font-size: 0.6rem;
            color: #6c7a89;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 600;
            margin-bottom: 0.6rem;
            border-left: 2px solid #0a2b3e;
            padding-left: 0.5rem;
        }

        /* Compact grid */
        .cred-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.7rem;
        }

        .cred-block {
            border-radius: 6px;
            padding: 0.5rem;
        }

        .cred-block.student-block {
            background: #f0f7ff;
            border: 1px solid #cde3f5;
        }

        .cred-block.parent-block {
            background: #f0faf4;
            border: 1px solid #c8e6d9;
        }

        .cred-section-label {
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .student-block .cred-section-label {
            color: #0056b3;
        }

        .parent-block .cred-section-label {
            color: #1e7e34;
        }

        .cred-row {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
            margin-bottom: 0.4rem;
            font-size: 0.7rem;
        }

        .cred-key {
            color: #6c757d;
            font-weight: 600;
            min-width: 40px;
            font-size: 0.6rem;
        }

        .cred-val {
            font-family: 'Courier New', 'SF Mono', monospace;
            font-weight: 700;
            color: #1a2a3a;
            font-size: 0.7rem;
            word-break: break-all;
        }

        /* Compact parent code */
        .parent-code-value {
            font-family: 'Courier New', 'SF Mono', monospace;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: #0f5c3f;
            background: #ffffff;
            display: inline-block;
            padding: 0.15rem 0.4rem;
            margin: 0.2rem 0 0.3rem;
            border-radius: 4px;
            border: 1px dashed #85c7a8;
            text-align: center;
            width: 100%;
        }

        .parent-hint {
            font-size: 0.6rem;
            color: #1e7e34;
            line-height: 1.3;
            margin-top: 0.2rem;
        }

        .first-login-note {
            font-size: 0.6rem;
            color: #0056b3;
            margin-top: 0.4rem;
            padding-top: 0.3rem;
            border-top: 1px solid #cde3f5;
            font-style: italic;
            line-height: 1.2;
        }

        /* Compact footer */
        .card-footer {
            border-top: 1px dashed #d4dce6;
            padding: 0.4rem 1rem;
            font-size: 0.6rem;
            color: #5a6874;
            background: #fefefe;
            line-height: 1.3;
        }

        .expiry {
            font-weight: 700;
            color: #c94f1f;
        }

        .footer-link {
            font-weight: 700;
            color: #0a2b3e;
        }

        /* A4 Portrait print optimization */
        @media print {
            body {
                background: white;
                padding: 0.1in;
                margin: 0;
            }

            .no-print {
                display: none;
            }

            .container {
                gap: 0.5rem;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ddd;
                break-inside: avoid;
                page-break-inside: avoid;
                margin-bottom: 0.5rem;
            }

            .card-header {
                background: #0a2b3e !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Ensure 2 cards per page minimum, possibly 4-6 depending on content */
            .card {
                width: calc(50% - 0.3rem);
            }
        }

        /* Responsive */
        @media (max-width: 800px) {
            .card {
                width: 100%;
                min-width: auto;
            }

            body {
                padding: 0.8rem;
            }
        }

        /* For very dense screens, allow more cards */
        @media (min-width: 1200px) {
            .card {
                width: calc(33.333% - 0.55rem);
                min-width: 300px;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()">🖨️ Print All Slips</button>
    </div>

    <div class="container">
        @foreach ($logins as $login)
            <div class="card">
                <div class="card-header">
                    <span class="school-name">Kweneng International Secondary School</span>
                    <span class="slip-label">Login Credentials</span>
                </div>

                <div class="card-body">
                    <div class="student-name">{{ $login['student_name'] }}</div>

                    @isset($login['admission_no'])
                        <div class="student-meta">ADMISSION NO: {{ $login['admission_no'] }}</div>
                    @endisset

                    <div class="cred-grid">
                        {{-- STUDENT LOGIN SECTION --}}
                        <div class="cred-block student-block">
                            <div class="cred-section-label">
                                <span>👩‍🎓</span> STUDENT LOGIN
                            </div>

                            <div class="cred-row">
                                <span class="cred-key">Email</span>
                                <span class="cred-val">{{ $login['student_email'] }}</span>
                            </div>
                            <div class="cred-row">
                                <span class="cred-key">Password</span>
                                <span class="cred-val">{{ $login['student_password'] }}</span>
                            </div>
                            <div class="first-login-note">
                                ⚠️ Change password on first login
                            </div>
                        </div>

                        {{-- PARENT CODE SECTION --}}
                        <div class="cred-block parent-block">
                            <div class="cred-section-label">
                                <span>👪</span> PARENT CODE
                            </div>

                            @isset($login['parent_code'])
                                <div class="parent-code-value">{{ $login['parent_code'] }}</div>
                                <div class="parent-hint">
                                    🔑 Click "Register as a parent" to create account
                                </div>
                            @else
                                <div class="parent-hint" style="color:#999; margin-top: 0.3rem;">
                                    ⚠️ No parent code generated
                                </div>
                            @endisset
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    @isset($login['parent_code_expires_at'])
                        <span class="expiry">
                            ⏱️ Expires:
                            {{ $login['parent_code_expires_at'] instanceof \Carbon\Carbon
                                ? $login['parent_code_expires_at']->format('d M Y, H:i')
                                : $login['parent_code_expires_at'] }}
                        </span>
                        <span> — </span>
                    @endisset
                    <span>🔗 <span class="footer-link">kwenenginternational.com/login</span></span>
                    <span> — 🔒 Confidential</span>
                </div>
            </div>
        @endforeach
    </div>

</body>

</html>
