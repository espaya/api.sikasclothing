<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\Wishlist;
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

            $wishlists = Wishlist::with(['product.categories'])
                ->where('user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->paginate(10);
            return response()->json($wishlists, 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An error occurred. Please try again later.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $wishlist = Wishlist::where('product_id', $id)->first();

            if (!$wishlist) {
                return response()->json(['message' => 'Product not found in your wishlist'], 404);
            }

            $wishlist->delete();

            return response()->json(['message' => 'Product removed from wishlist'], 200);
        } catch (Exception $ex) {
            Log::error('Error removing product from wishlist: ' . $ex->getMessage() . ' on line ' . $ex->getLine());
            return response()->json(['message' => 'Error removing product from wishlist'], 500);
        }
    }
}
