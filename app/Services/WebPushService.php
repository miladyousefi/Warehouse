<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class WebPushService
{
    public function publicKey(): ?string
    {
        return config('services.webpush.public_key');
    }

    public function isConfigured(): bool
    {
        return filled(config('services.webpush.public_key'))
            && filled(config('services.webpush.private_key'))
            && filled(config('services.webpush.subject'));
    }

    public function sendNotificationModel(Notification $notification): bool
    {
        $user = $notification->user;

        if (! $user) {
            return false;
        }

        return $this->sendToUser($user, [
            'title' => $notification->title,
            'body' => $notification->message,
            'url' => '/notifications',
            'tag' => 'notification-' . $notification->id,
        ]);
    }

    public function sendToUser(User $user, array $payload): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $sent = false;

        foreach ($subscriptions as $subscription) {
            if ($this->sendToSubscription($subscription, $payload)) {
                $sent = true;
            }
        }

        return $sent;
    }

    private function sendToSubscription(PushSubscription $subscription, array $payload): bool
    {
        $message = json_encode([
            'subscription' => [
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->public_key,
                    'auth' => $subscription->auth_token,
                ],
                'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
            ],
            'payload' => $payload,
            'vapid' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ], JSON_THROW_ON_ERROR);

        $script = base_path('scripts/send-web-push.mjs');
        $process = new Process(['node', $script], base_path());
        $process->setTimeout(20);
        $process->setInput($message);
        $process->run();

        if ($process->isSuccessful()) {
            $subscription->forceFill(['last_used_at' => now()])->save();
            return true;
        }

        $output = $process->getErrorOutput() ?: $process->getOutput();

        Log::warning('Web push send failed', [
            'subscription_id' => $subscription->id,
            'error' => trim($output),
        ]);

        if (str_contains($output, '410') || str_contains($output, '404')) {
            $subscription->delete();
        }

        return false;
    }
}
