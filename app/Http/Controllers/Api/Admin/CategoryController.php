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
use Illuminate\Validation\Rule;

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

    public function view($slug)
    {
        try {
            $category = Category::where('slug', $slug)->first();

            if (!$category) {
                return response()->json(['message' => 'Category not found!'], 404);
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

    public function update(Request $request, $slug)
    {
        // Find the existing category
        $category = Category::where('slug', $slug)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Validation
        $request->validate([
            'name' => ['required', 'string', Rule::unique('category', 'name')->ignore($category->id)],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'mimes:jpg,jpeg,png,webp,avif'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_featured' => ['nullable'],
            'status' => ['required', 'string'],
        ], [
            'name.required' => 'This field is required',
            'name.string' => 'Invalid inputs',
            'name.unique' => 'This category already exists',

            'description.required' => 'This field is required',
            'description.string' => 'Invalid inputs',

            'image.mimes' => 'Only jpg, jpeg, webp and png are accepted',

            'status.required' => 'This field is required',
            'status.string' => 'Invalid input',

            'parent_id.exists' => 'Invalid parent category',
        ]);

        try {
            DB::beginTransaction();

            // Prepare fields (slug excluded)
            $updateData = [
                'name' => $request->name,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'is_featured' => $request->boolean('is_featured'),
                'status' => $request->status,
            ];

            // Check for image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $newImageName = $image->getClientOriginalName(); // e.g. "category.jpg"

                // Extract just the filename (without path) from the existing stored image
                $existingImageName = $category->image ? basename($category->image) : null;

                // Only update if the new image name is different
                if ($newImageName !== $existingImageName) {
                    // delete old image if exists
                    if ($category->image && Storage::disk('public')->exists($category->image)) {
                        Storage::disk('public')->delete($category->image);
                    }

                    $imageName = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();
                    $directory = 'categories';
                    $imagePath = $image->storeAs($directory, $imageName, 'public');
                    $updateData['image'] = $imagePath;
                }
            }


            // Check if any data actually changed
            $changes = [];
            foreach ($updateData as $key => $value) {
                // Compare old and new values (cast both to string for accurate comparison)
                if ((string) $category->{$key} !== (string) $value) {
                    $changes[$key] = $value;
                }
            }

            // If no changes and no new image, skip update
            if (empty($changes)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'No changes were made to the category.',
                ], 200);
            }

            // Perform update
            $category->update($changes);

            DB::commit();

            return response()->json([
                'message' => 'Category updated successfully',
                'data' => $category,
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());

            return response()->json(['message' => 'An unexpected error occurred'], 500);
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
