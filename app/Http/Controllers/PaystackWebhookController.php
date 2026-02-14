<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use App\Models\Property;


class PaystackWebhookController extends Controller{
    
public function handle(Request $request)
{
    // return "Test";
    // Paystack sends JSON with 'event' and 'data'
    $payload = $request->all();
    \Log::info('PAYSTACK WEBHOOK:', $payload);

    $event = $payload['event'] ?? null;
    $data = $payload['data'] ?? [];

    if ($event === 'charge.success') {
        $txRef = $data['reference'] ?? null;
        $payment = Payment::where('transactionReference', $txRef)->first();

        if (!$payment) return response()->json(['status' => 'not_found'], 404);

        // Verify with Paystack
        $verify = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->get("https://api.paystack.co/transaction/verify/{$txRef}");

        if ($verify->successful() && $verify->json('data.status') === 'success') {
            $payment->update([
                'status' => 'paid',
                'paymentGateway' => 'paystack',
                'transactionId' => $verify->json('data.id'),
                'paymentMethod' => $verify->json('data.channel'),
                'metaData' => json_encode($verify->json('data')),
            ]);

            // Update property
            // $property = Property::find($payment->propertyId);
            // if ($property && !$property->isFeatured) {
            //     $property->update([
            //         'isFeatured' => true,
            //         'featuredUntil' => now()->addDays(30),
            //     ]);
            // }

            return response()->json(['status' => 'success'], 200);
        }
    }

    return response()->json(['status' => 'ignored'], 200);
}
}