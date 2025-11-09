<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginMail;
use App\Models\Products;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;



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

        try {
            if (!Auth::attempt($request->only('email', 'password'), true)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $request->session()->regenerate();

            $user = Auth::user();

            // $user->tokens()->delete();

            // $token = $user->createToken('web-app')->plainTextToken;

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

            $redirectUrl = match ($user->role) {
                'USERS' => '/account',
                'ADMIN' => '/sc-dashboard',
                default => '/login'
            };

            return response()->json([
                'token' => $user,
                'redirect_url' => $redirectUrl,
            ], 200);
        } catch (Exception $ex) {
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

        try {
            // sync guest session cart to database
            $guestCart = Session::get('cart', []);

            if (!empty($guestCart)) {
                foreach ($guestCart as $item) {
                    $product = Products::find($item['product_id']);
                    if (!$product) continue;

                    $existing = $user->cartItems()->where('product_id', $product->id)->first();

                    if ($existing) {
                        // merge quantities
                        $existing->quantity += $item['quantity'];
                        $existing->save();
                    } else {
                        // Add new item
                        $user->cartItems()->create([
                            'product_id' => $product->id,
                            'quantity' => $item['quantity'],
                            'price' => $product->price,
                            'size' => $item['size'] ?? null,
                            'color' => $item['color'] ?? null,
                            'user_id' => $user->id,
                        ]);
                    }
                }

                // clear quest cart from session
                Session::forget('cart');

                // clear user cache for cart count if you use one
                Cache::forget("cart_user_$user->id");
            }

            // Determine redirect based on user role
            $redirectUrl = match ($user->role) {
                'USERS' => '/account',
                'ADMIN' => 'sc-dashboard',
                default => '/login'
            };

            return response()->json([
                'token' => $user,
                'redirect_url' => $redirectUrl
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred while syncing your cart'], 500);
        }
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        $guard = Auth::getDefaultDriver();

        // If the user is logged in via session (web guard)
        if ($guard === 'web') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // If logged in via API token (Sanctum tokens)
        if ($request->user() && $request->user()->currentAccessToken() instanceof PersonalAccessToken) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }
}
