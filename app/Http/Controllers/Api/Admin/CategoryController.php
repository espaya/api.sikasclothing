<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // Make sure this is imported


class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);

        $categories = Category::orderByRaw("slug = 'general' DESC")
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        return response()->json($categories);
    }


    public function store(Request $request)
    {
        Log::error($request->all());
        $request->validate([
            'name' => ['required', 'string', 'unique:category,name'],
            'description' => ['required', 'string'],
            'image' => ['required', 'mimes:jpg,jpeg,png,webp,avif'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_featured' => ['nullable'],
            'status' => ['required', 'string'],
        ], [
            'name.required' => 'This field is required',
            'name.string' => 'Invalid inputs',
            'name.unique' => 'This category already exists',

            'description.required' => 'This field is required',
            'description.string' => 'Invalid inputs',
            'image.required' => 'Please upload an image',
            'image.mime' => 'Only jpg, jpeg, webp and png are accepted',

            'status.required' => 'This field is required',
            'status.string' => 'Invalid input',

            'is_featured.boolean' => 'Invalid input',

            'parent_id.exists' => 'Invalid parent category'

        ]);

        DB::beginTransaction();

        try {
            $imagePath = null;
            $imageFile = null;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();
                $imageFile = $image; // hold temporarily for now
            }

            $category = Category::create([
                'name' => $request->name,
                'description' => $request->description,
                'slug' => Str::slug($request->name),
                'parent_id' => $request->parent_id,
                'is_featured' => $request->boolean('is_featured'),
                'status' => $request->status,
                'image' => '', // temp value
            ]);

            // Store image only after DB insert is successful
            if ($imageFile) {
                $directory = 'categories';
                $imagePath = $imageFile->storeAs($directory, $imageName, 'public');
                $category->update(['image' => $imagePath]);
            }

            DB::commit();
            return response()->json(['message' => 'Category created successfully', 'data' => $category], 201);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('An error occurred whilst saving category: ' . $ex->getMessage());

            return response()->json(['message' => 'An error occurred whilst saving category'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::find($id);

            if ($category) {
                $category->delete();
            }

            return response()->json(['message' => 'Category Deleted Successfully'], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'Could not delete category. Try again later'], 200);
        }
    }
}
