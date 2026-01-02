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
    public function index()
    {
        try {
            $contact = ContactUs::orderBy('id', 'DESC')->paginate(10);
            if ($contact->isEmpty()) {
                return response()->json(['message' => 'No contacts found!'], 404);
            }

            return response()->json($contact, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'contact_us_name' => ['required', 'string'],
            'contact_us_email' => ['required', 'email'],
            'message' => ['required', 'string'],
            'subject' => ['required', 'string']
        ], [
            'contact_us_name.required' => 'This field is required',
            'contact_us_name.string' => 'Invalid inputs',

            'contact_us_email.required' => 'This field is required',
            'contact_us_email.email' => 'Incorrect email',

            'message.required' => 'This field is required',
            'message.string' => 'Invalid inputs',

            'subject.required' => 'This field is required',
            'subject.string' => 'Invalid input'
        ]);

        try {
            DB::beginTransaction();


            ContactUs::create([
                'name' => $request->contact_us_name,
                'email' => $request->contact_us_email,
                'message' => $request->message,
                'subject' => $request->subject,
                'is_read' => false
            ]);

            // send email notification to the user
            Mail::to($request->contact_us_email)->send(new ContactMail($request->contact_us_name));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'We have received your message. Thank you'
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Could not deliver your message. Try again later: ' . $ex->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not deliver your message. Try again later'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $contact = ContactUs::find($id);

            if (!$contact) {
                return response()->json(['message' => 'Contact was not found!'], 404);
            }

            $contact->delete();
            return response()->json(['message' => 'Contact deleted successfully'], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage() . ' on line: ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function markAsRead($id)
    {
        DB::beginTransaction();

        try {
            $contact = ContactUs::find($id);

            if (!$contact) {
                return response()->json(['message' => 'Contact not found!'], 200);
            }

            $contact->is_read = true;
            $contact->save();

            DB::commit();

            return response()->json(['message' => 'Contact marked as read successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . ' on line: ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
