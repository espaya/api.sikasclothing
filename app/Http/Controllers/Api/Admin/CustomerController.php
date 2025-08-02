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

            $query = User::where('role', 'USER')->orderBy('id', 'DESC')->get();

            if ($request->search) {
                $search = trim($request->search);
                $query->where('name', '=', "%$search")
                    ->orWhere('email', '=', "%$search%")
                    ->orderBy('name', 'ASC')
                    ->paginate(10);
            }

            return response()->json($query);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'Could not get customers. Try again later'], 500);
        }
    }
}
