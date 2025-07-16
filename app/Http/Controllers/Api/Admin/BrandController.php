<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class BrandController extends Controller
{
    public function index()
    {
        try {

            $brandOptions = Brand::orderBy('name', 'DESC')->get();

            if ($brandOptions) {
                return response()->json($brandOptions);
            } else {
                return response()->json(['message' => 'No brands found, go to the brands page to add some']);
            }
        } catch (Exception $ex) {
            Log::error('error fetching brand options: ' . $ex->getMessage());
            return response()->json(['message' => 'Error fetching brands']);
        }
    }

    public function store(Request $request)
    {
        Log::error($request->all());

        $request->validate([
            'name' => ['required', 'string'],
            'logo' => ['required', 'mimes:jpg,png,webp,jpeg'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:Active,Inactive'],
            'is_featured' => ['nullable'],
            'website' => ['nullable', 'url'],
        ], [
            'name.required' => 'This field is required',
            'name.string' => 'Invalid input',
            'logo.required' => 'This field is required',
            'logo.mimes' => 'Invalid file type',
            'description.string' => 'Invalid input',
            'status.required' => 'This field is required',
            'status.in' => 'Only "Active" and "Inactive" are allowed',
            'is_featured' => ''
        ]);

        DB::beginTransaction();

        try {
            $logoPath = null;

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');

                $uploadDir = 'brands';

                // Ensure the directory exists in the public disk
                if (!Storage::disk('public')->exists($uploadDir)) {
                    Storage::disk('public')->makeDirectory($uploadDir, 0755, true);
                }

                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension(); // Unique filename
                $file->move($uploadDir, $filename); // Store the file
                $logoPath = 'uploads/brands/' . $filename;
            }

            Brand::create([
                'name' => trim($request->name),
                'logo' => $logoPath,
                'description' => trim($request->description),
                'status' => trim($request->status),
                'is_featured' => $request->is_featured ? 1 : 0,
                'website' => trim($request->website),
            ]);

            DB::commit();

            return response()->json(['message' => 'Brand Added Successfully'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error saving brand: ' . $ex->getMessage());
            return response()->json(['message' => 'Error saving brand'], 500);
        }
    }
}
