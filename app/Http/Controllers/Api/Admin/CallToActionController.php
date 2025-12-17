<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallToAction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CallToActionController extends Controller
{
    public function index()
    {
        try {
            // Cache results for 1 hour
            $callToActions = Cache::remember('call_to_actions', 3600, function () {
                return CallToAction::orderBy('id', 'DESC')->get();
            });

            if ($callToActions->isEmpty()) {
                return response()->json(['message' => 'No call to actions found']);
            }

            return response()->json($callToActions);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An error occurred fetching call to actions, try again later']);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string'],
            'subtitle' => ['required', 'string'],
            'btn_text' => ['required', 'string'],
            'btn_url' => ['required', 'url'],
            'bg_image' => ['required', 'mimes:jpg,jpeg,png,webp,avif']
        ], [
            'title.required' => 'This field is required',
            'title.string' => 'Invalid input',
            'subtitle.required' => 'This field is required',
            'subtitle.string' => 'Invalid input',
            'btn_text.required' => 'This field is required',
            'btn_text.string' => 'Invalid input',
            'btn_url.required' => 'This field is required',
            'btn_url.url' => 'Invalid url',
            'bg_image.required' => 'This field is required',
            'bg_image.mimes' => 'Invalid image type'
        ]);

        DB::beginTransaction();

        try {
            $imageFileName = null;

            if ($request->hasFile('bg_image')) {
                $image = $request->file('bg_image');

                // Ensure the directory exists
                $directory = storage_path('app/public/call_to_action');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Generate unique filename
                $imageFileName = time() . '.' . $image->getClientOriginalExtension();

                // Store in public disk under "call_to_action"
                $image->storeAs('call_to_action', $imageFileName, 'public');
            }

            CallToAction::create([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'btn_text' => $request->btn_text,
                'btn_url' => $request->btn_url,
                'bg_image' => $imageFileName
            ]);

            DB::commit();

            // Invalidate cache when new record is added
            Cache::forget('call_to_actions');

            return response()->json(['message' => 'Call to action created successfully']);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An error occurred, try again later'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'subtitle' => ['required', 'string'],
            'btn_text' => ['required', 'string'],
            'btn_url' => ['required', 'url'],
            // 'bg_image' => ['required', 'mimes:jpg,jpeg,png,webp,avif']
        ], [
            'title.required' => 'This field is required',
            'title.string' => 'Invalid input',
            'subtitle.required' => 'This field is required',
            'subtitle.string' => 'Invalid input',
            'btn_text.required' => 'This field is required',
            'btn_text.string' => 'Invalid input',
            'btn_url.required' => 'This field is required',
            'btn_url.url' => 'Invalid url',
            'bg_image.required' => 'This field is required',
            // 'bg_image.mimes' => 'Invalid image type'
        ]);

        DB::beginTransaction();

        try {
            $cta = CallToAction::findOrFail($id);

            // Keep old image name to delete later if needed
            $oldImage = $cta->bg_image;

            // upload image if its new
            if ($request->bg_image !== $cta->bg_image) {
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

                    $directory = storage_path('app/public/call_to_action');
                    if (!file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    $imageFileName = time() . '.' . $image->getClientOriginalExtension();
                    $image->storeAs('call_to_action', $imageFileName, 'public');

                    // Assign new image to model
                    $validated['bg_image'] = $imageFileName;
                }
            }

            $cta->fill($validated);

            if (!$cta->isDirty()) {
                DB::rollBack();
                return response()->json(['message' => 'No changes detected'], 200);
            }

            $cta->save();
            DB::commit();

            // Only delete old image AFTER DB commit
            if ($request->hasFile('bg_image') && $oldImage) {
                $oldImagePath = storage_path('app/public/call_to_action/' . $oldImage);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath); // suppress errors just in case
                }
            }

            return response()->json(['message' => 'Call to action updated successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . ' on line: ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function view($id)
    {
        try {

            $cta = CallToAction::where('id', $id)->first();

            if (!$cta) {
                return response()->json(['message' => 'Call to action not found'], 404);
            }

            return response()->json($cta, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage() . ' on line: ' . $ex->getLine());
        }
    }
}
