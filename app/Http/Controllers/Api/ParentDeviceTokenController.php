<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentDeviceToken;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;

class ParentDeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $parent = $user->parent;

        if (! $parent) {
            return response()->json([
                'message' => 'Parent profile not found.',
            ], 404);
        }

        $token = $validated['token'];
        $platform = $validated['platform'] ?? null;

        /*
         * Strict cleanup:
         * Keep only the current token for this parent user.
         * This removes stale tokens after app reinstall.
         *
         * Note:
         * This means one active token per parent user.
         * If later you want one parent to receive notifications on multiple phones,
         * we should change this logic to support multiple devices.
         */
        ParentDeviceToken::where('user_id', $user->id)
            ->where('token', '!=', $token)
            ->delete();

        ParentDeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'parent_id'    => $parent->id,
                'user_id'      => $user->id,
                'platform'     => $platform,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device token saved.',
        ]);
    }

    public function test(Request $request, FirebaseNotificationService $firebase)
    {
        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json([
                'message' => 'Parent profile not found.',
            ], 404);
        }

        $firebase->sendToParent(
            $parent->id,
            'Kweneng Test Notification',
            'Push notifications are working on this device.',
            [
                'type'   => 'test',
                'screen' => 'dashboard',
            ]
        );

        return response()->json([
            'message' => 'Test notification sent.',
        ]);
    }
}
