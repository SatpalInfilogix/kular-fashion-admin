<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $cart = session()->get('cart', []);

        $itemId = $request->input('id');

        if (isset($cart[$itemId])) {
            $cart[$itemId]['quantity'] += $request->input('quantity', 1);
        } else {
            $cart[$itemId] = [
                "name" => $request->input('name'),
                "price" => $request->input('price'),
                "quantity" => $request->input('quantity', 1),
            ];
        }

        session()->put('cart', $cart);

        return response()->json(['message' => 'Item added to cart', 'cart' => $cart]);
    }
    
    public function removeItem($id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
            return response()->json(['message' => 'Item removed', 'cart' => $cart]);
        }

        return response()->json(['message' => 'Item not found'], 404);
    }
}
