<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Products;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function latestProducts()
    {
        $latestProducts = Products::orderBy('id', 'DESC')->limit(10)->get();
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
}
