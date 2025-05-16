<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected $paypalClient;

    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $this->paypalClient = new PayPalHttpClient(
            new SandboxEnvironment(
                env('PAYPAL_CLIENT_ID'),
                env('PAYPAL_CLIENT_SECRET')
            )
        );
    }

    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'delivery_address.city_name' => ['required', 'string', 'max:255'],
            'delivery_address.address_name' => ['required', 'string', 'max:255'],
            'delivery_address.building_number' => ['required', 'string', 'max:50'],
            'payment_method' => ['required', 'in:stripe,paypal'],
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(__('order.validation_error'), 422, $validator->errors());
        }

        $validated = $validator->validated();
        Log::info('Order store request received:', $validated);

        if (auth()->check()) {
            $userId = auth()->id();
            Cart::instance('cart_' . $userId);
            Cart::restore($userId);
            Log::debug('Ensuring cart instance for order:', ['instance' => 'cart_' . $userId, 'user_id' => $userId]);
        } else {
            Cart::instance('default');
            Cart::restore('default');
            Log::debug('Using default cart instance for order');
        }

        $cartItems = Cart::content();
        if ($cartItems->isEmpty()) {
            return ResponseHelper::error(__('order.cart_empty'), 400);
        }

        $orderNumber = 'ORD-' . Str::uuid()->toString();

        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => auth()->id() ?? null,
            'delivery_address' => [
                'city_name' => $validated['delivery_address']['city_name'],
                'address_name' => $validated['delivery_address']['address_name'],
                'building_number' => $validated['delivery_address']['building_number'],
            ],
            'order_status' => [
                'en' => 'pending',
                'ar' => 'معلق',
            ],
            'payment_method' => $validated['payment_method'],
            'payment_status' => [
                'en' => 'not_paid',
                'ar' => 'غير مدفوع',
            ],
            'total_amount' => (float) str_replace(',', '', Cart::total()),
        ]);

        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->id,
                'quantity' => $item->qty,
                'price' => $item->price,
                'image' => $item->options->image,
            ]);
        }

        Log::info('Order created successfully:', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'items_count' => $cartItems->count(),
            'total_amount' => $order->total_amount,
        ]);

        $paymentUrl = $this->processPayment($order, $validated['payment_method']);

        Cart::destroy();
        if (auth()->check()) {
            Cart::store(auth()->id());
        } else {
            Cart::store('default');
        }

        return ResponseHelper::success(
            __('order.created'),
            [
                'order' => new OrderResource($order),
                'payment_url' => $paymentUrl,
            ],
            201
        );
    } catch (\Exception $e) {
        Log::error('Order creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return ResponseHelper::error(__('order.error'), 500, $e->getMessage());
    }
}

    public function index()
    {
        try {
            $orders = auth()->check()
                ? Order::where('user_id', auth()->id())->get()
                : Order::whereNull('user_id')->get();

            Log::debug('Orders retrieved for user:', [
                'user_id' => auth()->id() ?? 'guest',
                'orders_count' => $orders->count(),
            ]);

            return ResponseHelper::success(
                __('order.retrieved'),
                OrderResource::collection($orders),
                200
            );
        } catch (\Exception $e) {
            Log::error('Order retrieval failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ResponseHelper::error(__('order.error'), 500, $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $order = Order::where('id', $id)
                ->when(auth()->check(), fn($query) => $query->where('user_id', auth()->id()))
                ->firstOrFail();

            Log::debug('Order retrieved:', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => auth()->id() ?? 'guest',
            ]);

            return ResponseHelper::success(
                __('order.details_retrieved'),
                new OrderResource($order),
                200
            );
        } catch (\Exception $e) {
            Log::error('Order details retrieval failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ResponseHelper::error(__('order.not_found'), 404, $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            Log::debug('Raw request data for order status update:', [
                'input' => $request->all(),
                'order_id' => $id
            ]);

            $validator = Validator::make($request->all(), [
                'order_status' => ['required', 'in:pending,shipped,delivered'],
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error(__('order.validation_error'), 422, $validator->errors());
            }

            $validated = $validator->validated();
            Log::info('Order status update request:', array_merge($validated, ['order_id' => $id]));

            $order = Order::where('id', $id)
                ->when(auth()->check(), fn($query) => $query->where('user_id', auth()->id()))
                ->firstOrFail();

            $order->update([
                'order_status' => [
                    'en' => $validated['order_status'],
                    'ar' => match ($validated['order_status']) {
                        'pending' => 'معلق',
                        'shipped' => 'تم الشحن',
                        'delivered' => 'تم التوصيل',
                    },
                ],
            ]);

            Log::info('Order status updated successfully:', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'new_status' => $order->order_status,
            ]);

            return ResponseHelper::success(
                __('order.status_updated'),
                new OrderResource($order),
                200
            );
        } catch (\Exception $e) {
            Log::error('Order status update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ResponseHelper::error(__('order.error'), 500, $e->getMessage());
        }
    }

    protected function processPayment(Order $order, string $paymentMethod)
{
    try {
        if ($paymentMethod === 'stripe') {
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Order #' . $order->order_number,
                        ],
                        'unit_amount' => $order->total_amount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('APP_URL') . '/payment/success?order_id=' . $order->id . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('APP_URL') . '/payment/cancel?order_id=' . $order->id,
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ]);
            return $session->url;
        } elseif ($paymentMethod === 'paypal') {
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($order->total_amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => env('APP_URL') . '/payment/success?order_id=' . $order->id,
                    'cancel_url' => env('APP_URL') . '/payment/cancel?order_id=' . $order->id,
                ],
            ];

            $response = $this->paypalClient->execute($request);
            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve') {
                    return $link->href;
                }
            }
        }

        throw new \Exception('Unsupported payment method');
    } catch (\Exception $e) {
        Log::error('Payment processing failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        throw $e;
    }
}
public function handleStripeWebhook(Request $request)
{
    try {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            env('STRIPE_WEBHOOK_SECRET')
        );

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $order = Order::where('id', $session->metadata->order_id)->firstOrFail();
                if ($session->payment_status === 'paid') {
                    $order->update([
                        'payment_status' => [
                            'en' => 'paid',
                            'ar' => 'مدفوع',
                        ],
                        'order_status' => [
                            'en' => 'confirmed',
                            'ar' => 'مؤكد',
                        ],
                    ]);
                    Log::info('Stripe payment confirmed for order:', [
                        'order_id' => $order->id,
                        'session_id' => $session->id,
                    ]);
                }
                break;
            case 'checkout.session.expired':
                $session = $event->data->object;
                $order = Order::where('id', $session->metadata->order_id)->firstOrFail();
                Log::info('Stripe session expired for order:', ['order_id' => $order->id]);
                // يمكنك إضافة منطق زي إلغاء الطلب
                break;
        }

        return response()->json(['status' => 'success'], 200);
    } catch (\Exception $e) {
        Log::error('Stripe webhook failed: ' . $e->getMessage());
        return response()->json(['status' => 'error'], 400);
    }
}
public function handlePaymentSuccess(Request $request)
{
    try {
        $order = Order::findOrFail($request->query('order_id'));

        if ($order->payment_method === 'stripe' && $request->has('session_id')) {
            $session = \Stripe\Checkout\Session::retrieve($request->query('session_id'));
            if ($session->payment_status === 'paid') {
                $order->update([
                    'payment_status' => [
                        'en' => 'paid',
                        'ar' => 'مدفوع',
                    ],
                    'order_status' => [
                        'en' => 'confirmed',
                        'ar' => 'مؤكد',
                    ],
                ]);
                Log::info('Stripe payment verified manually:', ['order_id' => $order->id]);
            }
        } elseif ($order->payment_method === 'paypal' && $request->has('token')) {
            $captureRequest = new \PayPalCheckoutSdk\Orders\OrdersCaptureRequest($request->query('token'));
            $response = $this->paypalClient->execute($captureRequest);
            if ($response->result->status === 'COMPLETED') {
                $order->update([
                    'payment_status' => [
                        'en' => 'paid',
                        'ar' => 'مدفوع',
                    ],
                    'order_status' => [
                        'en' => 'confirmed',
                        'ar' => 'مؤكد',
                    ],
                ]);
                Log::info('PayPal payment captured:', ['order_id' => $order->id]);
            }
        }

        return ResponseHelper::success(__('payment.success'), new OrderResource($order), 200);
    } catch (\Exception $e) {
        Log::error('Payment success handling failed: ' . $e->getMessage());
        return ResponseHelper::error(__('payment.error'), 500, $e->getMessage());
    }
}

public function handlePaymentCancel(Request $request)
{
    try {
        $order = Order::findOrFail($request->query('order_id'));
        Log::info('Payment cancelled for order:', ['order_id' => $order->id]);
        return ResponseHelper::error(__('payment.cancelled'), 400);
    } catch (\Exception $e) {
        Log::error('Payment cancel handling failed: ' . $e->getMessage());
        return ResponseHelper::error(__('payment.error'), 500, $e->getMessage());
    }
}
}