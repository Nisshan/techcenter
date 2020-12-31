<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        if (count(cart()->items()) == 0) {
            return redirect()->route('home')->with('error', 'No Items in Cart, Please Add Items First');
        }
        return view('frontend.pages.checkout', [
            'items' => cart()->items(),
            'tax' => cart()->tax(),
            'transaction' =>cart()->totals(),
            'subtotal' => cart()->getSubtotal(),
            'count' => count(cart()->items())
        ]);
    }
}