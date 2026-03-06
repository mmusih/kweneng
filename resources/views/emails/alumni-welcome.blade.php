<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Kweneng International Alumni Network</title>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2563eb;">Welcome to Kweneng International Alumni Network!</h1>
        </div>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h2 style="color: #2563eb;">Dear {{ $alumni->name }},</h2>
            
            <p>Thank you for joining the Kweneng International Alumni Network! We're thrilled to have you as part of our growing community.</p>
            
            <p>Your connection to Kweneng International continues even after graduation. As an alumnus, you're now part of a global network of accomplished professionals who share your educational background and values.</p>
            
            <h3 style="color: #2563eb; margin-top: 20px;">What You Can Expect:</h3>
            <ul style="padding-left: 20px;">
                <li>Exclusive alumni events and reunions</li>
                <li>Networking opportunities with fellow graduates</li>
                <li>Career advancement resources and job postings</li>
                <li>Mentorship programs for current students</li>
                <li>Quarterly newsletters with alumni achievements</li>
            </ul>
            
            <p>We encourage you to stay connected and engaged with our community. Whether you're looking to reconnect with old friends, advance your career, or give back to current students, our alumni network is here to support you.</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/') }}" style="background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                    Visit Our Website
                </a>
            </div>
            
            <p>If you have any questions or need assistance, please don't hesitate to reach out to us at alumni@kwenenginternational.com.</p>
            
            <p>Once again, welcome to the Kweneng International Alumni Network!</p>
            
            <p>Warm regards,<br>The Kweneng International Team</p>
        </div>
        
        <div style="text-align: center; font-size: 12px; color: #666; margin-top: 30px;">
            <p>This email was sent to {{ $alumni->email }}</p>
            <p>Kweneng International Secondary School<br>
            P O Box 586, Molepolole<br>
            alumni@kwenenginternational.com</p>
        </div>
    </div>
</body>
</html>
