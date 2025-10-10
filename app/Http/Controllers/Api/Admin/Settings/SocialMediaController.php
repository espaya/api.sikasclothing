<?php

namespace App\Http\Controllers\API\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\SocialMedia;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialMediaController extends Controller
{
    public function index()
    {
        try {
            $social = SocialMedia::orderBy('id', 'DESC')->get();

            if (!$social) {
                return response()->json(['message' => 'No social media found'], 400);
            }

            return response()->json($social, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'socials' => ['required', 'array', 'min:1'],
            'socials.*.social_name' => ['required', 'string', 'distinct', 'unique:social_media,social_name'],
            'socials.*.social_url'  => ['required', 'string', 'distinct', 'url', 'unique:social_media,social_url'],
            'socials.*.social_icon' => ['required', 'string', 'distinct', 'unique:social_media,social_icon'],
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->socials as $social) {
                SocialMedia::create([
                    'social_name' => $social['social_name'],
                    'social_url'  => $social['social_url'],
                    'social_icon' => $social['social_icon'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Social Media Added Successfully'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }


    public function destroy($id)
    {
        try {
            $social = SocialMedia::find($id);

            if (!$social) {
                return response()->json(['message' => 'Social media not found'], 404);
            }

            $social->delete();

            return response()->json(['message' => 'Social media deleted successfully'], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
