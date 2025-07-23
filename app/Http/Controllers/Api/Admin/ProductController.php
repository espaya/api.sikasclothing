<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\Tag;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        if ($request->input('search')) {
            $search = trim($request->input('search'));

            $products = Products::where('product_name', 'LIKE', "%$search%")
                ->orWhere('gender', 'LIKE', "%$search%")
                ->orWhere('brand', 'LIKE', "%$search%")
                ->orWhere('price', 'LIKE', "%$search%")
                ->orWhere('sale_price', 'LIKE', "%$search%")
                ->orWhere('status', 'LIKE', "%$search%")
                ->orWhere('color', 'LIKE', "%$search%")
                ->orWhere('material', 'LIKE', "%$search%")
                ->orWhere('fit_type', 'LIKE', "%$search%")
                ->orWhere('size', 'LIKE', "%$search%")
                ->orWhere('discount', 'LIKE', "%$search%")
                ->orderBy('id', 'DESC')
                ->paginate($perPage);

            return response()->json($products); // early return
        }

        // fallback: no search
        $products = Products::orderBy('id', 'DESC')->paginate($perPage);

        Log::info($products);

        return response()->json($products);
    }


    public function store(Request $request)
    {
        $request->validate([
            'product_name' => ['required', 'string'],
            'category' => ['required', 'string'],
            'tags' => ['nullable', 'string'],
            'gender' => ['required', 'string', 'in:Male,Female,Unisex,Other'],
            'brand' => ['required', 'string'],
            'description' => ['required', 'string'],
            'price' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'sale_price' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'stock_status' => ['required', 'in:in_stock,out_of_stock,backorder'],
            'color' => ['required', 'string'],
            'material' => ['required', 'string'],
            'fit_type' => ['required', 'string'],
            'size' => ['required_without:custom_size', 'string', 'nullable'],
            'gallery' => ['required', 'array', 'min:3'],
            'gallery.*' => ['file', 'mimes:jpg,jpeg,webp,png,avif', 'max:5120'],
            'status' => ['required', 'in:Published,Draft'],
            'featured' => ['nullable'],
            'custom_size' => ['required_without:size', 'string', 'nullable'],
            'barcode' => ['nullable', 'string', 'unique:products,barcode'],
            'discount' => ['nullable', 'string'],
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

            $tagIds = collect($request->tags ?? [])->map(function ($tagName) {
                return Tag::firstOrCreate(['name' => trim($tagName)])->id;
            });

            // Save product
            $product = Products::create([
                'product_name' => trim($request->product_name),
                'category' => trim($request->category),
                'gender' => trim($request->gender),
                'brand' => trim($request->brand),
                'description' => trim($request->description),
                'price' => trim($request->price),
                'sale_price' => trim($request->sale_price),
                'stock_quantity' => trim($request->stock_quantity),
                'stock_status' => trim($request->stock_status),
                'color' => json_encode($request->color),
                'material' => trim($request->material),
                'fit_type' => trim($request->fit_type),
                'size' => trim($request->size ?? $request->custom_size),
                'gallery' => implode(',', $imagePaths),
                'status' => trim($request->status),
                'featured' => $request->featured ?? null,
                'slug' => $slug,
                'sku' => $sku,
                'barcode' => trim($request->barcode),
                'tags' => '',
                'discount' => $request->discount ?? '',
            ]);

            $product->tags()->sync($tagIds);

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

            Log::error('Error Saving Product: ' . $ex->getMessage());
            return response()->json(['message' => 'Error Saving Product'], 500);
        }
    }

    private function generateSKU($productName, $brand, $size)
    {
        $brandCode = strtoupper(substr(preg_replace('/\s+/', '', $brand), 0, 3));
        $productCode = strtoupper(substr(preg_replace('/\s+/', '', $productName), 0, 3));
        $sizeCode = strtoupper($size);
        $random = rand(1000, 9999);

        return "{$brandCode}-{$productCode}-{$sizeCode}-{$random}";
    }
}
