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
    $secret = env('PAYSTACK_SECRET_KEY');

    // 1️⃣ Get signature from header
    $signature = $request->header('x-paystack-signature');

    if (!$signature) {
        Log::warning('Paystack webhook: No signature found');
        return response()->json(['status' => 'no_signature'], 400);
    }

    // 2️⃣ Compute signature
    $computedSignature = hash_hmac(
        'sha512',
        $request->getContent(),
        $secret
    );

    // 3️⃣ Compare signatures
    if (!hash_equals($computedSignature, $signature)) {
        Log::warning('Paystack webhook: Invalid signature');
        return response()->json(['status' => 'invalid_signature'], 400);
    }

    // 4️⃣ Signature valid → process webhook
    $payload = $request->all();
    Log::info('PAYSTACK WEBHOOK RECEIVED', $payload);

    $event = $payload['event'] ?? null;
    $data = $payload['data'] ?? [];

    if ($event === 'charge.success') {

        $txRef = $data['reference'] ?? null;

        if (!$txRef) {
            Log::warning('Paystack webhook: No reference found');
            return response()->json(['status' => 'no_reference'], 400);
        }

        $payment = Payment::where('transactionReference', $txRef)->first();

        if (!$payment) {
            Log::warning("Payment not found for reference: {$txRef}");
            return response()->json(['status' => 'not_found'], 404);
        }

        // Verify with Paystack
        $verify = Http::withToken($secret)
            ->get("https://api.paystack.co/transaction/verify/{$txRef}");

        if ($verify->successful() && $verify->json('data.status') === 'success') {

            $payment->update([
                'status' => 'paid',
                'paymentGateway' => 'paystack',
                'transactionId' => $verify->json('data.id'),
                'paymentMethod' => $verify->json('data.channel'),
                'metaData' => json_encode($verify->json('data')),
            ]);

            Log::info("Payment updated successfully for {$txRef}");

            return response()->json(['status' => 'success'], 200);
        }

        Log::warning("Verification failed for {$txRef}");
    }

    return response()->json(['status' => 'ignored'], 200);
}

}