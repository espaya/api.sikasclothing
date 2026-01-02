<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    public function index()
    {
        try {
            // Cache menus for 10 minutes (600 seconds)
            $menus = Cache::remember('menus_with_children', 600, function () {
                return Menu::with(['category', 'childrenRecursive'])
                    ->where(function ($q) {
                        $q->whereNull('parent_id')
                            ->orWhere('parent_id', 0);
                    })
                    ->where('is_active', 1)
                    ->orderBy('order')
                    ->get();
            });

            if ($menus->isEmpty()) {
                return response()->json([
                    'message' => 'No menu found!'
                ], 404);
            }

            return response()->json([
                'data' => $menus
            ]);
        } catch (\Exception $ex) {
            Log::error('Error fetching menus: ' . $ex->getMessage());
            return response()->json([
                'message' => 'Error fetching menus. Try again later.'
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'source_type' => 'required|string|in:category,custom',
            'source_id' => 'nullable|required_if:source_type,category|integer',
            'custom_url' => [
                'nullable',
                'required_if:source_type,custom',
                'regex:/^(#|https?:\/\/(localhost(:[0-9]{1,5})?|[\w.-]+\.[a-z]{2,})(\/[\w\/.#?=&%-]*)?)$/i'
            ],
            'location' => 'required|string|in:topbar,main,footer',
            'order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
            'child_type' => [
                'sometimes', // Only validate if present
                function ($attribute, $value, $fail) use ($request) {
                    if (!empty($request->children) && !$value) {
                        $fail('Child type is required when you add children.');
                    }
                    if ($value && !in_array($value, ['dropdown', 'mega'])) {
                        $fail('Invalid child type selected.');
                    }
                }
            ],
            'children' => 'nullable|array',
            'children.*.title' => 'required|string|max:255',
            'children.*.source_type' => 'required|string|in:category,custom',
            'children.*.source_id' => 'nullable|integer',
            'children.*.custom_url' => [
                'nullable',
                'regex:/^(#|https?:\/\/(localhost(:[0-9]{1,5})?|[\w.-]+\.[a-z]{2,})(\/[\w\/.#?=&%-]*)?)$/i'
            ],
        ]);

        // Save parent menu
        $menu = Menu::create([
            'title'       => $validated['title'],
            'source_type' => $validated['source_type'],
            'source_id'   => $validated['source_id'] ?? null,
            'custom_url'  => $validated['custom_url'] ?? null,
            'location'    => $validated['location'],
            'order'       => $validated['order'],
            'is_active'   => $validated['is_active'],
            'child_type'  => $validated['child_type'] ?? null,
        ]);

        // Save children if any
        if (!empty($validated['children'])) {
            foreach ($validated['children'] as $child) {
                $menu->children()->create([
                    'title'       => $child['title'],
                    'source_type' => $child['source_type'],
                    'source_id'   => $child['source_id'] ?? null,
                    'custom_url'  => $child['custom_url'] ?? null,
                    'location'    => $validated['location'], // take from parent
                    'is_active'   => $child['is_active'] ?? $validated['is_active'], // optional: inherit active status too
                ]);
            }
        }

        Cache::forget('menus_with_children'); // Clear cache

        return response()->json([
            'message' => 'Menu created successfully!',
            'data'    => $menu->load('children')
        ]);
    }

    public function view($id)
    {
        try {
            $menu = Menu::where('id', $id)->first();
            if (!$menu) {
                return response()->json(['message' => 'Menu not found'], 404);
            }
            return response()->json($menu, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage() . ' on line: ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function destroy($id)
    {
        try {

            $menu = Menu::find($id);

            if (!$menu) {
                return response()->json(['message' => 'Menu not found'], 404);
            }

            $menu->delete();
            Cache::forget('menus_with_children'); // Clear cache

            return response()->json(['message' => 'Menu deleted successfully'], 200);
        } catch (Exception $ex) {
            Log::error('Error deleting menu: ' . $ex->getMessage() . ' on line: ' . $ex->getLine());
            return response()->json(['message' => 'Error deleting menu. Try again'], 500);
        }
    }
}
