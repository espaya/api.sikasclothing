<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\Tag;
use App\Models\Wishlist;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $query = Products::with(['reviews', 'categories'])->withCount('reviews');


        if ($request->input('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'LIKE', "%$search%")
                    ->orWhere('gender', 'LIKE', "%$search%")
                    ->orWhere('brand', 'LIKE', "%$search%")
                    ->orWhere('price', 'LIKE', "%$search%")
                    ->orWhere('sale_price', 'LIKE', "%$search%")
                    ->orWhere('status', 'LIKE', "%$search%")
                    ->orWhere('color', 'LIKE', "%$search%")
                    ->orWhere('material', 'LIKE', "%$search%")
                    ->orWhere('fit_type', 'LIKE', "%$search%")
                    ->orWhere('size', 'LIKE', "%$search%")
                    ->orWhere('discount', 'LIKE', "%$search%");
            });
        }

        $products = $query->orderBy('id', 'DESC')->paginate($perPage);


        return response()->json($products);
    }

    public function show($slug)
    {
        $product = Products::with(['categories', 'reviews', 'tags'])->where('slug', $slug)->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Fetch related products: sharing at least one category, excluding current product
        $relatedProducts = Products::where('id', '!=', $product->id)
            ->whereHas('categories', function ($query) use ($product) {
                $query->whereIn('category_id', $product->categories->pluck('id'));
            })
            ->with(['categories', 'tags'])
            ->take(4)
            ->get();

        return response()->json([
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name' => ['required', 'string'],
            'category' => ['required', 'array', 'min:1'],
            'category.*' => ['string'],
            'tags' => ['nullable', 'array', 'min:1'],
            'tags.*' => ['string'],
            'gender' => ['required', 'string', 'in:Male,Female,Unisex,Other'],
            'brand' => ['required_without:custom_brand', 'string', 'nullable'],
            'custom_brand' => ['required_without:brand', 'string', 'nullable'],
            'description' => ['required', 'string'],
            'price' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'sale_price' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'stock_status' => ['required', 'in:in_stock,out_of_stock,backorder'],
            'colors' => ['required', 'array', 'min:1', 'not_in:#ffffff'],
            'colors.*' => [
                'required',
                'string',
                'regex:/^(#(?:[0-9a-fA-F]{3}){1,2}|rgba?\(\s*\d{1,3}%?\s*,\s*\d{1,3}%?\s*,\s*\d{1,3}%?(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)|hsla?\(\s*\d{1,3}(?:deg)?\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*(?:,\s*(?:0|1|0?\.\d+))?\s*\))$/i',
            ],

            'material' => ['required', 'string'],
            'fit_type' => ['required', 'array', 'min:1'],
            'fit_type.*' => ['string'],
            // 'size' => ['required_without:custom_size', 'string', 'nullable'],
            // 'custom_size' => ['required_without:size', 'string', 'nullable'],
            'size' => ['required', 'array', 'min:1'],
            'size.*' => ['string'],
            'gallery' => ['required', 'array', 'min:3'],
            'gallery.*' => ['file', 'mimes:jpg,jpeg,webp,png,avif', 'max:5120'],
            'status' => ['required', 'in:Published,Draft'],
            'featured' => ['nullable'],
            'barcode' => ['nullable', 'string', 'unique:products,barcode'],
            'discount' => ['nullable', 'string'],
            'weight' => ['nullable', 'string'],
            'dimensions' => ['nullable', 'string'],
            'storage' => ['nullable', 'string']
        ], [
            'product_name.required' => 'This field is required',
            'product_name.string' => 'Invalid inputs',
            'category.required' => 'This field is required',
            'category.string' => 'Invalid input',
            'tags.string' => 'Invalid inputs',
            'gender.required' => 'This field is required',
            'gender.string' => 'Invalid input',
            'brand.required' => 'This field is required',
            'brand.string' => 'Invalid inputs',
            'description.required' => 'This field is required',
            'description.string' => 'Invalid inputs',
            'price.required' => 'This field is required',
            'price.regex' => 'Invalid inputs. format: 12.54 etc only allowed',
            'sale_price.regex' => 'Invalid inputs. format: 12.54 etc only allowed',
            'stock_quantity.required' => 'This field is required',
            'stock_quantity.integer' => 'This valid input. Only integers are allowed',
            'stock_status.required' => 'This field is required',
            'stock_status.in' => 'Only "In Stock", "Out of Stock" and "Backorder" are allowed ',
            'status.required' => 'This field is required',
            'status.in' => 'Only "Published" and "Draft" are allowed',
            'featured.boolean' => 'Invalid input',
            'barcode.string' => 'Invalid barcode',
            'barcode.unique' => 'This barcode already exists'
        ]);

        $colors = collect($request->colors)
            ->reject(fn($color, $i) => $i === 0 && strtolower($color) === '#ffffff');

        if ($colors->isEmpty()) {
            return back()->withErrors(['colors' => 'Please select at least one color.'])->withInput();
        }



        DB::beginTransaction();

        $imagePaths = [];

        try {
            // Upload gallery images
            if ($request->hasFile('gallery')) {
                $directory = 'products';

                // Ensure the directory exists in the public disk
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory, 0755, true);
                }

                foreach ($request->file('gallery') as $image) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs($directory, $imageName, 'public');
                    $imagePaths[] = $path;
                }
            }

            // Generate unique slug
            $baseSlug = Str::slug($request->product_name);
            $slug = $baseSlug;
            $count = 1;

            while (Products::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }

            // Generate unique SKU
            $sku = $this->generateSKU($request->product_name, $request->brand, $request->size);

            while (Products::where('sku', $sku)->exists()) {
                $sku = $this->generateSKU($request->product_name, $request->brand, $request->size);
            }

            $tagIds = collect($request->tags ?? [])
                ->filter(fn($tag) => is_string($tag) && trim($tag) !== '')
                ->map(function ($tagName) {
                    return Tag::firstOrCreate(['name' => trim($tagName)])->id;
                });


            // Save product
            $product = Products::create([
                'product_name' => trim($request->product_name),
                'gender' => trim($request->gender),
                'brand' => trim($request->brand ?? $request->custom_brand ?? ''),
                'description' => trim($request->description),
                'price' => trim($request->price),
                'sale_price' => trim($request->sale_price),
                'stock_quantity' => trim($request->stock_quantity),
                'stock_status' => trim($request->stock_status),
                'color' => json_encode($request->colors),
                'material' => trim($request->material),
                'fit_type' => json_encode($request->fit_type),
                'size' => json_encode($request->size),
                'gallery' => implode(',', $imagePaths),
                'status' => trim($request->status),
                'featured' => $request->featured ?? null,
                'slug' => $slug,
                'sku' => $sku,
                'barcode' => trim($request->barcode),
                'discount' => $request->discount ?? '',
                'weight' => $request->weight ?? '',
                'dimensions' => $request->dimensions ?? '',
                'storage' => $request->storage ?? '',
            ]);

            $product->tags()->sync($tagIds->toArray());
            $product->categories()->attach($request->category);

            DB::commit();

            return response()->json(['message' => 'Product added successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();

            // Delete uploaded images if any
            foreach ($imagePaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('Error Saving Product: ' . $ex->getMessage() . ' in ' . $ex->getFile() . ' on line ' . $ex->getLine());

            return response()->json(['message' => 'Error Saving Product'], 500);
        }
    }

    private function generateSKU($productName, $brand, $size)
    {
        $brand = (string) $brand;
        $productName = (string) $productName;
        if (is_array($size)) {
            $size = implode('-', array_map('trim', $size));
        }


        $brandCode = strtoupper(substr(preg_replace('/\s+/', '', $brand), 0, 3));
        $productCode = strtoupper(substr(preg_replace('/\s+/', '', $productName), 0, 3));
        $sizeCode = strtoupper($size);
        $random = rand(1000, 9999);

        return "{$brandCode}-{$productCode}-{$sizeCode}-{$random}";
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $product = Products::with(['categories', 'tags', 'reviews'])->find($id);

            if (!$product) {
                return response()->json([
                    'message' => 'Product not found!'
                ], 404);
            }

            // Delete images
            if ($product->gallery && file_exists(public_path('storage/' . $product->gallery))) {
                unlink(public_path('storage/' . $product->gallery));
            }

            // If gallery is stored as array or JSON
            if (!empty($product->gallery)) {
                $gallery = is_array($product->gallery) ? $product->gallery : json_decode($product->gallery, true);

                if (is_array($gallery)) {
                    foreach ($gallery as $image) {
                        $imagePath = public_path('storage/' . $image);
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                }
            }


            // 2. Detach categories and tags (pivot tables)
            $product->categories()->detach();
            $product->tags()->detach();

            // 3. Delete reviews
            $product->reviews()->delete();

            // 4. Delete product
            $product->delete();

            DB::commit();

            return response()->json([
                'message' => 'Product deleted successfully'
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json([
                'message' => 'An error occurred. Try again later'
            ], 500);
        }
    }


    public function addToWishlist($id)
    {
        if (Auth::check()) {
            DB::beginTransaction();

            try {
                $product = Products::findOrFail($id);

                $alreadyInWishlist = Wishlist::where('user_id', Auth::id())
                    ->where('product_id', $id)
                    ->exists();

                if ($alreadyInWishlist) {
                    return response()->json(['message' => 'Product is already in the wishlist']);
                }

                Wishlist::create([
                    'user_id' => Auth::id(),
                    'product_id' => $id
                ]);

                DB::commit();

                return response()->json(['message' => 'Product added to wishlist'], 200);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                DB::rollBack();
                Log::error($e->getMessage());
                return response()->json(['message' => 'Product not found'], 404);
            } catch (\Exception $ex) {
                DB::rollBack();
                Log::error($ex->getMessage());

                return response()->json(['message' => 'An error occurred. Try again later'], 500);
            }
        } else {
            // 🔹 Guest users — store product ID in session
            $wishlist = session()->get('guest_wishlist', []);

            if (in_array($id, $wishlist)) {
                return response()->json(['message' => 'Product is already in the wishlist']);
            }

            $product = Products::find($id);

            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            $wishlist[] = $id;
            session(['guest_wishlist' => $wishlist]);

            return response()->json(['message' => 'Product added to wishlist'], 200);
        }
    }

    public function checkWishlist($id)
    {
        if (Auth::check()) {
            try {
                $alreadyInWishlist = Wishlist::where('user_id', Auth::id())
                    ->where('product_id', $id)
                    ->exists();

                Log::info($alreadyInWishlist);

                return response()->json($alreadyInWishlist); // true or false

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                Log::error($e->getMessage());
                return response()->json(false); // Fallback as false
            } catch (\Exception $ex) {
                Log::error($ex->getMessage());
                return response()->json(false); // Fallback as false
            }
        } else {
            // Guest user logic
            $wishlist = session()->get('guest_wishlist', []);

            return response()->json(in_array($id, $wishlist)); // true or false
        }
    }
}
