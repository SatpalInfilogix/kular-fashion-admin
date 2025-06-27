<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerOrders;
use App\Models\CustomerOrderItems;
use App\Models\CouponUsagePerCustomer;
use App\Models\Coupon;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductQuantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlacedMail;

class OrdersController extends Controller
{
    public function placeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_address_id' => 'required',
            'payment_mode' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $currentDateTime = Carbon::now();
        $coupon = null;

        // Handle coupon validation first
        if (!empty($request->coupon_code)) {
            $coupon = Coupon::whereRaw('BINARY `code` = ?', [$request->coupon_code])
                ->where(function ($query) use ($currentDateTime) {
                    $query->whereNotNull('start_date')
                        ->whereDate('start_date', '<=', $currentDateTime);
                })
                ->where(function ($query) use ($currentDateTime) {
                    $query->whereNotNull('expire_date')
                        ->whereDate('expire_date', '>=', $currentDateTime);
                })
                ->where('status', 1)
                ->first();

            if (empty($coupon)) {
                return response()->json(['success' => false, 'message' => 'Coupon not found or expired'], 200);
            }
        }

        // Handle logged-in users
        if (!empty($request->user_id)) {
            $cart = Cart::where('user_id', $request->user_id)
                ->with(['cartItems.variant.product.tags'])
                ->first();

            if (empty($cart)) {
                return response()->json(['success' => false, 'message' => 'Your cart is empty'], 200);
            }

            $total_of_cart_items = $this->calculateCartTotal($cart->cartItems);

            if (!empty($coupon)) {
                return $this->processOrderWithCoupon($request, $cart->cartItems, $coupon, $total_of_cart_items, true, $cart);
            } else {
                return $this->processOrderWithoutCoupon($request, $cart->cartItems, $total_of_cart_items, true, $cart);
            }
        }
        // Handle guest users
        else {
            // Validate required fields for guest checkout
            if (empty($request->cart_items)) {
                return response()->json(['success' => false, 'message' => 'Cart items not found'], 400);
            }

            if (empty($request->delivery_address_id)) {
                return response()->json(['success' => false, 'message' => 'Delivery address is required'], 400);
            }

            if (empty($request->payment_mode)) {
                return response()->json(['success' => false, 'message' => 'Payment mode is required'], 400);
            }

            try {
                $cartItems = collect($request->cart_items);

                // Extract variant IDs from cart items
                $variant_ids = $cartItems->pluck('variant_id');

                // Get variant data with products
                $variants_data = ProductQuantity::whereIn('id', $variant_ids)
                    ->with(['product.tags'])
                    ->get()
                    ->map(function ($variant) use ($cartItems) {
                        $cartItem = $cartItems->firstWhere('variant_id', $variant->id);
                        $variant->quantity = $cartItem['quantity'] ?? 1;
                        $variant->price = $cartItem['price'] ?? $variant->price; // Use provided price or fallback
                        return $variant;
                    });

                if ($variants_data->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'No valid products found in cart!'], 404);
                }

                // Calculate cart total
                $total_of_cart_items = $this->calculateCartTotal($variants_data);

