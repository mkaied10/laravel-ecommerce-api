<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;

class CartController extends Controller
{
    public function __construct()
    {
        if (auth()->check()) {
            $userId = auth()->id();
            Log::debug('Initializing user cart instance:', ['instance' => 'cart_' . $userId, 'user_id' => $userId]);

            Cart::instance('cart_' . $userId);
            try {
                Cart::restore($userId);
                Log::debug('Cart content after restore in constructor:', ['content' => Cart::content()->toArray(), 'user_id' => $userId]);
            } catch (\Exception $e) {
                Log::error('Failed to restore cart in constructor:', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        } else {
            Log::warning('No authenticated user, using default cart instance');
            Cart::instance('default');
            try {
                Cart::restore('default');
                Log::debug('Default cart content after restore in constructor:', ['content' => Cart::content()->toArray()]);
            } catch (\Exception $e) {
                Log::error('Failed to restore default cart in constructor:', ['error' => $e->getMessage()]);
            }
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'quantity' => ['required', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(__('cart.validation_error'), 422, $validator->errors());
            }

            $validated = $validator->validated();
            Log::info('Cart store request:', $validated);

            if (auth()->check()) {
                $userId = auth()->id();
                Cart::instance('cart_' . $userId);
                Log::debug('Ensuring cart instance for store:', ['instance' => 'cart_' . $userId, 'user_id' => $userId]);
            } else {
                Cart::instance('default');
                Log::debug('Using default cart instance for store');
            }

            $product = Product::findOrFail($validated['product_id']);
            Log::info('Product retrieved:', [
                'id' => $product->id,
                'name' => $product->getTranslation('name', app()->getLocale()),
                'price' => $product->price,
                'discounted_price' => $product->discounted_price,
                'quantity' => $product->quantity,
                'price_type' => gettype($product->price),
                'discounted_price_type' => gettype($product->discounted_price),
                'images' => $product->images,
                'images_type' => gettype($product->images)
            ]);

            if ($product->quantity < $validated['quantity']) {
                return ResponseHelper::error(__('cart.insufficient_stock'), 400);
            }

            $price = $product->discounted_price !== null ? (float) $product->discounted_price : (float) $product->price;
            Log::info('Price for cart:', [
                'price' => $price,
                'type' => gettype($price),
                'source' => $product->discounted_price !== null ? 'discounted_price' : 'price'
            ]);

            $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
            Log::info('Processed images:', [
                'images' => $images,
                'type' => gettype($images),
                'first_image' => $images[0] ?? null
            ]);

            $cartItem = Cart::add(
                $product->id,
                $product->getTranslation('name', app()->getLocale()), 
                $validated['quantity'],
                $price,
                [
                    'image' => $images[0] ?? null,
                    'taxRate' => 0
                ]
            );

            Log::info('Cart item added:', [
                'rowId' => $cartItem->rowId,
                'id' => $cartItem->id,
                'name' => $cartItem->name,
                'quantity' => $cartItem->qty,
                'price' => $cartItem->price,
                'subtotal' => $cartItem->subtotal,
                'options' => $cartItem->options
            ]);

            if (auth()->check()) {
                Cart::store(auth()->id());
                Log::debug('Cart stored for user:', ['user_id' => auth()->id()]);
            } else {
                Cart::store('default');
                Log::debug('Cart stored for default instance');
            }

            return ResponseHelper::success(
                __('cart.added'),
                [
                    'cart_item' => $cartItem,
                    'cart_count' => Cart::count(),
                    'cart_total' => Cart::total()
                ],
                201
            );
        } catch (\Exception $e) {
            Log::error('Cart store failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ResponseHelper::error(__('cart.error'), 500, $e->getMessage());
        }
    }

    public function index()
    {
        try {
            if (auth()->check()) {
                $userId = auth()->id();
                Cart::instance('cart_' . $userId);
                Cart::restore($userId);
                Log::debug('Cart content retrieved for user:', ['content' => Cart::content()->toArray(), 'user_id' => $userId]);
            } else {
                Cart::instance('default');
                Cart::restore('default');
                Log::debug('Cart content retrieved for default:', ['content' => Cart::content()->toArray()]);
            }

            $cartItems = Cart::content();
            return ResponseHelper::success(
                __('cart.retrieved'),
                [
                    'items' => $cartItems->values(),
                    'count' => Cart::count(),
                    'total' => Cart::total()
                ],
                200
            );
        } catch (\Exception $e) {
            Log::error('Cart index failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ResponseHelper::error(__('cart.error'), 500, $e->getMessage());
        }
    }

    public function update(Request $request, $rowId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => ['required', 'integer', 'min:1'],
                '_method' => ['sometimes', 'in:PUT'],
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(__('cart.validation_error'), 422, $validator->errors());
            }

            $validated = $validator->validated();
            Log::info('Cart update request:', array_merge($validated, ['rowId' => $rowId]));

            if (auth()->check()) {
                $userId = auth()->id();
                Cart::instance('cart_' . $userId);
                Cart::restore($userId);
                Log::debug('Ensuring cart instance for update:', ['instance' => 'cart_' . $userId, 'user_id' => $userId]);
            } else {
                Cart::instance('default');
                Cart::restore('default');
                Log::debug('Using default cart instance for update');
            }

            Log::debug('Cart content before update:', [
                'content' => Cart::content()->toArray(),
                'rowId' => $rowId,
                'user_id' => auth()->id() ?? 'default'
            ]);

            $cartItem = Cart::get($rowId);
            if (!$cartItem) {
                Log::error('Cart item not found for update:', ['rowId' => $rowId, 'user_id' => auth()->id() ?? 'default']);
                return ResponseHelper::error(__('cart.item_not_found'), 404);
            }

            $product = Product::findOrFail($cartItem->id);

            if ($product->quantity < $validated['quantity']) {
                return ResponseHelper::error(__('cart.insufficient_stock'), 400);
            }

            $price = $product->discounted_price !== null ? (float) $product->discounted_price : (float) $product->price;
            $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;

            Cart::update($rowId, [
                'qty' => $validated['quantity'],
                'price' => $price,
                'name' => $product->getTranslation('name', app()->getLocale()),
                'options' => array_merge($cartItem->options->toArray(), [
                    'image' => $images[0] ?? null,
                    'taxRate' => 0
                ])
            ]);

            $updatedCartItem = Cart::get($rowId);
            Log::info('Cart item updated:', [
                'rowId' => $updatedCartItem->rowId,
                'id' => $updatedCartItem->id,
                'name' => $updatedCartItem->name,
                'quantity' => $updatedCartItem->qty,
                'price' => $updatedCartItem->price,
                'subtotal' => $updatedCartItem->subtotal,
                'options' => $updatedCartItem->options
            ]);

            if (auth()->check()) {
                Cart::store(auth()->id());
                Log::debug('Cart stored for user:', ['user_id' => auth()->id()]);
            } else {
                Cart::store('default');
                Log::debug('Cart stored for default instance');
            }

            return ResponseHelper::success(
                __('cart.updated'),
                [
                    'cart_item' => $updatedCartItem,
                    'cart_count' => Cart::count(),
                    'cart_total' => Cart::total()
                ],
                200
            );
        } catch (\Exception $e) {
            Log::error('Cart update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'rowId' => $rowId]);
            return ResponseHelper::error(__('cart.error'), 500, $e->getMessage());
        }
    }

    public function destroy($rowId)
    {
        try {
            if (auth()->check()) {
                $userId = auth()->id();
                Cart::instance('cart_' . $userId);
                Cart::restore($userId);
                Log::debug('Ensuring cart instance for destroy:', ['instance' => 'cart_' . $userId, 'user_id' => $userId]);
            } else {
                Cart::instance('default');
                Cart::restore('default');
                Log::debug('Using default cart instance for destroy');
            }

            $cartItem = Cart::get($rowId);
            if (!$cartItem) {
                Log::error('Cart item not found for destroy:', ['rowId' => $rowId, 'user_id' => auth()->id() ?? 'default']);
                return ResponseHelper::error(__('cart.item_not_found'), 404);
            }

            Cart::remove($rowId);

            if (auth()->check()) {
                Cart::store(auth()->id());
                Log::debug('Cart stored for user:', ['user_id' => auth()->id()]);
            } else {
                Cart::store('default');
                Log::debug('Cart stored for default instance');
            }

            return ResponseHelper::success(
                __('cart.removed'),
                [
                    'cart_count' => Cart::count(),
                    'cart_total' => Cart::total()
                ],
                200
            );
        } catch (\Exception $e) {
            Log::error('Cart destroy failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'rowId' => $rowId]);
            return ResponseHelper::error(__('cart.error'), 500, $e->getMessage());
        }
    }

    public function clear()
    {
        try {
            if (auth()->check()) {
                $userId = auth()->id();
                Cart::instance('cart_' . $userId);
                Cart::restore($userId);
                Log::debug('Ensuring cart instance for clear:', ['instance' => 'cart_' . $userId, 'user_id' => $userId]);
            } else {
                Cart::instance('default');
                Cart::restore('default');
                Log::debug('Using default cart instance for clear');
            }

            Cart::destroy();

            if (auth()->check()) {
                Cart::store(auth()->id());
                Log::debug('Cart stored for user:', ['user_id' => auth()->id()]);
            } else {
                Cart::store('default');
                Log::debug('Cart stored for default instance');
            }

            return ResponseHelper::success(
                __('cart.cleared'),
                [
                    'cart_count' => Cart::count(),
                    'cart_total' => Cart::total()
                ],
                200
            );
        } catch (\Exception $e) {
            Log::error('Cart clear failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ResponseHelper::error(__('cart.error'), 500, $e->getMessage());
        }
    }
}