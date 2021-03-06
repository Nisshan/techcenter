<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\OrderCanceled;
use App\Mail\OrderPlaced;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderProduct;
use Cartalyst\Stripe\Exception\CardErrorException;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;


class OrderController extends Controller
{
    public function index()
    {
        if (count(cart()->items()) < 1) {
            return redirect()->route('home')->with('error', 'No Items in Cart, Please Add Before Processing');
        }
        if (auth()->check()) {
            session()->forget('coupon');
            $coupon = auth()->user()->coupon()->first();
            if (isset($coupon)) {
                session()->put('coupon', [
                    'name' => $coupon->name,
                    'discount' => $coupon->discount
                ]);
            }
        }

        cart()->refreshAllItemsData();

        $discount = session()->get('coupon')['discount'] ?? 0;

        $newSubTotal = cart()->getSubtotal() - $discount;

        return view('frontend.pages.checkout', [
            'items' => cart()->items(),
            'transaction' => cart()->totals(),
            'subtotal' => cart()->getSubtotal(),
            'count' => count(cart()->items()),
            'discount' => $discount,
            'payable' => $newSubTotal,
            'delivery_types' => Delivery::where('status', 1)->get()
        ]);
    }


    public function store(Request $request)
    {
        cart()->refreshAllItemsData();
        $delivery_price = $this->calculateDeliveryCharge($request->delivery_type);

        $discount = session()->get('coupon')['discount'] ?? 0;

        $newSubTotal = cart()->getSubtotal() - $discount;

        try {
            $stripe = Stripe::charges()->create([
                'amount' => $newSubTotal + $delivery_price,
                'currency' => 'USD',
                'source' => $request->stripeToken,
                'description' => 'Order',
                'receipt_email' => $request->email,
                'metadata' => [
                    'delivery_price' => $delivery_price
                ],
            ]);

            $order = $this->addToOrdersTables($request, null, $stripe);
            Mail::send(new OrderPlaced($order));

            cart()->clear();
            session()->forget('coupon');
            if (auth()->check()) {
                $coupon = auth()->user()->coupon()->first();
                if ($coupon) {
                    $coupon->delete();
                }
            }

            return redirect()->route('thankyou')->with('success', 'Thank you! Your payment has been successfully accepted!');
        } catch (CardErrorException $e) {
            $this->addToOrdersTables($request, $e->getMessage(), null);
            return back()->withErrors('Error! ' . $e->getMessage());
        }
    }

    protected function addToOrdersTables($request, $error, $stripe)
    {

        $delivery_price = $this->calculateDeliveryCharge($request->delivery_type);

        $discount = session()->get('coupon')['discount'] ?? 0;

        $newSubTotal = cart()->getSubtotal() - $discount;

        // Insert into orders table
        $order = Order::create([
            'user_id' => auth()->user() ? auth()->id() : null,
            'billing_email' => $request->email,
            'billing_name' => $request->name,
            'billing_address' => $request->address,
            'billing_city' => $request->city,
            'charge_id' => $stripe['id'],
            'billing_province' => $request->province,
            'billing_postalcode' => $request->postalcode,
            'billing_phone' => $request->phone,
            'billing_name_on_card' => $request->name_on_card,
            'billing_discount' => $discount,
            'billing_discount_code' => session()->get('coupon')['name'] ?? " ",
            'billing_total' => $newSubTotal,
            'error' => $error,
            'delivery_type' => $request->serviceType,
            'delivery_charge' => $delivery_price,
            'deliveryTime' => $request->deliveryTime,
            'delivery_date' => $request->delivery_date,
            'quantity' => count(cart()->items()),
            'status' => 'InReview'

        ]);

        // Insert into order_product table
        foreach (cart()->items() as $item) {
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item['modelId'],
                'quantity' => $item['quantity'],
            ]);
        }

        return $order;
    }


    public function view(Order $order)
    {
        return view('frontend.pages.order', [
            'order' => $order
        ]);
    }

    public function cancel(Order $order)
    {
        $order->status = 'Canceled';
        $order->save();

        Stripe::refunds()->create($order->charge_id, $order->billing_total, ['reason' => 'requested_by_customer']);
        Mail::send(new OrderCanceled($order));
        return back()->with('success', 'Order Cancelled and the refund will be transferred to you');
    }


    private function calculateDeliveryCharge($delivery_type)
    {
        if ($delivery_type !== 'self-pickup') {
            return Delivery::where('slug', $delivery_type)->first()->price;
        } else {
            return 0;
        }
    }
}
