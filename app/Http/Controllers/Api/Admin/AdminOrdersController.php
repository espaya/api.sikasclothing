<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'status' => ['nullable', 'string', 'in:unshipped,processing,packed,shipped,out_for_delivery,delivered,returned,cancelled'],
            'payment_status' => ['nullable', 'string', 'in:unpaid,pending,processing,paid,partially_paid,refunded,failed,cancelled'],
            'admin_notes' => ['nullable', 'string'],
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

            return response()->json(['message' => 'Order updated successfully'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . ' on line: ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
