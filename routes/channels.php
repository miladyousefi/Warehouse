<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('restaurant-calls', function ($user) {
    return $user?->can('restaurant_orders.view') || $user?->can('restaurant_orders.edit');
});

Broadcast::channel('restaurant-orders', function ($user) {
    return $user?->can('restaurant_orders.view') || $user?->can('restaurant_orders.edit');
});
