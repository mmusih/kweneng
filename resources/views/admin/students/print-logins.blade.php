<!DOCTYPE html>
<html>
<head>
    <title>Student Login Slips</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
        }

        .card {
            width: 48%;
            border: 1px solid #000;
            padding: 12px;
            margin: 1%;
            box-sizing: border-box;
        }

        .title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .line {
            margin: 4px 0;
        }

        .note {
            margin-top: 10px;
            font-size: 12px;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>
<body>

<button onclick="window.print()">Print</button>

<div class="container">
    @foreach($logins as $login)
        <div class="card">
            <div class="title">Kweneng International Secondary School</div>

            <div class="line"><strong>Portal:</strong> kwenenginternational.com/login</div>
            <div class="line"><strong>Name:</strong> {{ $login['name'] }}</div>
            <div class="line"><strong>Email:</strong> {{ $login['email'] }}</div>
            <div class="line"><strong>Password:</strong> {{ $login['password'] }}</div>

            <div class="note">
                You will be required to change your password on first login.
            </div>
        </div>
    @endforeach
</div>

</body>
</html>