<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerWishlistController extends Controller
{
    public function index()
    {
        try {
            if (!Auth::check()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $user = Auth::user();

            Log::info('Auth ID: ' . $user);

            $wishlists = $user->wishlist()
                ->with('product')
                ->orderBy('id', 'DESC')
                ->paginate(10);

            return response()->json($wishlists);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An error occurred. Please try again later.'], 500);
        }
    }
}
