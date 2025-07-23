<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginMail;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string']
        ], [
            'email.required' => 'This field is required',
            'email.string' => 'Invalid input',
            'email.email' => 'Invalid email',
            'password.required' => 'This field is required',
            'password.string' => 'Invalid input'
        ]);

        try 
        {
            if (!Auth::attempt($request->only('email', 'password'), true)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $request->session()->regenerate();

            $user = Auth::user();

            // // Agent to detect browser, platform, etc.
            // $agent = new Agent();
            // $browser = $agent->browser();
            // $platform = $agent->platform();
            // $device = $agent->device();
            // $ip = $request->ip();
            // $date = Carbon::now()->toDayDateTimeString();

            // // Send login mail with metadata
            // Mail::to($user->email)->send(new LoginMail($user, [
            //     'ip' => $ip,
            //     'browser' => $browser,
            //     'platform' => $platform,
            //     'device' => $device,
            //     'time' => $date
            // ]));

            $redirectUrl = match($user->role) {
                'USERS' => '/account',
                'ADMIN' => '/sc-dashboard',
                default => '/login'
            };

            return response()->json([
                'user' => $user,
                'redirect_url' => $redirectUrl,
            ], 200);
            
        }
        catch(Exception $ex)
        {
            Log::error($ex->getMessage());
            return response()->json([
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    // app/Http/Requests/Auth/LoginRequest.php

        public function authenticated(Request $request, $user)
        {
            // Do nothing — this stops it from redirecting
        }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}
