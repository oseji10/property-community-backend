<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Applications;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\Photo;
use App\Models\OlevelResult;
use App\Models\User;
use App\Models\Batch;
use App\Models\Halls;
use App\Models\Payment;
use App\Models\BatchedCandidates;
use App\Models\ReBatchedCandidates;
use App\Models\ReBatchedCandidatesHistory;
use App\Models\HallAssignment;
use PDF;
use DB;
class ApplicationController extends Controller
{
    public function index2()
    {
        // This method should return a list of applicants
        $applications = Applications::with(['users', 'jamb'])->get();
        if ($applications->isEmpty()) {
            return response()->json(['message' => 'No applicants found'], 404);
        }
        return response()->json($applications);
    }


   public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $batch = $request->query('batch');
    $status = $request->query('status');
    $search = $request->query('search');
    
    $query = Applications::with(['users', 'jamb'])->orderBy('id', 'desc');
    
    if ($batch) {
        $query->where('batch', $batch);
    }

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
    
    $applications = $query->paginate($perPage);
    
    return response()->json($applications);
}




      public function apply(Request $request)
    {
        try {
            // Decode olevelResults if sent as JSON string
            $olevelResults = $request->input('olevelResults');
            if (is_string($olevelResults)) {
                $olevelResults = json_decode($olevelResults, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ValidationException(null, response()->json([
                        'status' => 'error',
                        'message' => 'Validation failed',
                        'errors' => ['olevelResults' => ['The olevel results field must be a valid JSON array']],
                    ], 422));
                }
            }

            // Validate request data
            $validated = $request->validate([
                // 'gender' => 'required|string|in:Male,Female,Other',
                'dateOfBirth' => 'required|date',
                'maritalStatus' => 'nullable|string|in:Single,Married,Divorced,Widowed',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024', // Max 2MB
            ]);

            // Validate olevelResults separately since it may have been decoded
            $request->merge(['olevelResults' => $olevelResults]); // Update request with decoded array
            $request->validate([
                'olevelResults' => 'required|array|min:1',
                'olevelResults.*.subject' => 'required|string|max:255',
                'olevelResults.*.grade' => 'required|string|in:A1,B2,B3,C4,C5,C6,D7,E8,F9',
                'olevelResults.*.examType' => 'required|string|in:WAEC,NECO,NABTEB',
                'olevelResults.*.examYear' => 'required|integer|min:1980|max:' . date('Y'),
            ]);

            // Get authenticated user
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Prepare data for update or create
            $applicationData = [
                // 'gender' => $validated['gender'],
                'dateOfBirth' => $validated['dateOfBirth'],
                'maritalStatus' => $validated['maritalStatus'] ?? null,
                
                // 'examType' => $validated['examType'],
                // 'examYear' => $validated['examYear'],
                'olevelResults' => json_encode($olevelResults),
                'status' => 'unpaid',
            ];

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('photos', 'public');
                $applicationData['photo_path'] = $path;
            }

            // Update or create application
            $application = Applications::updateOrCreate(
                ['userId' => $user->id], // Find by user_id
                $applicationData // Update or create with these values
            );

             // Handle photo upload
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('photos', 'public');
                Photo::updateOrCreate(
                    ['userId' => $user->id, 'applicationId' => $application->applicationId],
                    ['photoPath' => $path]
                );
            }

            // Handle O'Level results
            // Delete existing results to avoid duplicates
            OlevelResult::where('applicationId', $application->applicationId)->delete();
            // Create new results
            foreach ($olevelResults as $result) {
                OlevelResult::create([
    'applicationId' => $application->applicationId,
    'subject' => $result['subject'],
    'grade' => $result['grade'],
    'examYear' => $result['examYear'],
    'examType' => $result['examType'],
]);
            }


            Log::info('Application updated/created:', ['user_id' => $user->id, 'application_id' => $application->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Application submitted successfully',
                'applicationId' => $application->applicationId,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Application submission failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Application submission failed due to an unexpected error. Please try again later.',
            ], 500);
        }
    }

   public function edit(Request $request, $hospitalId)
{
    $validated = $request->validate([
        'hospitalName' => 'nullable|string|max:255',
        'acronym' => 'nullable|string|max:255',
        'location' => 'nullable|max:255',
        'contactPerson' => 'nullable|max:255',
    ]);

    $hospital = Hospital::with(['contact_person', 'hospital_location'])->where('hospitalId', $hospitalId)->first();
    if (!$hospital) {
        return response()->json(['message' => 'Hospital type not found'], 404);
    }

    $hospital->update($validated);
    
    return response()->json([
        'hospitalId' => $hospital->hospitalId,
        'hospitalName' => $hospital->hospitalName,
        'acronym' => $hospital->acronym,
        'status' => $hospital->status,
        // 'contactPerson' => $hospital->contactPerson ? $hospital->contact_person->firstName : null,
        'contactPerson' => $hospital->contact_person ? $hospital->contact_person->firstName : null, // Check relationship
        'location' => $hospital->hospital_location ? $hospital->hospital_location->stateName : null,
    ], 200);
}

    public function destroy($hospitalId)
    {
        $hospital = Hospital::where('hospitalId', $hospitalId)->first();
        if (!$hospital) {
            return response()->json(['message' => 'Hospital type not found'], 404);
        }

        $hospital->delete();
        return response()->json(['message' => 'Hospital type deleted successfully'], 200);
    }
    public function show($hospitalId)
    {
        $hospital = Hospital::where('hospitalId', $hospitalId)->first();
        if (!$hospital) {
            return response()->json(['message' => 'Hospital type not found'], 404);
        }
        return response()->json($hospital);
    }







