<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Models\ContactUs;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'contact_us_name' => ['required', 'string'],
            'contact_us_email' => ['required', 'email'],
            'message' => ['nullable', 'string']
        ], [
            'contact_us_name.required' => 'This field is required',
            'contact_us_name.string' => 'Invalid inputs',

            'contact_us_email.required' => 'This field is required',
            'contact_us_email.email' => 'Incorrect email',

            'message.string' => 'Invalid inputs'
        ]);

        try 
        {
            DB::beginTransaction();

            $name = htmlspecialchars(trim($request->contact_us_name), ENT_QUOTES, 'utf-8');
            $email = htmlspecialchars(trim($request->contact_us_email), ENT_QUOTES, 'utf-8');
            $message = htmlspecialchars(trim($request->message), ENT_QUOTES, 'utf-8');

            ContactUs::create([
                'name' => $name,
                'email' => $email,
                'message' => $message
            ]);

            // send email notification to the user
            Mail::to($email)->send(new ContactMail($name));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'We have received your message. Thank you'
            ], 200);

        }
        catch(Exception $ex)
        {
            DB::rollBack();
            Log::error('Could not deliver your message. Try again later: ' . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not deliver your message. Try again later'
            ], 500);
        }
    }
}
