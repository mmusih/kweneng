<?php

namespace App\Services;

use App\Models\ParentDeviceToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $credentials = config('services.firebase.credentials');

        if (! $credentials) {
            throw new \RuntimeException('FIREBASE_CREDENTIALS is not set in .env');
        }

        $credentialsPath = base_path($credentials);

        $this->messaging = (new Factory)
            ->withServiceAccount($credentialsPath)
            ->createMessaging();
    }

    public function sendToParent(
        int $parentId,
        string $title,
        string $body,
        array $data = []
    ): void {
        $tokens = ParentDeviceToken::where('parent_id', $parentId)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    public function sendToManyParents(
        array $parentIds,
        string $title,
        string $body,
        array $data = []
    ): void {
        $parentIds = collect($parentIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($parentIds as $parentId) {
            $this->sendToParent((int) $parentId, $title, $body, $data);
        }
    }


    /**
     * Alias used by targeted announcement code.
     * Sends the same notification to a list of parent IDs.
     */
    public function sendToParents(
        array $parentIds,
        string $title,
        string $body,
        array $data = []
    ): void {
        $this->sendToManyParents($parentIds, $title, $body, $data);
    }

    public function sendToAllParents(
        string $title,
        string $body,
        array $data = []
    ): void {
        $tokens = ParentDeviceToken::query()
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    protected function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): void {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_merge([
                    'title' => $title,
                    'body'  => $body,
                ], $this->stringifyData($data)));

            $this->messaging->send($message);
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function stringifyData(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn($value, $key) => [$key => (string) $value])
            ->all();
    }
}
