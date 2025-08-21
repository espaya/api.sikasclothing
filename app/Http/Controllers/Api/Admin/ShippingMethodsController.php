<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShippingMethodsController extends Controller
{
    public function index()
    {
        return response()->json(ShippingMethod::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            "shipping_location" => ['required', 'string'],
            "shipping_cost" => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            "shipping_method" => ['required', 'string'],
            "status" => ['required', 'string', 'in:active,inactive'],
            "estimated_delivery_time" => ['required', 'string'],
            "weight_limit" => ['nullable', 'string'],
            "notes" => ['nullable', 'string']
        ], [
            // shipping_location
            'shipping_location.required' => 'Shipping location is required.',
            'shipping_location.string'   => 'Shipping location must be a valid string.',

            // shipping_cost
            'shipping_cost.required' => 'Shipping cost is required.',
            'shipping_cost.regex'    => 'Shipping cost must be a valid number (e.g. 50, 30.5, 100.00).',

            // shipping_method
            'shipping_method.required' => 'Shipping method is required.',
            'shipping_method.string'   => 'Shipping method must be a valid string.',

            // status
            'status.required' => 'Status is required.',
            'status.string'   => 'Status must be a valid string.',
            'status.in'       => 'Status must be either active or inactive.',

            // estimated_delivery_time
            'estimated_delivery_time.required' => 'Estimated delivery time is required.',
            'estimated_delivery_time.string'   => 'Estimated delivery time must be a valid string.',

            // weight_limit
            'weight_limit.string' => 'Weight limit must be a valid string.',

            // notes
            'notes.string' => 'Notes must be a valid string.'
        ]);

        DB::beginTransaction();

        try {
            ShippingMethod::create([
                "shipping_location" => $request->shipping_location,
                "shipping_cost" => $request->shipping_cost,
                "shipping_method" => $request->shipping_method,
                "status" => $request->status,
                "estimated_delivery_time" => $request->estimated_delivery_time,
                "weight_limit" => $request->weight_limit,
                "notes" => $request->note,
            ]);

            DB::commit();

            return response()->json(['message' => 'Shipping method added successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An error occurred, try again later'], 500);
        }
    }
}
