<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reviews;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewsController extends Controller
{

    public function store(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'rating' => ['required'],
            'remember' => ['nullable', 'in:1'],
            'review' => ['nullable', 'string']
        ], [
            'name.required' => 'This field is required',
            'name.string' => 'Invalid inputs',
            'email.required' => 'This field is required',
            'email.email' => 'Invalid email address',
            'remember.in' => 'Invalid input',
            'review.string' => 'Invalid inputs'
        ]);

        DB::beginTransaction();

        if (!$id) {
            return response()->json([
                'message' => 'Product Not Found!'
            ], 404);
        }

        try {
            Reviews::create([
                'name' => trim($request->name),
                'email' => trim($request->email),
                'rating' => trim($request->rating),
                'remember' => trim($request->remember),
                'review' => trim($request->review),
                'product_id' => trim($id),
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Your review submitted successfully'
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json([
                'message' => 'An error occurred whilst saving your review. Try again later'
            ], 500);
        }
    }
}
