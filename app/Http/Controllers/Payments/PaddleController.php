<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaddleController extends Controller
{
    public function checkout($priceId)
    {
        // If your checkout requires auth user
        // Replace this with Auth::user()->checkout($priceId)->returnTo(route('dashboard'))
        $checkout = \Laravel\Paddle\Checkout::guest([$priceId])
            ->returnTo(route('dashboard'));

        $checkout = [
            'items' => $checkout->getItems(),
            'custom' => $checkout->getCustomData(),
            'return_url' => $checkout->getReturnUrl(),
        ];

        return \response()->json($checkout);
    }

    public function subscriptionSwap(Request $request, $priceId): \Illuminate\Http\RedirectResponse
    {
        $request->user()->subscription()->swap($priceId);

        return redirect()->route('dashboard');
    }

    public function subscriptionCancel(Request $request)
    {
        $request->user()->subscription()->cancel();

        return redirect()->route('dashboard');
    }
}
