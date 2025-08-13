<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;
use App\Models\Hero;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HeroController extends Controller
{
    public function index()
    {
        $heros = Hero::orderBy('id', 'DESC')->get();

        if (!$heros) {
            return response()->json(['message' => 'Heros not found!']);
        }

        return response()->json($heros);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string'],
            'subtitle' => ['required', 'string'],
            'text' => ['required', 'string'],
            'img' => ['required', 'mimes:png,jpg,jpeg,webp'],
            'btn_text' => ['required', 'string'],
            'btn_link' => [
                'required',
                'string',
                "url"
            ]
        ], [
            'title.required' => 'This field is required',
            'title.string' => 'Invalid input',
            'subtitle.required' => 'This field is required',
            'subtitle.string' => 'Invalid input',
            'text.required' => 'This field is required',
            'text.string' => 'INvalid input',
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

            return response()->json(['message' => 'Hero slider created successfully']);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An error occurred, try again later'], 500);
        }
    }
}
