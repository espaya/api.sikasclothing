<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'guest_email',
        'shipping_address_id',
        'billing_address_id',
        'status',
        'payment_status',
        'payment_method',
        'total_amount',
        'subtotal_amount',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'shipping_method',
        'tracking_number',
        'currency',
        'notes',
        'admin_notes'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Generate tracking number
            do {
                $order->tracking_number = 'TRK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            } while (Order::where('tracking_number', $order->tracking_number)->exists());

            // Generate order number
            $order->order_number = self::generateOrderNumber();
        });
    }

    /**
     * Generate a unique, scalable order number
     * Format: ORD-YYYYMMDD-XXXXXX (where XXXXXX is an incremental number)
     */
    protected static function generateOrderNumber()
    {
        $datePrefix = now()->format('Ymd');
        $cacheKey = "order_number_counter_{$datePrefix}";

        // Use atomic increment with cache for better performance
        $dailyCounter = Cache::remember($cacheKey, now()->addDay(), function () use ($datePrefix) {
            // Get the highest order number for today
            $lastOrder = Order::where('order_number', 'like', "ORD-{$datePrefix}-%")
                ->orderBy('order_number', 'desc')
                ->first();

            if ($lastOrder && preg_match('/ORD-\d+-(\d+)/', $lastOrder->order_number, $matches)) {
                return (int) $matches[1];
            }

            return 0;
        });

        // Atomically increment the counter
        $counter = Cache::increment($cacheKey);

        // Format: ORD-YYYYMMDD-000001
        $orderNumber = sprintf("ORD-%s-%06d", $datePrefix, $counter);

        // Double-check uniqueness (rare case of cache reset)
        $retryCount = 0;
        while (Order::where('order_number', $orderNumber)->exists() && $retryCount < 5) {
            $counter = Cache::increment($cacheKey);
            $orderNumber = sprintf("ORD-%s-%06d", $datePrefix, $counter);
            $retryCount++;
        }

        // Final fallback - use timestamp with random suffix if still not unique
        if (Order::where('order_number', $orderNumber)->exists()) {
            do {
                $orderNumber = 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
            } while (Order::where('order_number', $orderNumber)->exists());
        }

        return $orderNumber;
    }


    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(BillingAddress::class, 'billing_address_id');
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method', 'id');
        // assuming `shipping_method` stores something like "fedex", "dhl"
    }
}
