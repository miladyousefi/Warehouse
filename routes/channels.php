<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('restaurant-calls', function ($user) {
    return $user?->can('restaurant_orders.view')
        || $user?->can('restaurant_orders.edit')
        || $user?->can('restaurant_orders.calls.handle');
});

Broadcast::channel('restaurant-orders', function ($user) {
    return $user?->can('restaurant_orders.view')
        || $user?->can('restaurant_orders.edit')
        || $user?->can('restaurant_orders.take_order')
        || $user?->can('restaurant_orders.monitor.confirm_cancel');
});
