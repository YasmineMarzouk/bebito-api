<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('subtotal_amount', 0)
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $subtotalAmount = (int) DB::table('order_items')
                        ->where('order_id', $order->id)
                        ->sum('total_amount');

                    if ($subtotalAmount === 0) {
                        continue;
                    }

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'subtotal_amount' => $subtotalAmount,
                            'shipping_amount' => max(0, (int) $order->total_amount + (int) $order->coupon_discount_amount - $subtotalAmount),
                        ]);
                }
            });
    }

    public function down(): void {}
};
