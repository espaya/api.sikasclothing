<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
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
            $order = Order::with(['items', 'items.product', 'shippingAddress', 'shippingMethod'])->where('order_number', $order_number)->first();
            if (!$order) {
                return response()->json(['message' => 'Order ' . $order_number . ' Not Found'], 404);
            }
            return response()->json($order, 200);
        } catch (Exception $ex) {
            Log::info($ex->getMessage() . 'on line ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
