<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscountController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:Percentage,Fixed'],
            'amount' => ['required', 'numeric', 'min:0'],
            'minimum_order_value' => ['required', 'numeric', 'min:0'],
            'maximum_discount' => ['required_if:type,Percentage', 'nullable', 'numeric', 'min:0'],
            'discount_code' => ['required', 'string', 'max:50', 'unique:discount,discount_code'],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:Active,Inactive'],
            'usage_limit' => ['required', 'integer', 'min:1'],
        ], [
            'title.required' => 'Title is required.',
            'type.required' => 'Discount type is required.',
            'type.in' => 'Type must be either Percentage or Fixed.',
            'amount.required' => 'Amount is required.',
            'minimum_order_value.required' => 'Minimum order value is required.',
            'maximum_discount.required_if' => 'Maximum discount is required when type is Percentage.',
            'discount_code.required' => 'Discount code is required.',
            'discount_code.unique' => 'This discount code already exists.',
            'starts_at.after_or_equal' => 'Start date must be today or later.',
            'ends_at.after_or_equal' => 'End date must be after or equal to the start date.',
            'usage_limit.required' => 'Usage limit is required.',
        ]);

        DB::beginTransaction();

        try {

            $data = collect($request->only([
                'title',
                'type',
                'amount',
                'minimum_order_value',
                'maximum_discount',
                'discount_code',
                'starts_at',
                'ends_at',
                'status',
                'usage_limit'
            ]))->map(fn($v) => is_string($v) ? trim($v) : $v)->toArray();

            Discount::create($data);

            DB::commit();

            return response()->json(['message' => 'Discount Added Successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('An error occurred whilst saving discount: ' . $ex->getMessage());
            return response()->json([
                'message' => 'An error occurred. Please try again later'
            ], 500);
        }
    }
}
