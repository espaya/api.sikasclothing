<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Products;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            if ($user) {
                $cartItems = $user->cartItems()->with('product')->orderBy('id', 'DESC')->get();
            } else {
                $cartItems = collect(session('cart', []));
            }

            return response()->json([
                'cartItems' => $cartItems,
                'total' => $this->calculateTotal($cartItems)
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json([
                'message' => 'Could not get cart items. Try again later'
            ], 500);
        }
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:product,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'size' => ['required', 'array', 'min:1'],
            'size.*' => ['string'],
            'color' => ['required', 'array', 'min:1'],
            'color.*' => [
                'string',
                'regex:/^(#(?:[0-9a-fA-F]{3}){1,2}$)|^rgb\((\s*\d+\s*,){2}\s*\d+\s*\)$|^hsl\((\s*\d+\s*,\s*\d+%?,\s*\d+%?)\)$/i'
            ],

        ], [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Product was not found',
            'quantity.required' => 'Product quantity is required',
            'quantity.integer' => 'Select the correct quantity',
            'quantity.min' => 'Quantity should be at least one(1)',
            'size.required' => 'Product size is required',
            'size.string' => 'Select the correct size',
            'size.min' => 'Select at lease one(1) quantity',
            'color.required' => 'Product color is required',
            'color.string' => 'Select the correct color',
            'color.min' => 'Select at least one(1) color'
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            $product = Products::find($request->product_id);

            if ($user) {
                // Check if item already in cart
                $cartItem = $user->cartItems()->where('product_id', $product->id)->first();

                if ($cartItem) {
                    $cartItem->quantity += $request->quantity;
                    $cartItem->save();
                }

                $cartItem = $user->cartItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $product->price,
                    'size' => $product->size,
                    'color' => $product->color,
                    'user_id' => $user->id,
                ]);
            }

            // guest cart stored in session
            $cart = session()->get('cart', []);

            $cart[] = [
                'product_id' => $product->id,
                'price' => $product->price,
                'quantity' => $product->quantity,
                'size' => $product->size,
                'color' => $product->color,
            ];

            session()->put('cart', $cart);

            DB::commit();

            return $this->index($request);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'Could not add product to cart'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info("Quantity: " . $request->quantity);
        $request->validate([
            'product_id' => ['required', 'exists:product,id'], // Fixed table name (assuming 'products')
            'quantity' => ['required', 'integer', 'min:1'],
            'size' => ['required', 'array', 'min:1'],
            'size.*' => ['string'],
            'color' => ['required', 'array', 'min:1'],
            'color.*' => [
                'string',
                'regex:/^(#(?:[0-9a-fA-F]{3}){1,2}$)|^rgb\((\s*\d+\s*,){2}\s*\d+\s*\)$|^hsl\((\s*\d+\s*,\s*\d+%?,\s*\d+%?)\)$/i'
            ],
        ], [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Product was not found',
            'quantity.required' => 'Product quantity is required',
            'quantity.integer' => 'Select the correct quantity',
            'quantity.min' => 'Quantity should be at least one(1)',
            'size.required' => 'Product size is required',
            'size.array' => 'Select the correct size format',
            'size.min' => 'Select at least one(1) size',
            'color.required' => 'Product color is required',
            'color.array' => 'Select the correct color format',
            'color.min' => 'Select at least one(1) color'
        ]);

        if (Auth::check()) {
            $user = Auth::user();

            DB::beginTransaction();
            try {
                $cartItem = $user->cartItems()->where("product_id", $id)->first();

                if (!$cartItem) {
                    DB::rollBack();
                    return response()->json(['message' => 'Item not found'], 404);
                }

                // Only update changed fields
                $updates = [];
                if ($cartItem->quantity != $request->quantity) {
                    $updates['quantity'] = $request->quantity;
                }
                if ($cartItem->size != $request->size) {
                    $updates['size'] = json_encode($request->size);
                }
                if ($cartItem->color != $request->color) {
                    $updates['color'] = json_encode($request->color);
                }

                if (!empty($updates)) {
                    $cartItem->update($updates);
                    DB::commit();
                    return response()->json([
                        'message' => 'Cart updated successfully',
                        'cart' => $this->index($request)->getData()
                    ]);
                }

                return response()->json(['message' => 'No changes detected'], 200);
            } catch (ModelNotFoundException $ex) {
                DB::rollBack();
                Log::error('Error: ' . $ex->getMessage() . ' on line: ' . $ex->getLine());
                return response()->json(['message' => 'Item not found'], 404);
            } catch (Exception $ex) {
                DB::rollBack();
                Log::error('Error: ' . $ex->getMessage() . ' on line: ' . $ex->getLine());
                return response()->json(['message' => 'Could not update cart'], 500);
            }
        } else {
            $cart = session()->get('cart', []);

            if (!isset($cart[$id])) {
                return response()->json(['message' => 'Item not found in cart'], 404);
            }

            // Check for actual changes before updating session
            $hasChanges = false;
            if ($cart[$id]['quantity'] != $request->quantity) {
                $cart[$id]['quantity'] = $request->quantity;
                $hasChanges = true;
            }
            if ($cart[$id]['size'] != $request->size) {
                $cart[$id]['size'] = $request->size;
                $hasChanges = true;
            }
            if ($cart[$id]['color'] != $request->color) {
                $cart[$id]['color'] = $request->color;
                $hasChanges = true;
            }

            if ($hasChanges) {
                session()->put('cart', $cart);
                return response()->json([
                    'message' => 'Cart updated',
                    'cart' => $cart
                ]);
            }

            return response()->json(['message' => 'No changes detected'], 200);
        }
    }


    public function updateQuantity(Request $request, $id)
    {
        Log::info($id);
        Log::info($request->all());

        // Manually validate to catch and format errors
        $request->validate([
            'product_id' => ['required', 'exists:product,id'], // Assuming table is "products"
            'quantity' => ['required', 'integer', 'min:1'],
        ], [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Product was not found',
            'quantity.required' => 'Product quantity is required',
            'quantity.integer' => 'Select the correct quantity',
            'quantity.min' => 'Quantity should be at least one(1)',
        ]);

        try {
            if (Auth::check()) {
                $user = Auth::user();

                DB::beginTransaction();

                $cartItem = $user->cartItems()->find($id); // Get user's specific cart item

                if (!$cartItem) {
                    return response()->json([
                        'message' => 'Item not found',
                        'errors' => [
                            'product_id' => ['Item was not found']
                        ]
                    ], 404);
                }

                $updates = [];
                if ((int)$cartItem->quantity !== (int)$request->quantity) {
                    $updates['quantity'] = $request->quantity;
                }

                if (!empty($updates)) {
                    $cartItem->update($updates);
                    DB::commit();

                    return response()->json([
                        'message' => 'Cart updated successfully',
                        'cart' => $this->index($request)->getData()
                    ], 200);
                }

                return response()->json(['message' => 'No changes detected'], 200);
            } else {
                // Guest user (session-based cart)
                $cart = session()->get('cart', []);

                if (!isset($cart[$id])) {
                    return response()->json([
                        'message' => 'Item not found in cart',
                        'errors' => [
                            'product_id' => ['Item not found in cart']
                        ]
                    ], 404);
                }

                $hasChanges = false;
                if ((int)$cart[$id]['quantity'] !== (int)$request->quantity) {
                    $cart[$id]['quantity'] = $request->quantity;
                    $hasChanges = true;
                }

                if ($hasChanges) {
                    session()->put('cart', $cart);
                    return response()->json([
                        'message' => 'Cart updated',
                        'cart' => $cart
                    ]);
                }

                return response()->json(['message' => 'No changes detected'], 200);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $ex) {
            Log::error('Cart item not found: ' . $ex->getMessage());
            return response()->json([
                'message' => 'Item not found',
                'errors' => [
                    'product_id' => ['Item was not found']
                ]
            ], 404);
        } catch (\Exception $ex) {
            Log::error('Update cart error: ' . $ex->getMessage());
            return response()->json([
                'message' => 'Could not update cart',
                'errors' => [
                    'general' => ['An unexpected error occurred']
                ]
            ], 500);
        }
    }


    public function itemExists($id)
    {
        // Validate the ID is numeric (optional)
        if (!is_numeric($id)) {
            return response()->json(false);
        }

        if (Auth::check()) {
            // Authenticated user - check database
            $exists = Auth::user()->cartItems()->where('product_id', $id)->exists();
            return response()->json($exists);
        }

        // Guest user - check session
        $cart = session()->get('cart', []);
        $exists = isset($cart[$id]);
        return response()->json($exists);
    }

    public function remove(Request $request, $id)
    {
        if (Auth::check()) {
            $user = Auth::user();

            try {
                $item = $user->cartItems()->find($id);
                if ($item) {
                    $item->delete();
                }
                return $this->index($request);
            } catch (Exception $ex) {
                Log::error($ex->getMessage());
                return response()->json(['message' => 'Could not remove item from cart'], 500);
            }
        } else {
            $cart = session()->get('cart', []);
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put('cart', $cart);
            }
            return response()->json(['message' => 'Item removed from cart', 'cart' => $cart]);
        }
    }

    public function clear()
    {
        if (Auth::check()) {
            $user = Auth::user();

            DB::beginTransaction();
            try {
                $user->cartItems()->delete();
                DB::commit();
                return response()->json(['message' => 'Cart cleared'], 200);
            } catch (Exception $ex) {
                DB::rollBack();
                Log::error($ex->getMessage());
                return response()->json(['message' => 'Could not clear cart'], 500);
            }
        } else {
            session()->forget('cart');
            return response()->json(['message' => 'Guest cart cleared'], 200);
        }
    }

    private function calculateTotal($cartItems)
    {
        return $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    public function cartTotal()
    {
        $user = Auth::user();

        if ($user) {
            $cartTotal = Cart::where('user_id', $user->id)->count();
        } else {
            $sessionCart = session()->get('cart', []);
            $cartTotal = count($sessionCart);
        }

        return response()->json($cartTotal);
    }

    public function getCart($id)
    {
        try {
            // Authenticated users
            if (Auth::check()) {
                $cartItems = Cart::where('product_id', $id)->get();

                if ($cartItems->isEmpty()) {
                    return response()->json(['message' => 'No items in cart'], 404);
                }

                return response()->json($cartItems);
            } else {
                // Guest users - get full cart from session
                $cart = session()->get('cart', []);

                if (empty($cart)) {
                    return response()->json(['message' => 'No items in cart'], 404);
                }

                return response()->json(array_values($cart)); // return as array, reindex numerically
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . ' on line:' . $ex->getLine());
            return response()->json(['message' => 'An error occurred. Try again later'], 500);
        }
    }
}
