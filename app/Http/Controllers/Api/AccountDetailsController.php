<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountDetails;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AccountDetailsController extends Controller
{
    public function store(Request $request)
    {
        $id = Auth::id();

        $request->validate([
            'firstname' => ['required', 'string'],
            'lastname' => ['required', 'string'],
            'display_name' => ['nullable', 'string'],
            'current_password' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !Hash::check($value, Auth::user()->password)) {
                        $fail('Current password is incorrect.');
                    }
                },
            ],
            'new_password' => ['nullable', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'],
            'confirm_password' => ['nullable', 'same:new_password'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($id)],
        ], [
            'firstname.required' => 'This field is required',
            'firstname.string' => 'Invalid input',
            'lastname.required' => 'This field is required',
            'lastname.string' => 'Invalid input',
            'display_name.string' => 'Invalid input',
            'new_password.regex' => 'Password must be at least 8 characters long, include one uppercase letter, one number, and one special character',
            'confirm_password.same' => 'Passwords do not match',
            'email.email' => 'Email is incorrect',
            'email.unique' => 'This email already exists',
        ]);

        try {
            DB::beginTransaction();

            $firstname = trim($request->firstname);
            $lastname = trim($request->lastname);
            $display_name = trim($request->display_name ?? '');
            $email = trim($request->email ?? '');
            $newPassword = trim($request->new_password ?? '');

            // Update account details
            AccountDetails::updateOrCreate(
                ['userID' => $id],
                [
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'display_name' => $display_name,
                ]
            );

            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found!'], 404);
            }

            // Update email if changed
            if (!empty($email)) {
                $user->email = $email;
            }

            // Update password if provided
            if (!empty($newPassword)) {
                $user->password = Hash::make($newPassword);
            }

            if ($user->isDirty()) {
                $user->save();
            }

            DB::commit();

            return response()->json(['message' => 'Account details saved successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error("An error occurred whilst saving your details: " . $ex->getMessage());

            return response()->json([
                'message' => 'An error occurred whilst saving your details',
            ], 500);
        }
    }


    public function getAccountDetails()
    {
        try {
            $user = User::with('accountDetails')->find(Auth::id());

            if (!$user) {
                return response()->json(['message' => 'User Not Found!'], 404);
            }

            return response()->json($user);
        } catch (Exception $ex) {
            Log::error('An error occurred whilst getting your account details: ' . $ex->getMessage());
            return response()->json(['message' => 'An error occurred whilst getting your account details']);
        }
    }
}
