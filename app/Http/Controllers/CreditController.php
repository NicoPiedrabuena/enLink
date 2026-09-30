<?php

namespace App\Http\Controllers;

use App\Actions\Payments\CheckoutUnavailableException;
use App\Actions\Payments\CreateMercadoPagoCheckoutAction;
use App\Models\CreditPackage;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CreditController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Credits', [
            'balance' => $user->creditBalance?->balance ?? 0,
            'packages' => CreditPackage::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'credits', 'price', 'currency']),
            'orders' => $user->paymentOrders()
                ->latest()
                ->limit(10)
                ->get(['id', 'package_name', 'credits', 'amount', 'currency', 'status', 'created_at']),
            'transactions' => $user->creditTransactions()
                ->latest()
                ->limit(10)
                ->get(['id', 'type', 'amount', 'balance_after', 'description', 'created_at']),
        ]);
    }

    public function checkout(Request $request, CreditPackage $creditPackage, CreateMercadoPagoCheckoutAction $createCheckout): SymfonyResponse
    {
        try {
            $order = $createCheckout->execute($request->user(), $creditPackage);
        } catch (CheckoutUnavailableException $exception) {
            return back()->withErrors(['checkout' => $exception->getMessage()]);
        }

        return Inertia::location($order->checkout_url);
    }
}
