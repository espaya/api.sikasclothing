<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriberController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:subscriber,email'],
        ], [
            'email.required' => 'Please enter your email',
            'email.email' => 'Invalid email',
            'email.unique' => 'This email already exists in our mailing list'
        ]);

        DB::beginTransaction();

        try {
            Subscriber::create([
                'email' => trim($request->email),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Email successfully added to our newsletter'
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An error occurred, try again later'], 500);
        }
    }
}
