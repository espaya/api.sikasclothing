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
}
