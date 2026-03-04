<?php

namespace App\Events;

use App\Models\RestaurantOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestaurantOrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RestaurantOrder $order, public ?string $previousStatus = null)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('restaurant-orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        $order = $this->order->load('table');

        return [
            'id' => $order->id,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'previous_status' => $this->previousStatus,
            'payment_status' => $order->payment_status,
            'cancel_reason' => $order->cancel_reason,
            'subtotal' => (float) $order->subtotal,
            'updated_at' => optional($order->updated_at)?->toDateTimeString(),
            'table' => [
                'id' => $order->table?->id,
                'name' => $order->table?->name,
                'table_number' => $order->table?->table_number,
            ],
        ];
    }
}
