<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopPageController extends Controller
{
    public function index(Request $request)
    {
        try {
            /**
             * 🔑 Build a unique cache key based on filters, sort, and page
             */
            $cacheKey = 'products_' . md5(json_encode($request->all()));

            // Cache for 10 minutes without tags
            $products = Cache::remember($cacheKey, 600, function () use ($request) {
                $query = Products::with(['reviews', 'categories', 'tags', 'brandRelation']);

                // Category filter
                if ($request->filled('category')) {
                    $category = $request->input('category');
                    $query->whereHas('categories', function ($q) use ($category) {
                        if (is_numeric($category)) {
                            $q->where('id', $category);
                        } else {
                            $q->where('slug', $category);
                        }
                    });
                }

                // Color filter
                if ($request->filled('color')) {
                    $color = $request->input('color');
                    $validColorPattern = '/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$|^rgb(a)?\(\s*\d+\s*,\s*\d+\s*,\s*\d+(,\s*[\d.]+)?\s*\)$|^hsl(a)?\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%(,\s*[\d.]+)?\s*\)$/';
                    if (preg_match($validColorPattern, $color)) {
                        $query->whereJsonContains('color', $color);
                    }
                }

                // Size filter
                if ($request->filled('size')) {
                    $query->whereJsonContains('size', $request->input('size'));
                }

                // Tags filter
                if ($request->filled('tags')) {
                    $tags = explode(',', $request->input('tags'));
                    $query->whereHas('tags', function ($q) use ($tags) {
                        $q->whereIn('name', $tags);
                    });
                }

                // Brand filter
                if ($request->filled('brand')) {
                    $brands = (array) $request->input('brand');
                    $query->where(function ($q) use ($brands) {
                        $q->whereIn('custom_brand', $brands)
                            ->orWhereHas('brandRelation', function ($sub) use ($brands) {
                                $sub->whereIn('name', $brands);
                            });

                        if (in_array('Other', $brands)) {
                            $q->orWhere('brand', 'Other');
                        }
                    });
                }

                // Price filter
                if ($request->filled('min_price') && $request->filled('max_price')) {
                    $minPrice = $request->input('min_price', Products::min('price'));
                    $maxPrice = $request->input('max_price', Products::max('price'));

                    $query->when($minPrice, function ($q) use ($minPrice) {
                        $q->where('price', '>=', (float) $minPrice);
                    });

                    $query->when($maxPrice, function ($q) use ($maxPrice) {
                        $q->where('price', '<=', (float) $maxPrice);
                    });
                }

                // Sorting
                if ($request->filled('sort_product')) {
                    switch ($request->input('sort_product')) {
                        case 'featured':
                            $query->orderBy('featured', 'DESC');
                            break;
                        case 'Best Selling':
                            $query->orderBy('sales_count', 'DESC');
                            break;
                        case 'a-z':
                            $query->orderBy('product_name', 'ASC');
                            break;
                        case 'z-a':
                            $query->orderBy('product_name', 'DESC');
                            break;
                        case 'price, low to high':
                            $query->orderBy('price', 'ASC');
                            break;
                        case 'price, high to low':
                            $query->orderBy('price', 'DESC');
                            break;
                        case 'date, old to new':
                            $query->orderBy('created_at', 'ASC');
                            break;
                        case 'date, new to old':
                            $query->orderBy('created_at', 'DESC');
                            break;
                    }
                } else {
                    $query->orderBy('id', 'DESC');
                }

                return $query->paginate(10);
            });

            return response()->json($products);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json([
                'message' => 'An error occurred whilst getting products'
            ], 500);
        }
    }


    public function getCategory()
    {
        // Cache categories for 1 hour without tags
        $categories = Cache::remember('all_categories', 3600, function () {
            return Category::orderBy('name', 'ASC')->get();
        });

        return response()->json($categories);
    }


    public function randomCategories()
    {
        try {
            $categories = Cache::remember('random_categories', 600, function () {
                $count = Category::count();
                $limit = $count < 8 ? $count : 8;

                return Category::inRandomOrder()
                    ->take($limit)
                    ->get(['id', 'name', 'slug', 'image'])
                    ->map(function ($category) {
                        return [
                            'id'     => $category->id,
                            'name'   => $category->name,
                            'slug'   => $category->slug,
                            'image'  => $category->image,
                            'status' => 'active',
                        ];
                    });
            });

            return response()->json($categories);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'Could not fetch categories'], 500);
        }
    }
}
