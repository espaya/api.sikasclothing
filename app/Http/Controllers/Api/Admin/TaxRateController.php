<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxRateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TaxRate::with(['createdBy:id,name', 'updatedBy:id,name']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('country', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', "%{$search}%")
                    ->orWhere('tax_name', 'like', "%{$search}%")
                    ->orWhere('tax_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('country_code')) {
            $query->where('country_code', strtoupper($request->country_code));
        }

        $taxRates = $query->orderBy('country')
            ->orderBy('state_code')
            ->paginate($request->get('per_page', 15));

        return response()->json($taxRates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => 'required|string|max:100',
            'country_code' => [
                'required',
                'string',
                'size:2',
                Rule::unique('tax_rates')->where(function ($query) use ($request) {
                    return $query->where('state_code', $request->state_code)
                        ->where('effective_date', $request->effective_date ?? now()->format('Y-m-d'));
                }),
            ],
            'state_code' => 'nullable|string|max:10',
            'tax_name' => 'required|string|max:100',
            'tax_type' => 'required|string|max:50',
            'rate' => 'required|numeric|min:0|max:100',
            'effective_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:effective_date',
            'status' => 'required|in:active,inactive,scheduled',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $validated['country_code'] = strtoupper($validated['country_code']);
            if (isset($validated['state_code'])) {
                $validated['state_code'] = strtoupper($validated['state_code']);
            }

            $taxRate = TaxRate::create($validated);

            Log::info('Tax rate created', [
                'tax_rate_id' => $taxRate->id,
                'country_code' => $taxRate->country_code,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Tax rate created successfully',
                'data' => $taxRate->load(['createdBy:id,name', 'updatedBy:id,name'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create tax rate', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return response()->json([
                'message' => 'Failed to create tax rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(TaxRate $taxRate): JsonResponse
    {
        return response()->json([
            'data' => $taxRate->load(['createdBy:id,name', 'updatedBy:id,name', 'histories.changedBy:id,name'])
        ]);
    }

    public function update(Request $request, TaxRate $taxRate): JsonResponse
    {
        $validated = $request->validate([
            'country' => 'sometimes|string|max:100',
            'tax_name' => 'sometimes|string|max:100',
            'tax_type' => 'sometimes|string|max:50',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'effective_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:effective_date',
            'status' => 'sometimes|in:active,inactive,scheduled',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $taxRate->update($validated);

            Log::info('Tax rate updated', [
                'tax_rate_id' => $taxRate->id,
                'changes' => $validated,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Tax rate updated successfully',
                'data' => $taxRate->fresh(['createdBy:id,name', 'updatedBy:id,name'])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update tax rate', [
                'tax_rate_id' => $taxRate->id,
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return response()->json([
                'message' => 'Failed to update tax rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        try {
            // Instead of deleting, mark as inactive
            $taxRate->update([
                'status' => 'inactive',
                'end_date' => now(),
            ]);

            Log::info('Tax rate deactivated', [
                'tax_rate_id' => $taxRate->id,
                'deactivated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Tax rate deactivated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to deactivate tax rate', [
                'tax_rate_id' => $taxRate->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to deactivate tax rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        try {
            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));
            $header = array_shift($csvData);

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                try {
                    if (count($row) !== count($header)) {
                        throw new \Exception('Column count mismatch');
                    }

                    $data = array_combine($header, $row);
                    $data['country_code'] = strtoupper($data['country_code']);

                    if (isset($data['state_code'])) {
                        $data['state_code'] = strtoupper($data['state_code']);
                    }

                    TaxRate::create($data);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            Log::info('Bulk tax rate import completed', [
                'imported' => $imported,
                'errors' => count($errors),
                'imported_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => "Successfully imported {$imported} tax rates",
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk import failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Bulk import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(): JsonResponse
    {
        try {
            $taxRates = TaxRate::select([
                'country',
                'country_code',
                'state_code',
                'tax_name',
                'tax_type',
                'rate',
                'effective_date',
                'end_date',
                'status'
            ])->orderBy('country')->get();

            $csvData = [];
            $csvData[] = [
                'country',
                'country_code',
                'state_code',
                'tax_name',
                'tax_type',
                'rate',
                'effective_date',
                'end_date',
                'status'
            ];

            foreach ($taxRates as $rate) {
                $csvData[] = [
                    $rate->country,
                    $rate->country_code,
                    $rate->state_code,
                    $rate->tax_name,
                    $rate->tax_type,
                    $rate->rate,
                    $rate->effective_date?->format('Y-m-d'),
                    $rate->end_date?->format('Y-m-d'),
                    $rate->status,
                ];
            }

            return response()->json([
                'data' => $csvData,
                'filename' => 'tax_rates_' . now()->format('Y_m_d_H_i_s') . '.csv'
            ]);
        } catch (\Exception $e) {
            Log::error('Export failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
