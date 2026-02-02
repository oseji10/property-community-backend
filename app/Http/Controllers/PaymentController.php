<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Applications;
use App\Models\Payment;
use App\Models\User;
use App\Models\Batch;
use App\Models\BatchedCandidates;
use App\Models\BatchCandidates;
use App\Models\PaymentSetting;
use Illuminate\Support\Str;
use DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
      public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    
    $status = $request->query('status');
    $search = $request->query('search');
    
    $query = Payment::with(['users'])->orderBy('id', 'desc');
    
    if ($status) {
        $query->where('status', $status);
    }
    
   if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('applicationId', 'like', "%$search%")
              ->orWhere('jambId', 'like', "%$search%");
        })->orWhereHas('users', function($q) use ($search) {
            $q->where('firstName', 'like', "%$search%")
              ->orWhere('lastName', 'like', "%$search%")
              ->orWhere('otherNames', 'like', "%$search%");
        });
    }
    
    $payments = $query->paginate($perPage);
    
    return response()->json($payments);
}

    public function initiatePayment(Request $request)
    {
        try {
         

            // Validate request
            $request->validate([
                'applicationId' => 'required|exists:applications,applicationId',
            ]);

            // Fetch application and user details
            $application = Applications::findOrFail($request->applicationId);
            $user = User::findOrFail($application->userId);

            // Check if payment already exists for this application
        $existingPayment = Payment::where('applicationId', $application->applicationId)->first();

        if ($existingPayment) {
         
            return response()->json([
                'status' => 'info',
                'message' => 'Payment has already been initiated for this application.',
                'rrr' => $existingPayment->rrr,
                'amount' => $existingPayment->amount,
                'paymentStatus' => $existingPayment->status,
            ], 200);
        }

            // Fetch payment amount (e.g., from database or config)
            $amount_query = PaymentSetting::where('applicationType', $application->applicationType)->first();
            $amount = $amount_query->amount;

            // Generate unique orderId
            $orderId = Str::uuid()->toString();

            // Prepare Remita payload
            $merchantId = env('REMITA_MERCHANT_ID');
            $apiKey = env('REMITA_API_KEY');
            $serviceTypeId = env('REMITA_SERVICE_TYPE_ID');
            $apiUrl = env('REMITA_API_URL');

            // Validate environment variables
            if (!$merchantId || !$apiKey || !$serviceTypeId || !$apiUrl) {
                Log::error('Remita configuration missing', [
                    'merchantId' => $merchantId,
                    'apiKey' => $apiKey ? 'set' : 'missing',
                    'serviceTypeId' => $serviceTypeId,
                    'apiUrl' => $apiUrl,
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment configuration is incomplete. Please contact support.',
                ], 500);
            }

    $payload = [
    'serviceTypeId' => $serviceTypeId,
    'amount' => $amount,
    'orderId' => $orderId,
    'payerName' => $user->firstName . ' ' . $user->lastName ?? ' ' . $user->otherNames ?? '',
    'payerEmail' => $user->email,
    'payerPhone' => $user->phoneNumber ?? 'N/A',
    'description' => 'Application Fee - ' . $application->applicationId,
    'customFields' => [
        [
            'applicationId' => $application->applicationId ?? '',
            'JAMB ID' => $application->jambId ?? '',
            'Application Type' => $application->application_type->typeName ?? '',
        ]
    ]
];


            // Generate hash
            $concatString = $merchantId . $serviceTypeId . $orderId . $amount . $apiKey;

            
            $hash = hash('sha512', $concatString);
            
            // Construct full URL
            $fullUrl = rtrim($apiUrl, '/') ;
            Log::info('Attempting Remita API call', ['url' => $fullUrl, 'payload' => $payload]);
            
            // Make API call to Remita
            $httpClient = Http::withHeaders([
                'Authorization' => 'remitaConsumerKey=' . $merchantId . ',remitaConsumerToken=' . $hash,
                'Content-Type' => 'application/json',
            ]);

            // Temporary workaround for SSL issues in sandbox (REMOVE IN PRODUCTION)
            // if (env('APP_ENV') === 'local' || env('APP_ENV') === 'testing') {
            //     $httpClient->withoutVerifying();
            // }

            // $response = $httpClient->post($fullUrl, $payload);
            $response = $httpClient->asJson()->post($fullUrl, $payload);
            
            
            $rawBody = $response->body();
            
            // Remove jsonp wrapper if present
            if (str_starts_with($rawBody, 'jsonp (')) {
                $cleaned = trim($rawBody, "jsonp ()");
                $responseData = json_decode($cleaned, true);
            } else {
                $responseData = json_decode($rawBody, true);
            }
            
            // return $responseData;
            if ($response->successful() && isset($responseData['RRR'])) {
                
                // Store payment details
                $payment = Payment::create([
                    'applicationId' => $application->applicationId,
                    'userId' => $user->id,
                    'rrr' => $responseData['RRR'],
                    'amount' => $amount,
                    'orderId' => $orderId,
                    'status' => 'payment_pending',
                    'response' => json_encode($responseData),
                ]);
                
                // Update application status
                $application->update(['status' => 'payment_pending']);
                
                // Return payment details
                // Construct correct payment URL
                $paymentBaseUrl = 'https://login.remita.net/remita/ecomm';
                // $paymentUrl = $paymentBaseUrl . '/' . $merchantId . '/' . $responseData['RRR'] . '/' . $hash;
                // $paymentUrl = $paymentBaseUrl . '/' . $merchantId . '/' . $responseData['RRR'] . '/' . $hash . '/pay';
                
                $rrr = $responseData['RRR'];
                $concatString2 = $rrr . $apiKey . $merchantId;
                $hash2 = hash('sha512', $concatString2);

                $paymentHash = hash('sha512', $responseData['RRR'] . $apiKey . $merchantId);
                $paymentUrl = $paymentBaseUrl . '/' . $merchantId . '/' . $responseData['RRR'] . '/' . $paymentHash . '/payment';




                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment initiated successfully',
                    'rrr' => $responseData['RRR'],
                    'paymentUrl' => $paymentUrl,
                    // 'paymentUrl' => $paymentBaseUrl . '/' . $merchantId . '/' . $responseData['RRR'] . '/' . $hash . '/pay',
                    // 'paymentUrl' => 'https://login.remita.net/remita/ecomm/' . $merchantId . '/' . $responseData['RRR'] . '/' . $hash2 . '/pay',

                    'amount' => $amount,
                ], 200);
            }

            Log::error('Remita API error:', ['response' => $responseData, 'status_code' => $response->status()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate payment: ' . ($responseData['message'] ?? 'Unknown error'),
            ], $response->status());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Remita API request failed: ' . $e->getMessage(), ['url' => $fullUrl]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to connect to payment gateway. Please try again later.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Payment initiation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Payment initiation failed due to an unexpected error.',
            ], 500);
        }
    }


   public function verify(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'rrr' => 'required|string',
        'applicationId' => 'required|exists:applications,applicationId',
    ]);

    $rrr = $request->rrr;
    $applicationId = $request->applicationId;

    // $payment = Payment::where('rrr', $rrr)->first();
    $payment = Payment::where('rrr', $rrr)
    ->where('applicationId', $applicationId)
    ->where('userId', $user->id)
    ->lockForUpdate()
    ->first();

    if (!$payment) {
        return response()->json([
            'status' => 'error',
            'message' => 'RRR not found in local records.',
        ], 404);
    }

    // Remita API details
    $merchantId = env('REMITA_MERCHANT_ID');
    $apiKey = env('REMITA_API_KEY');
    $remitaUrl = env('REMITA_API_VERIFY_URL'); // e.g., https://remitademo.net/remita/exapp/api/v1/send/api/echannelsvc/merchant/api/paymentstatus

 
    $apiHash = hash('sha512', $rrr . $apiKey . $merchantId);

    try {
        // API call
        $response = Http::withHeaders([
            'Authorization' => 'remitaConsumerKey=' . $merchantId . ',remitaConsumerToken=' . $apiHash,
            'Content-Type' => 'application/json',
        ])->get($remitaUrl . '/' . $merchantId . '/' . $rrr . '/' . $apiHash . '/status.reg');

        $responseData = $response->json();
        Log::info('Remita verification response', ['response' => $responseData]);
        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === '00') {
            $payment->update([
                'status' => 'payment_completed',
                'channel' => $responseData['channel'] ?? null,
                'paymentDate' => now(),
            ]);
    // $applicant = Applications::where('applicationId', $applicationId)->first(); 
    $applicant = Applications::where('applicationId', $applicationId)
    ->where('userId', $user->id)->first(); 
     $applicant->update(['status' => 'payment_completed']);
     $retrieveToBatch = Applications::where('applicationId', $applicationId)->where('userId', $user->id)->where('status', 'payment_completed')->first();
    
    //  $this->assignBatchToCandidate($applicant);
     $this->assignBatchToCandidate($retrieveToBatch);
    BatchedCandidates::updateOrCreate(
    [
        'applicationId' => $retrieveToBatch->applicationId,
        // 'batchId' => $applicant->batch,
    ],
    [
        'batchId' => $retrieveToBatch->batch,
        'assigned_at' => now(),
    ]
);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified successfully!',
            ], 200);
        } else {
            Log::error('Remita payment verification failed', ['response' => $responseData]);
            return response()->json([
                'status' => 'error',
                'message' => $responseData['message'] ?? 'Payment not yet completed',
            ], 400);
        }
    } catch (\Exception $e) {
        Log::error('Remita payment verification error', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while verifying payment',
        ], 500);
    }
}




