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
    public function index(Request $request)
    {
        try {

            $perPage = $request->get('per_page', 10);

            $brands = Brand::orderBy('name', 'DESC')->paginate($perPage);

            return response()->json($brands);
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
        ]);

        DB::beginTransaction();

        try {
            $logoPath = null;
            $logoFile = null;

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoName = time() . '.' . $logo->getClientOriginalExtension();
                $logoFile = $logo; // hold temporarily for now
            }

            // generate slug
            $slug = Str::slug(trim($request->name));

            $brand = Brand::create([
                'name' => trim($request->name),
                'logo' => '',
                'description' => trim($request->description),
                'status' => trim($request->status),
                'is_featured' => $request->has('is_featured') ? 1 : 0,
                'website' => trim($request->website),
                'slug' => $slug
            ]);

            if($logoFile)
            {
                $directory = 'brands';
                $logoPath = $logoFile->storeAs($directory, $logoName, 'public');
                $brand->update(['logo' => $logoPath]);
            }


            DB::commit();

            return response()->json(['message' => 'Brand Added Successfully'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Error saving brand: ' . $ex->getMessage());
            return response()->json(['message' => 'Error saving brand'], 500);
        }
    }
}
