<?php
// app/Http/Controllers/FlutterwaveWebhookController.php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
class FlutterwaveWebhookController extends Controller
{
   public function handle(Request $request)
{
    $payload = $request->all();
        \Log::info('WEBHOOK RECEIVED:', $request->all());

    $tx_ref = $payload['data']['tx_ref'] ?? null;
    $transaction_id = $payload['data']['id'] ?? null;

    if (!$tx_ref || !$transaction_id) {
        return response()->json(['status' => 'error'], 400);
    }

    $payment = Payment::where('transactionReference', $tx_ref)->first();
    if (!$payment) {
        return response()->json(['status' => 'not_found'], 404);
    }

    // Verify with Flutterwave
    $response = Http::withToken(env('FLUTTERWAVE_SECRET_KEY'))
        ->get("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify");

    if (!$response->successful() || $response->json('status') !== 'success') {
        return response()->json(['status' => 'failed'], 400);
    }

    $data = $response->json('data');

    if ($data['status'] === 'successful' && $data['tx_ref'] === $tx_ref) {

        // Update payment
        $payment->update([
            'status' => 'paid',
            'transactionId' => $transaction_id,
            'paymentGateway' => 'flutterwave',
            'metaData' => json_encode($data),
            'paymentMethod' => $data['payment_type'],
        ]);

        // Update property
        $property = Property::find($payment->propertyId);
        if ($property && !$property->isFeatured) {
            $property->update([
                'isFeatured' => true,
                'featuredUntil' => now()->addDays(30),
            ]);
        }

        return response()->json(['status' => 'success'], 200);
    }

    return response()->json(['status' => 'failed'], 400);
}

}