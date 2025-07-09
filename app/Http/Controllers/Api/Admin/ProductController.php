<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => ['required', 'string'],
            'category' => ['required', 'string'],
            'tags' => ['nullable', 'string'],
            'gender' => ['required', 'string'],
            'brand' => ['required', 'string'],
            'description' => ['required', 'string'],
            'price' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'sale_price' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'stock_status' => ['required', 'in:in_stock,out_of_stock,backorder'],
            'color' => ['required', 'string'],
            'material' => ['required', 'string'],
            'fit_type' => ['required', 'string'],
            'size' => ['required', 'string'],
            'gallery' => ['required', 'array'],
            'gallery.*' => ['file', 'mimes:jpg,jpeg,webp,png', 'max:5120'],
            'status' => ['required', 'in:published,draft'],
            'featured' => ['nullable', 'boolean'],
            'custom_brand' => ['nullable', 'string'],
            'custom_size' => ['nullable', 'string']
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
            'featured.boolean' => 'Invalid input'
        ]);

        DB::beginTransaction();

        try {

            // Upload gallery images
            $imagePaths = [];

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
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            // Generate a unique SKU
            $sku = $this->generateSKU($request->product_name, $request->brand, $request->size);

            while (Products::where('sku', $sku)->exists()) {
                $sku = $this->generateSKU($request->product_name, $request->brand, $request->size);
            }

            // Save product
            Products::create([
                'product_name' => trim($request->product_name),
                'category' => trim($request->category),
                'tags' => implode(',', $request->tags ?? []),
                'gender' => trim($request->gender),
                'brand' => trim($request->brand),
                'description' => trim($request->description),
                'price' => trim($request->price),
                'sale_price' => trim($request->sale_price),
                'stock_quantity' => trim($request->stock_quantity),
                'stock_status' => trim($request->stock_status),
                'color' => trim($request->color),
                'material' => trim($request->material),
                'fit_type' => trim($request->fit_type),
                'size' => trim($request->size),
                'gallery' => implode(',', $imagePaths),
                'status' => trim($request->status),
                'featured' => $request->boolean('featured'),
                'slug' => $slug,
                'custom_brand' => $request->custom_brand,
                'custom_size' => $request->custom_size,
                'sku' => $sku
            ]);

            DB::commit();

            return response()->json(['message' => 'Product added successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
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
