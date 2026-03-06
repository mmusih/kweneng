<?php
require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = User::create([
    'name' => 'Administrator',
    'email' => 'admin@school.local',
    'password' => Hash::make('password'),
    'role' => 'admin',
    'status' => 'active',
]);

echo "Admin user created!\n";
echo "Email: admin@school.local\n";
echo "Password: password\n";
