<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;


class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'shipping_address_id' => ['required', 'integer'],
            'billing_address_id' => ['required', 'integer'],
            'status' => ['required', 'string'],
            'payment_status' => ['required', 'string'],
            'payment_method' => ['required', 'string'],
            'subtotal_amount' => ['required', 'numeric'],
            'shipping_amount' => ['required', 'numeric'],
            'tax_amount' => ['required', 'numeric'],
            'total_amount' => ['required', 'numeric'],
            'discount_amount' => ['required', 'numeric'],
            'shipping_method' => ['required', 'integer'],
            'terms_conditions' => ['required', 'boolean'],
            'currency' => ['required', 'string'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0']
        ], [
            'user_id.required' => 'User ID is required',
            'user_id.integer' => 'Invalid user identity',
            'shipping_address_id.required' => 'Shipping address is required',
            'shipping_address_id.integer' => 'Invalid shipping address',
            'billing_address_id.required' => 'Billing address is required',
            'billing_address_id.integer' => 'Invalid billing address',
            'status.required' => 'Order status is required',
            'status.string' => 'Invalid order status',
            'payment_status.required' => 'Payment status is required',
            'payment_status.string' => 'Invalid payment status',
            'payment_method.required' => 'Payment method is required',
            'payment_method.string' => 'Invalid payment method',
            'subtotal_amount.required' => 'Subtotal amount is required',
            'subtotal_amount.numeric' => 'Subtotal must be a valid number',
            'shipping_amount.required' => 'Shipping amount is required',
            'shipping_amount.numeric' => 'Shipping amount must be a valid number',
            'tax_amount.required' => 'Tax amount is required',
            'tax_amount.numeric' => 'Tax amount must be a valid number',
            'total_amount.required' => 'Total amount is required',
            'total_amount.numeric' => 'Total amount must be a valid number',
            'discount_amount.required' => 'Discount amount is required',
            'discount_amount.numeric' => 'Discount amount must be a valid number',
            'shipping_method.required' => 'Shipping method is required',
            'shipping_method.integer' => 'Invalid shipping method',
            'terms_conditions.required' => 'You must accept the terms and conditions',
            'terms_conditions.boolean' => 'Invalid terms acceptance',
            'currency.required' => 'Currency is required',
            'currency.string' => 'Invalid currency',
            'notes.string' => 'Notes must be text',
            'items.required' => 'Order items are required',
            'items.array' => 'Invalid order items format',
            'items.*.product_id.required' => 'Product ID is required for all items',
            'items.*.product_id.integer' => 'Invalid product ID',
            'items.*.quantity.required' => 'Quantity is required for all items',
            'items.*.quantity.integer' => 'Quantity must be a whole number',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'items.*.price.required' => 'Price is required for all items',
            'items.*.price.numeric' => 'Price must be a valid number',
            'items.*.price.min' => 'Price cannot be negative'
        ]);

        DB::beginTransaction();

        $user = Auth::user();

        try {

            $payLoad = [
                'user_id' => $request->user_id,
                'shipping_address_id' => $request->shipping_address_id,
                'billing_address_id' => $request->billing_address_id,
                'status' => $request->status,
                'payment_status' => $request->payment_status,
                'payment_method' => $request->payment_method,
                'subtotal_amount' => $request->subtotal_amount,
                'shipping_amount' => $request->shipping_amount,
                'tax_amount' => $request->tax_amount,
                'total_amount' => $request->total_amount,
                'discount_amount' => $request->discount_amount,
                'shipping_method' => $request->shipping_method,
                'terms_conditions' => $request->terms_conditions,
                'currency' => $request->currency,
                'notes' => $request->notes,
            ];

            // Create the order - tracking_number will be auto-generated
            $order = Order::create($payLoad);

            // Create order items and update product quantities
            foreach ($request->items as $item) {
                // Create the order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                ]);

                // Subtract from the product quantity
                $product = Products::find($item['product_id']);

                if ($product) {
                    // Check if there's enough stock
                    if ($product->stock_quantity < $item['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Insufficient stock for product: ' . $product->name
                        ], 422);
                    }

                    // Update the product quantity
                    $product->decrement('stock_quantity', $item['quantity']);

                    // Optional: Track total sales
                    $product->increment('total_sold', $item['quantity']);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Product not found with ID: ' . $item['product_id']
                    ], 422);
                }
            }

            // Process payment based on method
            if ($request->payment_method == 'paypal') {
                // process paypal payment here
                // You might want to integrate with PayPal API
                // For now, we'll just mark it as paid if PayPal is selected
                $order->update(['payment_status' => 'paid']);
            }

            // Clear the user's cart after successful order
            if (Auth::check()) {
                $user->cartItems()->delete();
            } else {
                session()->forget('cart');
            }

            // send email to user
            $orderItems = OrderItem::with('product')->where('order_id', $order->id)->get();
            Mail::to($user->email)->send(new OrderMail($user, $order, $orderItems));

            DB::commit();

            // Clear cached orders pages
            for ($page = 1; $page <= 10; $page++) { // adjust to your max pages
                Cache::forget("orders_page_{$page}");
            }


            return response()->json([
                'order_id' => $order->order_number,
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function getOrderSingle($id)
    {
        try {
            $order = Order::with([
                'items.product',
                'shippingMethod'
            ])->where('order_number', $id)->first();

            if (!$order) {
                return response()->json(['message' => 'No orders were found!'], 404);
            }

            return response()->json($order, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function allOrders()
    {
        try {
            $page = request()->get('page', 1); // get current page number
            $cacheKey = "orders_page_{$page}";

            $orders = Cache::remember($cacheKey, 60, function () {
                return Order::orderBy('id', 'DESC')->paginate(10);
            });

            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No orders found!'], 404);
            }

            return response()->json($orders, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
