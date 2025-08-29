<?php

namespace App\Http\Controllers;

use App\Models\ShippingAddress;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShippingAddressController extends Controller
{
    public function shippingAddress()
    {
        try {
            $user = ShippingAddress::where('userID', Auth::id())->first();
            return response()->json($user);
        } catch (Exception $ex) {
            Log::error('Error getting billing address: ' . $ex->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'firstname' => ['required', 'string'],
            'lastname' => ['required', 'string'],
            'company_name' => ['nullable', 'string'],
            'country' => ['required', 'string'],
            'address_line_1' => ['required', 'string'],
            'address_line_2' => ['nullable', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'zip' => ['required', 'string'],
            'is_checkout_only' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'id' => ['nullable', 'integer']
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
        ]);

        $userID = Auth::id();

        if (!$userID) {
            return response()->json([
                'message' => 'User not found! Please sign in to your account again.',
                'redirect_url' => '/login'
            ], 401); // Use 401 for unauthorized
        }

        try {
            DB::beginTransaction();

            // Sanitize and prepare data
            $data = [
                'firstname'         => htmlspecialchars(trim($request->firstname), ENT_QUOTES, 'UTF-8'),
                'lastname'          => htmlspecialchars(trim($request->lastname), ENT_QUOTES, 'UTF-8'),
                'company_name'      => htmlspecialchars(trim($request->company_name ?? ''), ENT_QUOTES, 'UTF-8'),
                'country'           => htmlspecialchars(trim($request->country), ENT_QUOTES, 'UTF-8'),
                'address_line_1'    => htmlspecialchars(trim($request->address_line_1), ENT_QUOTES, 'UTF-8'),
                'address_line_2'    => htmlspecialchars(trim($request->address_line_2 ?? ''), ENT_QUOTES, 'UTF-8'),
                'city'              => htmlspecialchars(trim($request->city), ENT_QUOTES, 'UTF-8'),
                'state'             => htmlspecialchars(trim($request->state), ENT_QUOTES, 'UTF-8'),
                'zip'               => htmlspecialchars(trim($request->zip), ENT_QUOTES, 'UTF-8'),
                'is_checkout_only'  => $request->boolean('is_checkout_only', false),
                'is_default'        => $request->boolean('is_default', false),
                'userID'            => $userID,
            ];

            // Check if this is being saved as default, unset otheres
            if ($data['is_default']) {
                ShippingAddress::where('userID', $userID)->update(['is_default' => false]);
            }

            if ($request->id) {
                $address = ShippingAddress::where('id', $request->id)->where('userID', $userID)->firstOrFail();

                // update new data
                $address->fill($data);
                if ($address->isDirty()) $address->save();
            } else {
                ShippingAddress::create($data);
            }

            DB::commit();

            return response()->json([
                'message' => $data['is_checkout_only']
                    ? 'Temporary shipping address added for this order'
                    : 'Shipping address updated successfully.'
            ], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('An error occurred whilst saving shipping address: ' . $ex->getMessage());

            return response()->json([
                'message' => 'An error occurred whilst saving the shipping address.'
            ], 500);
        }
    }
}
