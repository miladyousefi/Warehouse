<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill product_id from subject when subject_type is Product
        DB::table('activity_logs')
            ->where('subject_type', 'App\Models\Product')
            ->whereNotNull('subject_id')
            ->whereNull('product_id')
            ->update([
                'product_id' => DB::raw('subject_id'),
            ]);

        // Backfill product_id from new_values JSON for stock_* actions
        $logs = DB::table('activity_logs')
            ->whereIn('action', ['stock_in', 'stock_out', 'stock_transfer', 'stock_adjustment'])
            ->whereNotNull('new_values')
            ->whereNull('product_id')
            ->get();

        foreach ($logs as $log) {
            $newValues = json_decode($log->new_values, true);
            if (isset($newValues['product_id'])) {
                DB::table('activity_logs')
                    ->where('id', $log->id)
                    ->update(['product_id' => (int) $newValues['product_id']]);
            }
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
