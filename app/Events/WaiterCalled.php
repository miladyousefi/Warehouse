<?php

namespace App\Events;

use App\Models\RestaurantTableCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaiterCalled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RestaurantTableCall $call)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('restaurant-calls')];
    }

    public function broadcastAs(): string
    {
        return 'waiter.called';
    }

    public function broadcastWith(): array
    {
        $call = $this->call->load('table');

        return [
            'id' => $call->id,
            'status' => $call->status,
            'note' => $call->note,
            'requested_at' => optional($call->requested_at)->toIso8601String(),
            'table' => [
                'id' => $call->table?->id,
                'name' => $call->table?->name,
                'table_number' => $call->table?->table_number,
            ],
        ];
    }
}
