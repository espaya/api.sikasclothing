<?php

namespace App\Http\Controllers;

use App\Models\Spotlight;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SpotlightController extends Controller
{
    public function index()
    {
        $spotlights = Cache::remember('spotlights_all', 3600, function () {
            return Spotlight::orderBy('id', 'DESC')->get();
        });

        if ($spotlights->isEmpty()) {
            return response()->json(['message' => 'No Spotlights found'], 404);
        }

        return response()->json($spotlights);
    }

    public function frontpage()
    {
        $spotlights = Cache::remember('spotlights_frontpage', 3600, function () {
            return Spotlight::orderBy('id', 'DESC')
                ->limit(3)
                ->get();
        });

        if ($spotlights->isEmpty()) {
            return response()->json(['message' => 'No Spotlights found'], 404);
        }

        return response()->json($spotlights);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string'],
            'link_text' => ['required', 'string'],
            'link_url' => ['required', 'url'],
            'add_to_megamenu' => ['nullable', 'in:1'],
            'bg_color' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $isValidHex = preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $value);
                    $isValidRgb = preg_match('/^rgb\(\s*(?:\d{1,3}%?\s*,\s*){2}\d{1,3}%?\s*\)$/', $value);
                    $isValidHsl = preg_match('/^hsl\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*\)$/', $value);

                    if (!($isValidHex || $isValidRgb || $isValidHsl)) {
                        $fail("The {$attribute} must be a valid HEX, RGB, or HSL color.");
                    }
                },
                'required_without:bg_image',
            ],
            'bg_image' => ['nullable', 'mimes:jpg,jpeg,png,webp,avif', 'required_without:bg_color'],
        ], [
            'title.required' => 'This field is required',
            'title.string' => 'Invalid input',
            'link_text.required' => 'This field is required',
            'link_text.string' => 'Invalid input',
            'link_url.required' => 'This field is required',
            'link_url.url' => 'Invalid url/link',
            'bg_color.required_without' => 'This field is required without background image',
            'bg_color.string' => 'Invalid input',
            'bg_image.required_without' => 'This field is required without background color',
            'bg_image.mimes' => 'Invalid image',
            'add_to_megamenu.in' => 'This field contains invalid input'
        ]);

        DB::beginTransaction();

        try {
            $imageFileName = null;

            if ($request->hasFile('bg_image')) {
                $image = $request->file('bg_image');

                // Ensure the directory exists (inside storage/app/public)
                $directory = storage_path('app/public/spotlight');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Generate unique filename
                $imageFileName = time() . '.' . $image->getClientOriginalExtension();

                // Store in public disk under "spotlight"
                $image->storeAs('spotlight', $imageFileName, 'public');
            }

            Spotlight::create([
                'title' => $request->title,
                'link_text' => $request->link_text,
                'link_url' => $request->link_url,
                'bg_color'  => $request->bg_color ?? "",
                'bg_image'  => $imageFileName ?? "",
                'add_to_megamenu' => $request->add_to_megamenu ?? "",
            ]);

            DB::commit();

            // Clear caches so fresh data is fetched next time
            Cache::forget('spotlights_all');
            Cache::forget('spotlights_frontpage');

            return response()->json(['message' => 'Spotlight created successfully']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'Could not create the spotlight'], 500);
        }
    }

    public function view($id)
    {
        try {
            $spotlight = Spotlight::where('id', $id)->first();

            if (!$spotlight) {
                return response()->json(['message' => 'Spotlight not found'], 404);
            }

            return response()->json($spotlight, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage() . ' on line: ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info($request);
        $request->validate([
            'title' => ['required', 'string'],
            'link_text' => ['required', 'string'],
            'link_url' => ['required', 'url'],
            'add_to_megamenu' => ['nullable', 'in:1'],
            'bg_color' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $isValidHex = preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $value);
                    $isValidRgb = preg_match('/^rgb\(\s*(?:\d{1,3}%?\s*,\s*){2}\d{1,3}%?\s*\)$/', $value);
                    $isValidHsl = preg_match('/^hsl\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*\)$/', $value);

                    if (!($isValidHex || $isValidRgb || $isValidHsl)) {
                        $fail("The {$attribute} must be a valid HEX, RGB, or HSL color.");
                    }
                },
                'required_without:bg_image',
            ],
            'bg_image' => ['nullable', 'required_without:bg_color'],
        ], [
            'title.required' => 'This field is required',
            'title.string' => 'Invalid input',
            'link_text.required' => 'This field is required',
            'link_text.string' => 'Invalid input',
            'link_url.required' => 'This field is required',
            'link_url.url' => 'Invalid url/link',
            'bg_color.required_without' => 'This field is required without background image',
            'bg_color.string' => 'Invalid input',
            'bg_image.required_without' => 'This field is required without background color',
            'bg_image.mimes' => 'Invalid image type',
            'add_to_megamenu.in' => 'This field contains invalid input'
        ]);

        DB::beginTransaction();

        try {
            $spotlight = Spotlight::findOrFail($id);

            // Normalize checkbox
            $validated['add_to_megamenu'] = $request->boolean('add_to_megamenu');

            // Keep old image name to delete later if needed
            $oldImage = $spotlight->bg_image;

            // upload image if its new
            if ($request->bg_image !== $spotlight->bg_image) {
                // Handle image upload
                if ($request->hasFile('bg_image')) {

                    // ✅ Validate MIME type & size
                    $request->validate([
                        'bg_image' => [
                            'file',
                            'image',
                            'mimetypes:image/jpeg,image/png,image/webp,image/avif',
                            'max:2048', // 2MB
                        ],
                    ], [
                        'bg_image.image' => 'The file must be an image.',
                        'bg_image.mimetypes' => 'Only JPG, PNG, or WEBP images are allowed.',
                        'bg_image.max' => 'Image must not exceed 2MB.',
                    ]);

                    $image = $request->file('bg_image');

                    $directory = storage_path('app/public/spotlight');
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    $imageFileName = time() . '.' . $image->getClientOriginalExtension();
                    $image->storeAs('spotlight', $imageFileName, 'public');

                    // Assign new image to model
                    $validated['bg_image'] = $imageFileName;
                }
            }


            $spotlight->fill($validated);

            if (! $spotlight->isDirty()) {
                DB::rollBack();
                return response()->json(['message' => 'No changes detected'], 200);
            }

            $spotlight->save();
            DB::commit();

            // Only delete old image AFTER DB commit
            if ($request->hasFile('bg_image') && $oldImage) {
                $oldImagePath = storage_path('app/public/spotlight/' . $oldImage);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath); // suppress errors just in case
                }
            }

            // Clear caches
            Cache::forget('spotlights_all');
            Cache::forget('spotlights_frontpage');

            return response()->json(['message' => 'Spotlight updated successfully'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . ' on line ' . $ex->getLine());

            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
