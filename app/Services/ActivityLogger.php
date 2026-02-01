<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $productId = null
    ): ActivityLog {
        $productId = $productId ?? ($subject && $subject instanceof \App\Models\Product ? $subject->getKey() : null)
            ?? (isset($newValues['product_id']) ? (int) $newValues['product_id'] : null);

        return ActivityLog::create([
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'product_id' => $productId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => Auth::id(),
        ]);
    }
}