private function assignBatchToCandidate(Applications $retrieveToBatch)
{

DB::transaction(function () use ($retrieveToBatch) {
    if ($retrieveToBatch->batch) {
        return;
    }

    $batches = Batch::orderByRaw("LENGTH(batchId), batchId")->get();

    foreach ($batches as $batch) {
        $assignedCount = Applications::where('batch', $batch->batchId)->count();

        if ($assignedCount < $batch->capacity) {
            $retrieveToBatch->batch = $batch->batchId;
            $retrieveToBatch->save();

           

            return;
        }
    }

    throw new \Exception("No available batch with free capacity");
});

}

 
public function logBatchInfo (Applications $retrieveToBatch) {
    $batch = $retrieveToBatch->batch->batchId;
    BatchedCandidates::create([
        'applicationId' => $retrieveToBatch->applicationId,
        'batchId' => $batch->batchId,
    ]);

}

// My Payments
 public function myPayments(Request $request)
    {
        $loggedInUser = auth()->user()->id;
        $payments = Payment::
        with('payment_for')->
        where('userId', $loggedInUser)
        // ->where('status', 'payment_completed')
        ->first();
        if (!$payments) {
            return response()->json(['message' => 'No RRR generated'], 404);
        }
        return response()->json($payments);
    }


    // All Payments
    public function all_payments(){
        $all_payments = Payment::with('users')->limit('10')->orderBy('updated_at', 'desc')->get();
        return response()->json($all_payments);
    }

    public function recentPayments() {
        $recentPayments = Payment::with('users')
        ->where('status', 'payment_completed')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json($recentPayments);
    }
}


