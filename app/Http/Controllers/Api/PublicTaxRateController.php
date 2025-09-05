<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicTaxRateController extends Controller
{
    /**
     * Get tax rate for a specific location
     */
    public function show(Request $request, string $countryCode): JsonResponse
    {
        $request->validate([
            'state_code' => 'nullable|string|max:10',
        ]);

        $stateCode = $request->query('state_code');

        // Cache per country+state
        $cacheKey = "tax_rate_{$countryCode}_" . ($stateCode ?? 'all');

        $taxRate = Cache::remember($cacheKey, 3600, function () use ($countryCode, $stateCode) {
            return TaxRate::getForLocation($countryCode, $stateCode);
        });

        if (!$taxRate) {
            return response()->json([
                'message' => 'Tax rate not found for the specified location',
                'country_code' => strtoupper($countryCode),
                'state_code' => $stateCode ? strtoupper($stateCode) : null,
            ], 404);
        }

        return response()->json([
            'data' => [
                'country' => $taxRate->country,
                'country_code' => $taxRate->country_code,
                'state_code' => $taxRate->state_code,
                'tax_name' => $taxRate->tax_name,
                'tax_type' => $taxRate->tax_type,
                'rate' => $taxRate->rate,
                'formatted_rate' => $taxRate->formatted_rate,
                'effective_date' => $taxRate->effective_date->format('Y-m-d'),
                'description' => $taxRate->description,
            ]
        ]);
    }

    /**
     * Calculate tax amount for a given subtotal
     */
    public function calculate(Request $request, string $countryCode): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'state_code' => 'nullable|string|max:10',
        ]);

        $amount = $request->amount;
        $stateCode = $request->query('state_code');

        // Cache key includes country+state
        $cacheKey = "tax_rate_{$countryCode}_" . ($stateCode ?? 'all');

        $taxRate = Cache::remember($cacheKey, 3600, function () use ($countryCode, $stateCode) {
            return TaxRate::getForLocation($countryCode, $stateCode);
        });

        if (!$taxRate) {
            return response()->json([
                'message' => 'Tax rate not found for the specified location',
                'country_code' => strtoupper($countryCode),
                'state_code' => $stateCode ? strtoupper($stateCode) : null,
            ], 404);
        }

        $taxAmount = round($amount * ($taxRate->rate / 100), 2);
        $totalAmount = $amount + $taxAmount;

        return response()->json([
            'data' => [
                'subtotal' => number_format($amount, 2),
                'tax_rate' => $taxRate->rate,
                'tax_amount' => number_format($taxAmount, 2),
                'total_amount' => number_format($totalAmount, 2),
                'tax_details' => [
                    'country' => $taxRate->country,
                    'country_code' => $taxRate->country_code,
                    'state_code' => $taxRate->state_code,
                    'tax_name' => $taxRate->tax_name,
                    'tax_type' => $taxRate->tax_type,
                ]
            ]
        ]);
    }

    /**
     * Get all active tax rates (for dropdown/selection purposes)
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->filled('search') ? $request->search : null;

        // Cache key depends on search
        $cacheKey = "tax_rates_list_" . ($search ?? 'all');

        $taxRates = Cache::remember($cacheKey, 3600, function () use ($search) {
            $query = TaxRate::active()
                ->select(['country', 'country_code', 'state_code', 'tax_name', 'tax_type', 'rate'])
                ->orderBy('country')
                ->orderBy('state_code');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('country', 'like', "%{$search}%")
                        ->orWhere('country_code', 'like', "%{$search}%");
                });
            }

            return $query->get();
        });

        return response()->json([
            'data' => $taxRates
        ]);
    }
}
