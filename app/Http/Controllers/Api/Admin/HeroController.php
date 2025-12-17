<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Hero;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HeroController extends Controller
{
    public function index()
    {
        $heroes = Cache::remember('heroes_all', 3600, function () {
            return Hero::orderBy('id', 'DESC')->get();
        });

        if ($heroes->isEmpty()) {
            return response()->json(['message' => 'Heroes not found!']);
        }

        return response()->json($heroes);
    }

    public function view($id)
    {
        try {
            $hero = Hero::where('id', $id)->first();

            if (!$hero) {
                return response()->json(['message' => 'Hero not found'], 404);
            }
            return response()->json($hero, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage() . ' on line: ' . $ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string'],
            'subtitle' => ['required', 'string'],
            'text' => ['required', 'string'],
            'img' => ['required', 'mimes:png,jpg,jpeg,webp'],
            'btn_text' => ['required', 'string'],
            'btn_link' => ['required', 'string', 'url']
        ], [
            'title.required' => 'This field is required',
            'title.string' => 'Invalid input',
            'subtitle.required' => 'This field is required',
            'subtitle.string' => 'Invalid input',
            'text.required' => 'This field is required',
            'text.string' => 'Invalid input',
            'img.required' => 'This field is required',
            'img.mimes' => 'Invalid image type',
            'btn_text.required' => 'This field is required',
            'btn_text.string' => 'Invalid input',
            'btn_link.required' => 'This field is required',
            'btn_link.url' => 'Invalid url'
        ]);

        DB::beginTransaction();

        try {
            $imageFileName = null;

            if ($request->hasFile('img')) {
                $image = $request->file('img');
                $directory = storage_path('app/public/heros');

                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                $imageFileName = time() . '.' . $image->getClientOriginalExtension();
                $image->move($directory, $imageFileName);
            }

            Hero::create([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'text' => $request->text,
                'img' => $imageFileName,
                'btn_text' => $request->btn_text,
                'btn_link' => $request->btn_link
            ]);

            DB::commit();

            // Clear cache so the new hero appears in index()
            Cache::forget('heroes_all');

            return response()->json(['message' => 'Hero slider created successfully']);
        } catch (Exception $ex) {
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
            'text' => ['required', 'string'],
            'img' => ['nullable', 'mimes:png,jpg,jpeg,webp'],
            'btn_text' => ['required', 'string'],
            'btn_link' => ['required', 'url'],
        ], [
            'title.required' => 'This field is required',
            'subtitle.required' => 'This field is required',
            'text.required' => 'This field is required',
            'img.mimes' => 'Invalid image type',
            'btn_text.required' => 'This field is required',
            'btn_link.url' => 'Invalid url',
        ]);

        DB::beginTransaction();

        try {
            $hero = Hero::findOrFail($id);

            $oldImage = $hero->img;
            $newImageName = null;

            /**
             * 1️⃣ Fill only provided fields
             */
            $hero->fill($validated);

            /**
             * 2️⃣ Handle image change
             */
            if ($request->hasFile('img')) {
                $newImageName = uniqid() . '.' . $request->img->extension();
                $hero->img = $newImageName;
            }

            /**
             * 3️⃣ Stop if nothing changed
             */
            if (! $hero->isDirty()) {
                DB::rollBack();

                return response()->json([
                    'message' => 'No changes detected'
                ], 200);
            }

            /**
             * 4️⃣ Save DB changes
             */
            $hero->save();

            /**
             * 5️⃣ File system AFTER DB success
             */
            if ($request->hasFile('img')) {
                if ($oldImage && Storage::disk('public')->exists('heros/' . $oldImage)) {
                    Storage::disk('public')->delete('heros/' . $oldImage);
                }

                $request->img->storeAs('heros', $newImageName, 'public');
            }

            DB::commit();

            return response()->json([
                'message' => 'Hero updated successfully'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error($e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Update failed. Changes rolled back.'
            ], 500);
        }
    }
}