// My Exam Slips
 public function mySlips(Request $request)
    {
        $loggedInUser = auth()->user()->id;
        $application = Applications::with('users', 'jamb')
        ->where('userId', $loggedInUser)
        ->where('status', 'payment_completed')
        ->first();
        if (!$application) {
            return response()->json(['message' => 'Payment not made'], 404);
        }
        return response()->json($application);
    }

// Application Status
    public function applicationStatus(Request $request)
    {
        $loggedInUser = auth()->user()->id;
        $application = Applications::where('userId', $loggedInUser)
        // ->where('status', 'payment_completed')
        ->first();
        if (!$application) {
            return response()->json(['message' => 'Application not made'], 404);
        }
        return response()->json($application);
    }



 public function changeBatch(Request $request, Applications $applicationId)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        'batch' => 'required|string|exists:batches,batchId',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        // Check if the batch exists and is active
        $batch = \App\Models\Batch::where('batchId', $request->batch)
                    ->where('status', 'active')
                    ->first();

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Selected batch is not available or inactive'
            ], 400);
        }

        // Check batch capacity
        $currentCount = Applications::where('batch', $request->batch)->count();
        if ($currentCount >= $batch->capacity) {
            return response()->json([
                'success' => false,
                'message' => 'Selected batch has reached maximum capacity'
            ], 400);
        }

        // Get the application and current batch
        $application = Applications::where('applicationId', $applicationId->applicationId)->first();
        $candidate_batch = BatchedCandidates::where('applicationId', $applicationId->applicationId)->first();

        if (!$application || !$candidate_batch) {
            return response()->json([
                'success' => false,
                'message' => 'Applicant or batch information not found'
            ], 404);
        }

        // Store the old batch ID before making any changes
        $oldBatchId = $candidate_batch->batchId;
        $newBatchId = $request->batch;

        // Update the application and batch
        $application->update(['batch' => $newBatchId]);
        $candidate_batch->update(['batchId' => $newBatchId]);

        // Create the re-batch record with the correct old and new values
        ReBatchedCandidatesHistory::create([
            'applicationId' => $application->applicationId,
            'oldBatchId' => $oldBatchId, // Use the stored old value
            'newBatchId' => $newBatchId,  // Use the new value from the request
            'rebatchedBy' => auth()->user()->id,
        ]);

        // This would keep just one record per application, updating it on each change
ReBatchedCandidates::updateOrCreate(
    ['applicationId' => $application->applicationId], // Match condition
    [
        'oldBatchId' => $oldBatchId,
        'newBatchId' => $newBatchId,
        'rebatchedBy' => auth()->user()->id,
    ]
);
        return response()->json([
            'success' => true,
            'message' => 'Batch changed successfully',
            'data' => $application
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to change batch',
            'error' => $e->getMessage()
        ], 500);
    }
}

// REBACTHED CANDIDATES
public function rebatched_candidates(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');
    
    $query = ReBatchedCandidates::with(['applicant.users', 'rebatched_by'])->orderBy('id', 'desc');

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('applicationId', 'like', "%$search%");
        })->orWhereHas('applicant.users', function($q) use ($search) {
            $q->where('firstName', 'like', "%$search%")
            ->orWhere('jambId', 'like', "%$search%")
              ->orWhere('lastName', 'like', "%$search%")
              ->orWhere('otherNames', 'like', "%$search%");
        });
    }

    $rebatched = $query->paginate($perPage);

    return response()->json($rebatched);
}

