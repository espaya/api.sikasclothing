<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopPageController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Initialize the query to fetch products with related reviews, categories, and tags
            $query = Products::with(['reviews', 'categories', 'tags', 'brandRelation']);

            /**
             * 🔍 Apply filters only if provided
             */
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


            if ($request->filled('color')) {
                $color = $request->input('color');

                // Regex to validate Hex, RGB, HSL
                $validColorPattern = '/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$|^rgb(a)?\(\s*\d+\s*,\s*\d+\s*,\s*\d+(,\s*[\d.]+)?\s*\)$|^hsl(a)?\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%(,\s*[\d.]+)?\s*\)$/';

                if (preg_match($validColorPattern, $color)) {
                    // Search for products where the color JSON array contains the given color
                    $query->whereJsonContains('color', $color);
                }
            }

            if ($request->filled('size')) {
                $query->whereJsonContains('size', $request->input('size'));
            }



            if ($request->filled('tags')) {
                $tags = explode(',', $request->input('tags')); // ✅ handle CSV from frontend

                $query->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('name', $tags); // adjust field (name/slug/id) as needed
                });
            }


            if ($request->filled('brand')) {
                $brands = (array) $request->input('brand');

                $query->where(function ($q) use ($brands) {
                    // Handle normal brand filtering
                    $q->whereIn('custom_brand', $brands)
                        ->orWhereHas('brandRelation', function ($sub) use ($brands) {
                            $sub->whereIn('name', $brands);
                        });

                    // Handle "Other" option separately
                    if (in_array('Other', $brands)) {
                        $q->orWhere('brand', 'Other');
                    }
                });
            }


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


            /**
             * 📌 Apply sorting only if requested
             */
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
                // ✅ Default sorting (only if no sorting requested)
                $query->orderBy('id', 'DESC');
            }

            /**
             * 📄 Paginate results
             */
            $products = $query->paginate(10);

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
        $categories = Category::orderBy('name', 'ASC')->get();
        return response()->json($categories);
    }

    public function randomCategories()
    {
        try {
            $count = Category::count();
            $limit = $count < 8 ? $count : 8;

            $categories = Category::inRandomOrder()
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

            return response()->json($categories);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'Could not fetch categories'], 500);
        }
    }



    // public function getBrands()
    // {
    //     try {
    //         $brands = Brand::orderBy('name', 'ASC')->get();

    //         return response()->json($brands);
    //     } catch (Exception $ex) {
    //         Log::error($ex->getMessage());
    //         return response()->json(['message' => 'Could not get brands'], 500);
    //     }
    // }
}
