<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Batch;
use DB;
class BatchController extends Controller
{
    public function batches(Request $request)
    {
        $batches = Batch::orderBy('id', 'asc')->get();
        return response()->json($batches);
    }

  public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');
    
    $query = Batch::orderBy('id', 'asc');
        
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('batchName', 'like', "%$search%")
              ->orWhere('batchId', 'like', "%$search%");
        });
    }
    
    $jambs = $query->paginate($perPage);
    
    return response()->json($jambs);
}

    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();
    
        // Create a new cadre with the data (ensure that the fields are mass assignable in the model)
        $batch = Batch::create($data);
    
        // Return a response, typically JSON
        return response()->json($batch, 201); // HTTP status code 201: Created
    }

    public function update(Request $request, $batchId)
    {
        $batch = Batch::find($batchId);
        if (!$batch) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        $data = $request->all();
        $batch->update($data);

        return response()->json([
            'message' => 'Batch updated successfully',
            'batchId' => $batch->batchId,
            'batchName' => $batch->batchName,
            'examDate' => $batch->examDate,
            'examTime' => $batch->examTime,
            'capacity' => $batch->capacity,
            'isVerificationActive' => $batch->isVerificationActive,
            'status' => $batch->status,
        ], 200); // HTTP status code 200: OK
    }


public function changeStatus(Request $request, $batchId)
{
    $batch = Batch::where('batchId', $batchId)->first();
    if (!$batch) {
        return response()->json(['message' => 'Batch not found'], 404);
    }

    $status = $request->isVerificationActive;
    
    // Start a database transaction
    DB::beginTransaction();
    
    try {
        // If we're activating verification for this batch, deactivate all others
        if ($status) {
            Batch::where('batchId', '!=', $batchId)
                 ->update(['isVerificationActive' => "0"]);
        }
        
        // Update the current batch
        $batch->update(['isVerificationActive' => $status ? "1" : "0"]);

        DB::commit();

        return response()->json([
            'message' => 'Batch verification status updated successfully',
            'batchId' => $batch->batchId,
            'batchName' => $batch->batchName,
            'isVerificationActive' => $batch->isVerificationActive,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to update verification status',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function destroy($batchId)
    {
        $batch = Batch::find($batchId);
        if (!$batch) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        $batch->delete();
        return response()->json(['message' => 'Batch deleted successfully'], 200); // HTTP status code 200: OK
    }
  

   
}
