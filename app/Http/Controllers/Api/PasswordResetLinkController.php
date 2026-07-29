<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'If an account matches that email address, a password reset link has been sent.',
        ]);
    }
}
