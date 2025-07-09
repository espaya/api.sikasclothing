<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => [
                'required', 
                'string', 
                'min:4', 
                'max:15', 
                'regex:/^[a-zA-Z0-9]+$/', 
                'unique:users,name',
                'not_in:admin,admins,administrator,administrators,author,authors,contributor,contributors,editor,editors,sika,sikascloth,sikasclothing,sikasclothings'
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'username.required' => 'This field is required',
            'username.string' => 'This field contains invalid characters',
            'username.min' => 'Username is too short',
            'username.unique' => 'Username already exists',
            'username.max' => 'Username is too long',
            'username.not_in' => 'This user name is not allowed',
            'max.regex' => 'Username must contain only letters and/or numbers',
            'email.required' => 'This field is required',
            'email.string' => 'This field contains invalid characters',
            'email.lowercase' => 'Only lowercases are allowed',
            'email.email' => 'Invalid email format',
            'email.max' => 'Input is too long',
            'email.unique' => 'This email already exists',
            'password.required' => 'This field is required',
            'password.confirmed' => 'Please confirm the password',
        ]);

        try 
        {
            $user = User::create([
                'name' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            event(new Registered($user));

            // Auth::login($user);
            // return redirect()->intended(route('dashboard', absolute: false));

            // send email confirmation to user
            Mail::to($user->email)->send(new WelcomeMail($user));

            return response()->json([
                'message' => 'Registration successful, you can now sign in to your account'
            ], 200);
        }
        catch(Exception $ex)
        {
            Log::error('An error occurred whilst registering: ' . $ex->getMessage());
            return response()->json([
                'message' => 'An error occurred whilst registering'
            ], 500);
        }
    }
}
