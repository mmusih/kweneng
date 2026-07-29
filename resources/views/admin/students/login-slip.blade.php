{{--
    resources/views/admin/students/login-slip.blade.php

    Printable login slip for a student.
    $slip  = array from GenerateLoginSlip::for()
    $student = Student model (with user loaded)

    To print: admin visits /admin/students/{id}/slip
    The page auto-triggers the browser print dialog.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Code — {{ $slip['student_name'] }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #f5f5f5;
            padding: 2rem;
        }

        .slip-wrapper {
            max-width: 520px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Individual slip card */
        .slip {
            background: #fff;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .slip-header {
            background: #1e3a5f;
            color: #fff;
            padding: 0.9rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .slip-header .school-name {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .slip-header .slip-title {
            font-size: 11px;
            opacity: 0.75;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .slip-body {
            padding: 1.25rem;
        }

        .student-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 0.3rem;
        }

        .admission-no {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .parent-code {
            font-family: 'Courier New', monospace;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: #065f46;
            margin-top: 0.8rem;
            background: #f0fdf4;
            border: 1px dashed #86efac;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            text-align: center;
        }

        .slip-footer {
            border-top: 1px dashed #e5e7eb;
            padding: 0.7rem 1.25rem;
            font-size: 10.5px;
            color: #6b7280;
            line-height: 1.5;
        }

        .expiry-note {
            font-weight: 600;
            color: #b45309;
        }

        /* Screen-only controls */
        .no-print {
            max-width: 680px;
            margin: 0 auto 1.5rem;
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .btn {
            padding: 0.5rem 1.1rem;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #1e3a5f;
            color: #fff;
        }

        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-ghost {
            background: #f3f4f6;
            color: #374151;
        }

        /* Print styles */
        @media print {
            body {
                background: #fff;
                padding: 0.5cm;
            }

            .no-print {
                display: none !important;
            }

            .slip {
                border: 1px solid #ccc;
                box-shadow: none;
            }

            .slip-wrapper {
                gap: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button class="btn btn-primary" onclick="window.print()">&#128438; Print Slip</button>
        <a href="{{ route('admin.students.slip', ['student' => $student, 'regenerate' => 1]) }}" class="btn btn-danger"
            onclick="return confirm('This will invalidate the current codes and generate new ones. Continue?')">
            &#8635; Regenerate Codes
        </a>
        <a href="{{ url()->previous() }}" class="btn btn-ghost">&larr; Back</a>
    </div>

    <div class="slip-wrapper">
        <div class="slip">
            <div class="slip-header">
                <div>
                    <div class="school-name">{{ config('app.name', 'School Name') }}</div>
                </div>
                <div class="slip-title">Parent Code</div>
            </div>

            <div class="slip-body">
                <div class="student-name">{{ $slip['student_name'] }}</div>
                <div class="admission-no">{{ $slip['identity_display'] ?? ('Legacy Ref: ' . $slip['admission_no']) }}</div>

                <div class="parent-code">{{ $slip['parent_code'] }}</div>
            </div>

            <div class="slip-footer">
                <span class="expiry-note">Parent code expires:
                    {{ $slip['parent_code_expires_at']->format('d M Y, H:i') }}</span>
            </div>
        </div>
    </div>

    <script>
        // Auto-open print dialog when the page loads (admin can cancel if they want to review first)
        window.addEventListener('load', function() {
            // Small delay so styles are fully applied before print dialog
            setTimeout(function() {
                window.print();
            }, 400);
        });
    </script>
</body>

</html>
