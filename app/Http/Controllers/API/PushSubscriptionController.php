<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __construct(private WebPushService $webPushService) {}

    public function config(): JsonResponse
    {
        return response()->json([
            'supported' => $this->webPushService->isConfigured(),
            'public_key' => $this->webPushService->publicKey(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $request->string('contentEncoding')->value() ?: 'aesgcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'success' => true,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
        ]);

        PushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        $sent = $this->webPushService->sendToUser($request->user(), [
            'title' => 'Notifications are ready',
            'body' => 'You will now receive mobile notifications when new updates arrive.',
            'url' => '/ai/chat',
            'tag' => 'push-test',
        ]);

        return response()->json([
            'success' => $sent,
        ]);
    }
}
