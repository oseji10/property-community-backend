<?php
namespace App\Http\Controllers;

use Rave;  // or use KingFlamez\Rave\Facades\Rave;  // adjust import based on actual namespace
use App\Models\Property;
use App\Models\Payment;
use App\Models\PromotionPackages;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Damms005\LaravelFlutterwave\Facades\Flutterwave;
use Illuminate\Support\Facades\Http;


class PropertyFeatureController extends Controller
{


public function initiatePaymentFlutter(Request $request, $slug)
    {
       $property = Property::where('slug', $slug)
        ->where('addedBy', auth()->id())
        ->firstOrFail();

        $promotion_package = PromotionPackages::where('packageId', $request->planId)->first();

    if ($property->isFeatured) {
        return response()->json(['message' => 'Already featured'], 400);
    }

    $txRef = 'feat-' . Str::random(12) . '-' . time();
// $ngrokUrl = 'https://otiosely-chronological-cari.ngrok-free.dev'; // Your ngrok URL
        $secretKey = env('FLUTTERWAVE_SECRET_KEY');
        $response = Http::withHeaders(['Authorization' => "Bearer $secretKey"])
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $txRef,
                'amount' => $promotion_package->price, // Overridden by plan, but include for initial charge
                'currency' => 'NGN',
                // 'redirect_url' => env('APP_URL') . '/api/promotion/verify-redirect',
                'redirect_url' => 'https://nonnationalistic-unbloodily-moon.ngrok-free.dev/api/promotion/verify-redirect',
                'customer' => [
                    'email' => $property->owner->email,
                    'name' => $property->owner->firstName . ' ' . $property->owner->lastName,
                ],
                'customizations' => [
                    'title' => 'Property Plus Africa',
                    'description' => 'Promotion Subscription',
                    // 'description' => 'Subscribe to ' . $plan->planName,
                ],
                'meta' => [
                    'propertyId' => $property->propertyId, // For webhook
                ],
            ]);

        if ($response->successful()) {
              $payment = Payment::create([
                'propertyId' => $property->propertyId,
                'amount' => 4000,
                'status' => 'pending',
                'transactionReference' => $txRef,
                'userId' => auth()->user()->id,
        ]);
            $link = $response->json()['data']['link'];
            // return response()->json(['payment_link' => $link]);
            return response()->json([
            'success' => true,
            'payment_link' => $link
        ]);

        } else {
            // $subscription->delete(); // Cleanup
            return response()->json(['error' => 'Failed to initiate payment'], 500);
        }
    }


public function initiatePayment(Request $request, $slug)
{
    $property = Property::where('slug', $slug)
        ->where('addedBy', auth()->id())
        ->firstOrFail();

    $promotion_package = PromotionPackages::where('packageId', $request->planId)->first();

    if ($property->isFeatured) {
        return response()->json(['message' => 'Already featured'], 400);
    }

    $txRef = 'feat-' . Str::random(12) . '-' . time();

    $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
        ->post('https://api.paystack.co/transaction/initialize', [
            'email' => $property->owner->email,
            'amount' => $promotion_package->price * 100, // Paystack uses kobo
            'reference' => $txRef,
            'callback_url' => env('APP_URL') . '/api/promotion/verify-redirect',
            'metadata' => [
                'propertyId' => $property->propertyId,
            ],
        ]);

    if ($response->successful()) {
        $payment = Payment::create([
            'propertyId' => $property->propertyId,
            'amount' => $promotion_package->price,
            'status' => 'pending',
            'transactionReference' => $txRef,
            'userId' => auth()->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'payment_link' => $response->json('data.authorization_url')
        ]);
    }

    return response()->json(['error' => 'Failed to initiate payment'], 500);
}


    // Callback handler (after redirect from Flutterwave)
//    public function handleCallbackFlutter(Request $request)
// {
//     $status = $request->query('status');
//     $tx_ref = $request->query('tx_ref');
//     $transaction_id = $request->query('transaction_id');

//     $payment = Payment::where('transactionReference', $tx_ref)->first();

//     if (!$payment) {
//         return redirect('/dashboard?payment=error');
//     }

//     $property = Property::where('propertyId', $payment->propertyId)->first();

//     if (!$property) {
//         return redirect('/dashboard?payment=error');
//     }

//     // Accept both successful and completed
//     if (!in_array($status, ['successful', 'completed'])) {
//         return redirect("/dashboard/properties/{$property->slug}?payment=failed");
//     }

//     // Verify with Flutterwave
//     $response = Http::withToken(env('FLUTTERWAVE_SECRET_KEY'))
//         ->get("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify");

//     if (!$response->successful() || $response->json('status') !== 'success') {
//         return redirect("/dashboard/properties/{$property->slug}?payment=error");
//     }

//     $data = $response->json('data');

//    if (
//     $data['status'] === 'successful' &&
//     $data['tx_ref'] === $tx_ref &&
//     (float) $data['amount'] >= 4000
// ) {

//     if (!$property->isFeatured) {
//         $property->update([
//             'isFeatured' => true,
//             'featuredUntil' => now()->addDays(30),
//         ]);
//     }

//     if ($payment->status !== 'paid') {
//         $payment->update([
//             'status' => 'paid',
//             'transactionId' => $transaction_id,
//             'paymentGateway' => 'flutterwave',
//             'metaData' => json_encode($data),
//             'paymentMethod' => $data['payment_type'],
//         ]);
//     }

//     return redirect("/dashboard/properties/{$property->slug}?payment=success");
// }


//     return redirect("/dashboard/properties/{$property->slug}?payment=error");
// }


// public function handleCallback(Request $request)
// {
//     $tx_ref = $request->query('tx_ref');

//     // Fetch the payment record
//     $payment = Payment::where('transactionReference', $tx_ref)->first();

//     if (!$payment) {
//     return redirect('/dashboard?payment=error');
// }

// if ($payment->status === 'paid') {
//     return redirect("/dashboard/properties/{$property->slug}?payment=success");
// }

// // Payment is still pending
// return redirect("/dashboard/properties/{$property->slug}?payment=pending");

// }


public function handleCallback(Request $request)
{
   $txRef = $request->query('trxref');
    $payment = Payment::where('transactionReference', $txRef)->first();
    $frontend_url = env('FRONTEND_URL');
    if (!$payment) return redirect("{$frontend_url}/properties/view/?slug={$payment->property->slug}&payment=error");

    if ($payment->status === 'paid') {
        return redirect("{$frontend_url}/properties/view/?slug={$payment->property->slug}&payment=success");
    }

    return redirect("{$frontend_url}/properties/view/slug?={$payment->property->slug}&payment=pending");
}



}