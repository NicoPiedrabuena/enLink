<?php

namespace App\Http\Controllers;

use App\Actions\Payments\PaymentProcessingUnavailableException;
use App\Actions\Payments\ProcessMercadoPagoWebhookAction;
use App\Support\MercadoPagoWebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function store(Request $request, ProcessMercadoPagoWebhookAction $processWebhook): JsonResponse
    {
        if (! MercadoPagoWebhookSignature::isValid($request)) {
            return response()->json(['message' => 'Firma de webhook inválida.'], 401);
        }

        try {
            $receipt = $processWebhook->execute($request->all());
        } catch (PaymentProcessingUnavailableException) {
            // A 5xx asks Mercado Pago to retry the same signed notification.
            return response()->json(['message' => 'No se pudo procesar el pago. Reintentá más tarde.'], 503);
        }

        return response()->json(['status' => $receipt->status]);
    }
}
