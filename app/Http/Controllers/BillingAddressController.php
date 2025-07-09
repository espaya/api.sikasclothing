<?php

namespace App\Http\Controllers;

use App\Models\BillingAddress;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingAddressController extends Controller
{
    public function billingAddress()
    {
        try {
            $user = User::with('billingAddress')->find(Auth::id());
            return response()->json($user);
        } catch (Exception $ex) {
            Log::error('Error getting billing address: ' . $ex->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'firstname' => ['required', 'string'], // 🔁 fixed typo: firstnane → firstname
            'lastname' => ['required', 'string'],
            'company_name' => ['nullable', 'string'], // 🔁 fixed typo: comapny_name → company_name
            'country' => ['required', 'string'],
            'address_line_1' => ['required', 'string'],
            'address_line_2' => ['nullable', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'zip' => ['required', 'string'],
            'default' => ['nullable', 'boolean'], // 🔁 fixed typo: nullabe → nullable
            'phone' => ['required', 'string', 'regex:/^(\+?\d{1,4}[\s-]?)?(\d{7,15})$/'],
            'email' => ['required', 'email'],
        ], [
            'firstname.required' => 'This field is required',
            'firstname.string' => 'Invalid inputs',
            'lastname.required' => 'This field is required',
            'lastname.string' => 'Invalid input',
            'company_name.string' => 'Invalid input',
            'country.required' => 'This field is required',
            'country.string' => 'Invalid inputs',
            'address_line_1.required' => 'This field is required',
            'address_line_1.string' => 'Invalid inputs',
            'address_line_2.string' => 'Invalid inputs',
            'city.required' => 'This field is required',
            'city.string' => 'Invalid inputs',
            'state.required' => 'Invalid inputs',
            'state.string' => 'Invalid inputs',
            'zip.required' => 'This field is required',
            'zip.string' => 'Invalid input',
            'default.boolean' => 'Invalid input',
            'phone.required' => 'This field is required',
            'phone.string' => 'Invalid inputs',
            'phone.regex' => 'Invalid phone number format',
            'email.required' => 'This field is required',
            'email.email' => 'Invalid input',
        ]);

        $userID = Auth::id();

        if (!$userID) {
            return response()->json([
                'message' => 'User not found! Please sign in to your account again.',
                'redirect_url' => '/login'
            ], 401);
        }

        try {
            // Cleaned and sanitized data
            $data = [
                'firstname'       => htmlspecialchars(trim($request->firstname), ENT_QUOTES, 'UTF-8'),
                'lastname'        => htmlspecialchars(trim($request->lastname), ENT_QUOTES, 'UTF-8'),
                'company_name'    => htmlspecialchars(trim($request->company_name ?? ''), ENT_QUOTES, 'UTF-8'),
                'country'         => htmlspecialchars(trim($request->country), ENT_QUOTES, 'UTF-8'),
                'address_line_1'  => htmlspecialchars(trim($request->address_line_1), ENT_QUOTES, 'UTF-8'),
                'address_line_2'  => htmlspecialchars(trim($request->address_line_2 ?? ''), ENT_QUOTES, 'UTF-8'),
                'city'            => htmlspecialchars(trim($request->city), ENT_QUOTES, 'UTF-8'),
                'state'           => htmlspecialchars(trim($request->state), ENT_QUOTES, 'UTF-8'),
                'zip'             => htmlspecialchars(trim($request->zip), ENT_QUOTES, 'UTF-8'),
                'phone'           => htmlspecialchars(trim($request->phone), ENT_QUOTES, 'UTF-8'),
                'email'           => htmlspecialchars(trim($request->email), ENT_QUOTES, 'UTF-8'),
                'default'         => $request->default, // 🧠 converts checkbox to true/false
            ];

            // Save or update billing address
            BillingAddress::updateOrCreate(
                ['userID' => $userID],
                $data
            );

            return response()->json(['message' => 'Billing address saved successfully.'], 200);
        } catch (\Exception $ex) {
            Log::error('An error occurred whilst saving your billing address: ' . $ex->getMessage());

            return response()->json([
                'message' => 'An error occurred whilst saving your billing address.'
            ], 500);
        }
    }
}
