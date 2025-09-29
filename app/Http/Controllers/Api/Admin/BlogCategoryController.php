<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Unique;

class BlogCategoryController extends Controller
{
    public function index()
    {
        try {

            $category = PostCategory::orderBy('id', 'DESC')->paginate(10);

            if ($category->isEmpty()) {
                return response()->json(['message' => 'No category found'], 404);
            }

            return response()->json($category, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => ['required', 'string', 'unique:post_category,category_name'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'file', 'mimes:jpg,jpeg,webp,avif']
        ], [
            'category_name.required' => 'This field is required',
            'category_name.string' => 'Invalid input',
            'category_name.unique' => 'Category already exists',
            'status.required' => 'This field is required',
            'status.string' => 'Invalid input',
            'status.in' => 'Invalid status option',
            'description.string' => 'Invalid input',
            'featured_image.file' => 'Unknown file type',
            'featured_image.mimes' => 'Unknown file type'
        ]);

        DB::beginTransaction();

        try {
            $featured_image = null;
            $tempImage = null;
            $tempName = null;

            if ($request->hasFile('featured_image')) {
                $tempImage = $request->file('featured_image');
                $tempName = uniqid() . '.' . $tempImage->getClientOriginalExtension();

                // Save only the name (or relative path) to DB
                $featured_image = 'post_categories/' . $tempName;
            }

            PostCategory::create([
                'category_name' => $request->category_name,
                'status' => $request->status,
                'description' => $request->description,
                'featured_image' => $featured_image
            ]);

            DB::commit();

            // 👉 Move file only after DB commit succeeds
            if ($tempImage) {
                $uploadPath = storage_path('app/public/post_categories');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $tempImage->move($uploadPath, $tempName);
            }

            return response()->json(['message' => 'Category added successfully'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
