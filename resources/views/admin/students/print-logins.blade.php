<!DOCTYPE html>
<html>

<head>
    <title>Parent Codes</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
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
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.45rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border: 1px solid #d7dde5;
            border-radius: 6px;
            overflow: hidden;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .card-header {
            background: #0a2b3e;
            color: #ffffff;
            padding: 0.35rem 0.55rem;
        }

        .school-name {
            font-size: 0.66rem;
            font-weight: 700;
        }

        .slip-label {
            font-size: 0.52rem;
            font-weight: 500;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 0.5rem 0.6rem;
        }

        .student-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0a2b3e;
            margin-bottom: 0.1rem;
        }

        .student-class {
            font-size: 0.68rem;
            color: #6c7a89;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
        }

        .parent-code-value {
            font-family: 'Courier New', 'SF Mono', monospace;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: #0f5c3f;
            background: #f0faf4;
            display: block;
            padding: 0.28rem 0.4rem;
            border-radius: 4px;
            border: 1px dashed #85c7a8;
            text-align: center;
        }

        .card-footer {
            border-top: 1px dashed #d4dce6;
            padding: 0.25rem 0.55rem;
            font-size: 0.55rem;
            color: #5a6874;
        }

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
                grid-template-columns: repeat(3, 1fr);
                gap: 0.35rem;
            }

            .card {
                border: 1px solid #ddd;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .card-header {
                background: #0a2b3e !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 800px) {
            .container {
                grid-template-columns: 1fr;
            }

            body {
                padding: 0.8rem;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()">🖨️ Print Parent Codes</button>
    </div>

    <div class="container">
        @foreach ($logins as $login)
            <div class="card">
                <div class="card-header">
                    <span class="school-name">Kweneng International Secondary School</span>
                    <span class="slip-label">Parent Code</span>
                </div>

                <div class="card-body">
                    <div class="student-name">{{ $login['student_name'] }}</div>

                    @if (!empty($login['class_name']))
                        <div class="student-class">{{ $login['class_name'] }}</div>
                    @endif

                    @isset($login['parent_code'])
                        <div class="parent-code-value">{{ $login['parent_code'] }}</div>
                    @else
                        <div class="parent-code-value" style="color:#999; letter-spacing:0;">No code generated</div>
                    @endisset
                </div>

                <div class="card-footer">
                    @isset($login['parent_code_expires_at'])
                        Expires:
                        {{ $login['parent_code_expires_at'] instanceof \Carbon\Carbon
                            ? $login['parent_code_expires_at']->format('d M Y, H:i')
                            : $login['parent_code_expires_at'] }}
                    @endisset
                </div>
            </div>
        @endforeach
    </div>

</body>

</html>
