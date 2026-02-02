<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Programmes;
class ProgrammeController extends Controller
{
 

  public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');
    
    $query = Programmes::orderBy('programmeId', 'desc');
        
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('programmeName', 'like', "%$search%")
              ->orWhere('programmeId', 'like', "%$search%");
        });
    }
    
    $programmes = $query->paginate($perPage);
    
    return response()->json($programmes);
}

    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();
    
        // Create a new cadre with the data (ensure that the fields are mass assignable in the model)
        $data['isActive'] = 1;
        $programme = Programmes::create($data);
    
        // Return a response, typically JSON
        return response()->json($programme, 201); // HTTP status code 201: Created
    }

    public function update(Request $request, $programmeId)
    {
        $programme = Programmes::where('programmeId', $programmeId)->first();
        if (!$programme) {
            return response()->json(['message' => 'Programme not found'], 404);
        }

        $data = $request->all();
        $programme->update($data);

        return response()->json([
            'message' => 'programme updated successfully',
            'programmeId' => $programme->programmeId,
            'programmeName' => $programme->programmeName,
            'duration' => $programme->duration,
        ], 200); // HTTP status code 200: OK
    }



    public function destroy($programmeId)
    {
        $programme = Programmes::find($programmeId);
        if (!$programme) {
            return response()->json(['message' => 'Programme not found'], 404);
        }

        $programme->delete();
        return response()->json(['message' => 'Programme deleted successfully'], 200); // HTTP status code 200: OK
    }
  

   
}
