<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallToAction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallToActionController extends Controller
{
    public function index()
    {
        try {
            $callToActions = CallToAction::orderBy('id', 'DESC')->get();

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

            return response()->json(['message' => 'Call to action created successfully']);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An error occurred, try again later'], 500);
        }
    }
}