                // Process with or without coupon
                if (!empty($request->coupon_code)) {
                    return $this->processOrderWithCoupon($request, $variants_data, $request->coupon_code, $total_of_cart_items, false);
                } else {
                    return $this->processOrderWithoutCoupon($request, $variants_data, $total_of_cart_items, false);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checkout failed: ' . $e->getMessage()
                ], 500);
            }
        }
    }

    protected function processLoggedInOrder($request, $cart, $coupon)
    {
        $total_of_cart_items = $this->calculateCartTotal($cart->cartItems);

        if (!empty($coupon)) {
            return $this->processOrderWithCoupon($request, $cart, $coupon, $total_of_cart_items, true);
        } else {
            return $this->processOrderWithoutCoupon($request, $cart, $total_of_cart_items, true);
        }
    }

    protected function processGuestOrder($request, $coupon)
    {
        $cartItems = collect($request->cart['cartItems']);
        $variant_ids = $cartItems->pluck('variant_id');

        $variants_data = ProductQuantity::whereIn('id', $variant_ids)
            ->with(['product.tags'])
            ->get()
            ->map(function ($variant) use ($cartItems) {
                $variant->quantity = $cartItems->firstWhere('variant_id', $variant->id)['quantity'] ?? 1;
                return $variant;
            });

        if ($variants_data->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart Items not found!'], 201);
        }

        $total_of_cart_items = $this->calculateCartTotal($variants_data);

        if (!empty($coupon)) {
            return $this->processOrderWithCoupon($request, $variants_data, $coupon, $total_of_cart_items, false);
        } else {
            return $this->processOrderWithoutCoupon($request, $variants_data, $total_of_cart_items, false);
        }
    }

    protected function calculateCartTotal($items)
    {
        $total = 0;
        foreach ($items as $item) {
            if (isset($item->variant)) {
                $product = $item->variant->product;
            } else {
                $product = $item->product;
            }

            if ($product && $product->price) {
                $total += $product->price * ($item->quantity ?? 1);
            }
        }
        return $total;
    }

    protected function processOrderWithCoupon($request, $items, $coupon, $total_of_cart_items, $isLoggedIn, $cart = null)
    {
        // Validate coupon requirements
        if ($coupon->min_spend > $total_of_cart_items) {
            return response()->json(['success' => false, 'message' => 'Coupon valid on shopping of more than ' . $coupon->min_spend . ' only!'], 200);
        }

        if ($coupon->max_spend < $total_of_cart_items) {
            return response()->json(['success' => false, 'message' => 'Coupon valid on shopping of less than ' . $coupon->max_spend . ' only!'], 200);
        }

        if ($coupon->usage_limit_total == 1 && $coupon->usage_total_limit_used >= $coupon->usage_limit_total_value) {
            return response()->json(['success' => false, 'message' => 'Coupon is expired'], 200);
        }

        if ($isLoggedIn && $coupon->usage_limit_per_customer == 1 && $coupon->usage_limit_per_customer_value > 0) {
            $couponUsagePerCustomer = CouponUsagePerCustomer::where('coupon_id', $coupon->id)
                ->where('user_id', $request->user_id)
                ->first();

            if (!empty($couponUsagePerCustomer) && $couponUsagePerCustomer->usage_value >= $coupon->usage_limit_per_customer_value) {
                return response()->json(['success' => false, 'message' => 'Your limit to use this coupon is over.'], 200);
            }
        }

        if (!$isLoggedIn && $coupon->usage_limit_per_customer == 1) {
            return response()->json(['success' => false, 'message' => 'This coupon can be applied only after login'], 200);
        }

        // Process coupon logic
        $allowedTags = json_decode($coupon->allowed_tags, true) ?? [];
        $disallowedTags = json_decode($coupon->disallowed_tags, true) ?? [];

        $filtered_total = 0;
        $eligibleItems = [];

        foreach ($items as $item) {
            $variant = isset($item->variant) ? $item->variant : $item;
            $product = $variant->product ?? null;

            if (!$product || !$product->price) {
                continue;
            }

            $price = $product->price;
            $mrp = $product->mrp ?? $price;
            $quantity = $item->quantity ?? 1;
            $productTagIds = $product->tags->pluck('tag_id')->toArray();
            $productId = $product->id;

            // Apply coupon filters
            if ($coupon->limit_by_price === 'reduced_items' && $price >= $mrp) {
                continue;
            }

            if ($coupon->limit_by_price === 'full_price_items' && $price < $mrp) {
                continue;
            }

            if (!empty($allowedTags) && !array_intersect($productTagIds, $allowedTags)) {
                continue;
            }

            if (!empty($disallowedTags) && array_intersect($productTagIds, $disallowedTags)) {
                continue;
            }

            $filtered_total += $price * $quantity;
            $eligibleItems[] = [
                'product_id' => $productId,
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        // Calculate discount based on coupon type
        $discounted_total = 0;
        $final_price = $filtered_total;

        switch ($coupon->type) {
            case 'fixed':
                $final_price = max(0, $filtered_total - $coupon->value);
                $discounted_total = $coupon->value;
                break;

            case 'percentage':
                $discounted_total = ($coupon->value / 100) * $filtered_total;
                $final_price = max(0, $filtered_total - $discounted_total);
                break;

            case 'buy_x_get_y':
                $buyQty = (int) $coupon->buy_x_quantity;
                $getQty = (int) $coupon->get_y_quantity;

                $allItems = [];
                foreach ($eligibleItems as $item) {
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        $allItems[] = $item['price'];
                    }
                }

                sort($allItems);
                $totalQty = count($allItems);
                $chargeableQty = max(0, $totalQty - $getQty);
                $final_price = array_sum(array_slice($allItems, 0, $chargeableQty));
                $discounted_total = $filtered_total - $final_price;
                break;

            case 'buy_x_for_y':
                $discountProductIds = json_decode($coupon->buy_x_product_ids, true) ?? [];
                $discountType = $coupon->buy_x_discount_type;
                $discountValue = $coupon->buy_x_discount;

                foreach ($eligibleItems as $item) {
                    $isDiscounted = in_array($item['product_id'], $discountProductIds);
                    $line_total = $item['price'] * $item['quantity'];

                    if ($isDiscounted) {
                        if ($discountType === 'discount') {
                            $line_total -= ($discountValue * $item['quantity']);
                        } elseif ($discountType === 'percentage') {
                            $line_total -= ($item['price'] * ($discountValue / 100) * $item['quantity']);
                        }
                    }

                    $discounted_total += $line_total;
                }

                $final_price = max(0, $discounted_total);
                $discounted_total = $filtered_total - $final_price;
                break;
        }

        // Create order
        $orderData = $this->prepareOrderData($request, [
            'subtotal' => $total_of_cart_items,
            'discount' => $discounted_total,
            'total' => $final_price,
            'coupon_id' => $coupon->id,
            'is_logged_in' => $isLoggedIn
        ]);

        $placeOrder = CustomerOrders::create($orderData);

        // Create order items
        foreach ($items as $item) {
            $variant = isset($item->variant) ? $item->variant : $item;
            $product = $variant->product ?? null;

            if (!$product) continue;

            CustomerOrderItems::create([
                'customer_order_id' => $placeOrder->id,
                'user_id' => $isLoggedIn ? $request->user_id : null,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'actual_rate' => $product->mrp,
                'offered_rate' => $product->price,
                'quantity' => $item->quantity,
                'price' => $item->quantity * $product->price
            ]);
        }

        if ($placeOrder) {
            // Update coupon usage
            if ($coupon->usage_limit_total == 1) {
                Coupon::where('id', $coupon->id)->increment('usage_total_limit_used');
            }

            if ($isLoggedIn && $coupon->usage_limit_per_customer == 1 && $coupon->usage_limit_per_customer_value > 0) {
                CouponUsagePerCustomer::updateOrCreate(
                    ['coupon_id' => $coupon->id, 'user_id' => $request->user_id],
                    ['usage_value' => \DB::raw('usage_value + 1')]
                );
            }

            // Clear cart for logged-in users
            if ($isLoggedIn) {
                CartItem::where('cart_id', $cart->id)->delete();
                Cart::where('id', $cart->id)->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Order Placed Successfully.',
                'order_id' => $placeOrder->id
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'Error placing order'], 200);
    }

    protected function processOrderWithoutCoupon($request, $items, $total_of_cart_items, $isLoggedIn, $cart = null)
    {
        $orderData = $this->prepareOrderData($request, [
            'subtotal' => $total_of_cart_items,
            'total' => $total_of_cart_items,
            'is_logged_in' => $isLoggedIn
        ]);

        $placeOrder = CustomerOrders::create($orderData);

        foreach ($items as $item) {
            $variant = isset($item->variant) ? $item->variant : $item;
            $product = $variant->product ?? null;

            if (!$product) continue;

            CustomerOrderItems::create([
                'customer_order_id' => $placeOrder->id,
                'user_id' => $isLoggedIn ? $request->user_id : null,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'actual_rate' => $product->mrp,
                'offered_rate' => $product->price,
                'quantity' => $item->quantity,
                'price' => $item->quantity * $product->price
            ]);
        }

        if ($placeOrder) {
            // Clear cart for logged-in users
            if ($isLoggedIn && $cart) {
                CartItem::where('cart_id', $cart->id)->delete();
                Cart::where('id', $cart->id)->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Order Placed Successfully.',
                'order_id' => $placeOrder->id
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'Error placing order'], 200);
    }

    protected function prepareOrderData($request, $options)
    {
        $latestOrder = CustomerOrders::latest('id')->first();
        $nextId = $latestOrder ? $latestOrder->id + 1 : 1;
        $formattedOrderId = 'KF' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return [
            'unique_order_id' => $formattedOrderId,
            'user_id' => $options['is_logged_in'] ? $request->user_id : null,
            'customer_address_id' => $request->delivery_address_id, // Now correctly using delivery_address_id from request
            'coupon_id' => $options['coupon_id'] ?? null,
            'payment_type' => $request->payment_mode,
            'payment_status' => 'pending',
            'subtotal' => number_format($options['subtotal'], 2, '.', ''),
            'discount' => isset($options['discount']) ? number_format($options['discount'], 2, '.', '') : '0.00',
            'total' => number_format($options['total'], 2, '.', ''),
            'placed_at' => Carbon::now()
        ];
    }

    public function orderGet($id = null)
    {
        $query = CustomerOrders::with(['orderItems', 'orderItems.product', 'orderItems.user']);

        if (!is_null($id)) {
            $query->where('id', $id);
        }

        $orders = $query->where('user_id', Auth::id())->get();

        if ($query->count() <= 0) {
            return response()->json(['success' => false, 'message' => 'Order Id Not Found'], 200);
        }

        return response()->json(['success' => true, 'data' => $orders], 200);
    }

  public function sendOrderEmail(Request $request)
{
    $request->validate([
        'order_id' => 'required|integer',
    ]);

    $order = CustomerOrders::with(['orderItems.product','orderItems.variant.sizes.sizeDetail', 'user'])->find($request->order_id);

    if (!$order || !$order->user || !$order->user->email) {
        \Log::error('Order or user email not found', ['order_id' => $request->order_id]);
        return response()->json([
            'success' => false,
            'message' => 'Order or user email not found.'
        ], 404);
    }

    try {
        Mail::to($order->user->email)->send(new OrderPlacedMail($order));
        return response()->json([
            'success' => true,
            'message' => 'Email sent to ' . $order->user->email
        ]);
    } catch (\Throwable $e) {
        \Log::error('Email sending failed', [
            'order_id' => $order->id,
            'user_email' => $order->user->email,
            'error' => $e->getMessage()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Email failed: ' . $e->getMessage()
        ], 500);
    }
}

}
