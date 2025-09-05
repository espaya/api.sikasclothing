<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


class HomeController extends Controller
{
    public function latestProducts()
    {
        $latestProducts = Products::with(['reviews'])->orderBy('id', 'DESC')->limit(10)->get();
        return response()->json($latestProducts);
    }

    public function shopByCategory()
    {
        $categories = Category::where('is_featured', '1')
            ->orderBy('name', 'ASC') // ASC = ascending (A–Z)
            ->limit(10)
            ->get();
        return response()->json($categories);
    }

    public function bestSelling()
    {
        try {
            $cacheKey = 'best_selling_products';
            $cacheDuration = 60; // minutes

            $bestSelling = Cache::remember($cacheKey, $cacheDuration * 60, function () {
                $categories = Category::take(4)->get();
                $result = [];

                foreach ($categories as $category) {
                    $products = Products::withSum('orderItems', 'quantity')
                        ->whereHas('categories', fn($q) => $q->where('category.id', $category->id))
                        ->having('order_items_sum_quantity', '>', 0)
                        ->orderByDesc('order_items_sum_quantity')
                        ->take(4)
                        ->get();


                    $result[$category->name] = $products;
                }

                return $result;
            });

            return response()->json($bestSelling, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