// ATTENDANCE
 public function attendance(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $batch = $request->query('batch');
    $hall = $request->query('hall');
    
    // $query = Applications::with(['users'])->orderBy('id', 'desc');
    $query = HallAssignment::with(['applications.users'])->orderBy('id', 'desc');

    if ($batch) {
        $query->where('batch', $batch);
    }

    if ($hall) {
        $query->where('hall', $hall);
    }
    
  
    
    $attendance = $query->paginate($perPage);
    
    return response()->json($attendance);
}


public function printAttendance(Request $request)
{
        $imagePath = storage_path('app/public/images/cons_logo.png');
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
        $base64Image = 'data:image/' . $imageType . ';base64,' . $imageData;
        
        $validated = $request->validate([
            'batch' => 'required|string',
            'hall' => 'required|string',
        ]);

        $batch = $request->batch;
        $hall = $request->hall;

        // Get attendance records with user information
        // $records = Applications::with('users')
       $records = HallAssignment::with('applications.users')
    ->where('batch', $batch)
    ->where('hall', $hall)
    ->orderByRaw('CAST(seatNumber AS UNSIGNED) ASC')
    ->get();


        // Get batch and hall details (you may need to adjust based on your models)
        $batchDetails = Batch::where('batchId', $batch)->first();
        $hallDetails = Halls::where('hallId', $hall)->first();

        $data = [
            'logo' => $base64Image,
            'records' => $records,
            'batch' => $batchDetails,
            'hall' => $hallDetails,
            'date' => now()->format('F j, Y'),
        ];

        $pdf = PDF::loadView('pdf.attendance', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        return $pdf->stream("attendance_{$batch}_{$hall}.pdf");
    }


        // Analytics
 public function analytics(){
    $signed_up = Applications::where('status', '=', 'not_submitted')->count();
    $payment_pending = Applications::where('status', '=', 'payment_pending')->count();
    $payment_completed = Applications::where('status', '=', 'payment_completed')->count();
    $sum_payments = Payment::where('status', 'payment_completed')->sum('amount');
    $batched_candidates = BatchedCandidates::count();
    $rebatched_candidates = ReBatchedCandidates::count();
    $total_batches = Batch::count();
    $total_candidates_verified = Applications::where('isPresent', '=', 1)->count();

    return response()->json([
        'signed_up' => $signed_up,
        'payment_pending' => $payment_pending,
        'payment_completed' => $payment_completed,
        'total' => $sum_payments,
        'batched_candidates' => $batched_candidates,
        'rebatched_candidates' => $rebatched_candidates,
        'total_batches' => $total_batches,
        'total_candidates_verified' => $total_candidates_verified,
    ]);
 }

 public function batched_candidates(Request $request){
    $results = BatchedCandidates::select('batchId', DB::raw('COUNT(applicationId) as total_candidates'))
    ->groupBy('batchId')
    ->get();
    return response()->json($results);
 }

  public function candidates_states(Request $request)
{
    $results = Applications::with('jamb:jambId,state')
    ->where('status', 'payment_completed') // eager load only the fields needed
        ->get()
        ->groupBy(fn($app) => $app->jamb->state ?? 'N/A')
        ->map(fn($group) => [
            'state' => $group->first()->jamb->state ?? 'N/A',
            'total_candidates' => $group->count(),
        ])
        ->sortByDesc('total_candidates')
        ->values(); // reset array keys

    return response()->json($results);
}


public function candidates_gender(Request $request)
{
    $results = Applications::with(['jamb' => function($q) {
            $q->select('jambId', 'gender');
        }])
        ->where('status', 'payment_completed')
        ->get()
        ->groupBy(function ($app) {
            // Handle missing jamb relation
            $gender = $app->jamb->gender ?? null;

            if (!$gender) {
                return 'N/A';
            }

            // Trim spaces & normalize case
            $gender = strtoupper(trim($gender));

            // Accept only M / F, else mark N/A
            return in_array($gender, ['M', 'F']) ? $gender : 'N/A';
        })
        ->map(fn($group, $gender) => [
            'gender' => $gender,
            'total_candidates' => $group->count(),
        ])
        ->sortByDesc('total_candidates')
        ->values();

    return response()->json($results);
}


// Total candidates verified
public function verified_candidates(Request $request)
{
    $userId = auth()->id();

    $results = HallAssignment::select(
            'batch',
            DB::raw('COUNT(applicationId) as total_candidates'),
            DB::raw("SUM(CASE WHEN verifiedBy = {$userId} THEN 1 ELSE 0 END) as verified_by_user")
        )
        ->groupBy('batch')
        ->get();

    return response()->json($results);
}


}

