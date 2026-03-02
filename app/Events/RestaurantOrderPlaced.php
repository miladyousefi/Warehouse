<?php

namespace App\Events;

use App\Models\RestaurantOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestaurantOrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RestaurantOrder $order)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('restaurant-orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }

    public function broadcastWith(): array
    {
        $order = $this->order->load('table');

        return [
            'id' => $order->id,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'subtotal' => (float) $order->subtotal,
            'table' => [
                'id' => $order->table?->id,
                'name' => $order->table?->name,
                'table_number' => $order->table?->table_number,
            ],
        ];
    }
}
