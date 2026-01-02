<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Order_Status\CancelledOrderMail;
use App\Mail\Order_Status\CompletedOrderMail;
use App\Mail\Order_Status\ConfirmedOrderMail;
use App\Mail\Order_Status\DelayedOrderMail;
use App\Mail\Order_Status\DeliveredOrderMail;
use App\Mail\Order_Status\FailedOrderMail;
use App\Mail\Order_Status\OnHoldOrderMail;
use App\Mail\Order_Status\OutForDeliveryOrderMail;
use App\Mail\Order_Status\PackedOrderMail;
use App\Mail\Order_Status\PendingOrderMail;
use App\Mail\Order_Status\ProcessingOrderMail;
use App\Mail\Order_Status\RefundedOrderMail;
use App\Mail\Order_Status\ReturnedOrderMail;
use App\Mail\Order_Status\ShippedOrderMail;
use App\Mail\Payment\CancelledMail;
use App\Mail\Payment\FailedMail;
use App\Mail\Payment\PaidMail;
use App\Mail\Payment\PendingMail;
use App\Mail\Payment\ProcessingMail;
use App\Mail\Payment\RefundedMail;
use App\Mail\Payment\UnpaidMail;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminOrdersController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::with(['items'])->orderBy('id', 'DESC')->paginate(10);
            if (!$orders) {
                return response()->json(['message' => 'No order found!'], 404);
            }
            return response()->json($orders, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexprected error occurred'], 500);
        }
    }

    public function view($order_number)
    {
        try {
            $order = Order::with(['items', 'items.product', 'shippingAddress', 'shippingMethod'])
                ->where('order_number', $order_number)
                ->first();

            if (!$order) {
                return response()->json(['message' => 'Order ' . $order_number . ' Not Found'], 404);
            }
            return response()->json($order, 200);
        } catch (Exception $ex) {
            Log::info($ex->getMessage() . 'on line ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function updateOrderOptions(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                'string',
                'in:pending,confirmed,processing,on_hold,packed,shipped,out_for_delivery,delivered,completed,delayed,returned,refunded,cancelled,failed'
            ],

            'payment_status' => [
                'nullable',
                'string',
                'in:unpaid,pending,processing,paid,refunded,failed,cancelled'
            ],

            'admin_notes' => [
                'nullable',
                'string'
            ],

        ], [
            'status.string' => 'Invalid status',
            'status.in' => 'Unknown status option',
            'payment_status.string' => 'Invalid status',
            'payment_status.in' => 'Unknown status option',
            'admin_notes.string' => 'Invalid inputs',
        ]);

        DB::beginTransaction();

        try {

            $order = Order::findOrFail($id);

            $order->fill($validated);

            if (! $order->isDirty()) {
                DB::rollBack();
                return response()->json(['message' => 'No changes detected'], 200);
            }

            $order->save();
            DB::commit();

            $user = User::findOrFail($order->user_id);

            /**
             * -----------------------------------
             * SEND PAYMENT STATUS EMAIL (ONLY IF CHANGED)
             * -----------------------------------
             */
            if ($order->wasChanged('payment_status')) {
                match ($order->payment_status) {
                    'paid' => Mail::to($user->email)->send(new PaidMail($order, $user)),
                    'failed' => Mail::to($user->email)->send(new FailedMail($order, $user)),
                    'refunded' => Mail::to($user->email)->send(new RefundedMail($order, $user)),
                    'cancelled' => Mail::to($user->email)->send(new CancelledMail($order, $user)),
                    'pending' => Mail::to($user->email)->send(new PendingMail($order, $user)),
                    'processing' => Mail::to($user->email)->send(new ProcessingMail($order, $user)),
                    'unpaid' => Mail::to($user->email)->send(new UnpaidMail($order, $user)),
                };
            }

            /**
             * -----------------------------------
             * SEND ORDER STATUS EMAIL (ONLY IF CHANGED)
             * -----------------------------------
             */
            if ($order->wasChanged('status')) {
                match ($order->status) {
                    'pending' => Mail::to($user->email)->send(new PendingOrderMail($order, $user)),
                    'confirmed' => Mail::to($user->email)->send(new ConfirmedOrderMail($order, $user)),
                    'processing' => Mail::to($user->email)->send(new ProcessingOrderMail($order, $user)),
                    'on_hold' => Mail::to($user->email)->send(new OnHoldOrderMail($order, $user)),
                    'packed' => Mail::to($user->email)->send(new PackedOrderMail($order, $user)),
                    'shipped' => Mail::to($user->email)->send(new ShippedOrderMail($order, $user)),
                    'out_for_delivery' => Mail::to($user->email)->send(new OutForDeliveryOrderMail($order, $user)),
                    'delivered' => Mail::to($user->email)->send(new DeliveredOrderMail($order, $user)),
                    'completed' => Mail::to($user->email)->send(new CompletedOrderMail($order, $user)),
                    'delayed' => Mail::to($user->email)->send(new DelayedOrderMail($order, $user)),
                    'returned' => Mail::to($user->email)->send(new ReturnedOrderMail($order, $user)),
                    'refunded' => Mail::to($user->email)->send(new RefundedOrderMail($order, $user)),
                    'cancelled' => Mail::to($user->email)->send(new CancelledOrderMail($order, $user)),
                    'failed' => Mail::to($user->email)->send(new FailedOrderMail($order, $user)),
                };
            }

            return response()->json(['message' => 'Order updated successfully'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . ' on line ' . $ex->getLine());

            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
