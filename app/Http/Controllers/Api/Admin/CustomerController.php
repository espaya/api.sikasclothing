<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Start query builder, don't call get() or paginate() yet
            $query = User::where('role', 'USER');

            if ($request->search) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%")
                        ->orWhere('email', 'LIKE', "%$search%");
                });
            }

            // Order and paginate
            $users = $query->orderBy('name', 'ASC')->paginate(10);

            return response()->json($users);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'Could not get customers. Try again later'], 500);
        }
    }
}
