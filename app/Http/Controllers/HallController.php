<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Halls;
class HallController extends Controller
{
    public function halls(Request $request)
    {
        $halls = Halls::orderBy('id', 'desc')->get();
        return response()->json($halls);
    }

  public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');
    
    $query = Halls::orderBy('id', 'desc');
        
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('hallName', 'like', "%$search%")
              ->orWhere('hallId', 'like', "%$search%");
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
        $data['isActive'] = 1;
        $hall = Halls::create($data);
    
        // Return a response, typically JSON
        return response()->json($hall, 201); // HTTP status code 201: Created
    }

    public function update(Request $request, $hallId)
    {
        $hall = Halls::where('hallId', $hallId)->first();
        if (!$hall) {
            return response()->json(['message' => 'Hall not found'], 404);
        }

        $data = $request->all();
        $hall->update($data);

        return response()->json([
            'message' => 'hall updated successfully',
            'hallId' => $hall->hallId,
            'hallName' => $hall->hallName,
            'capacity' => $hall->capacity,
        ], 200); // HTTP status code 200: OK
    }

    public function changeStatus(Request $request, $hallId)
    {
        $hall = Halls::where('hallId', $hallId)->first();
        if (!$hall) {
            return response()->json(['message' => 'Hall not found'], 404);
        }

        $status = $request->isActive;
        $hall->update(['isActive' => $status]);

        return response()->json([
            'message' => 'Hall updated successfully',
            'hallId' => $hall->hallId,
            'hallName' => $hall->hallName,
            'capacity' => $hall->capacity,
            'isActive' => $hall->isActive,
        ], 200); // HTTP status code 200: OK
    }

    public function destroy($hallId)
    {
        $hall = Halls::find($hallId);
        if (!$hall) {
            return response()->json(['message' => 'hall not found'], 404);
        }

        $hall->delete();
        return response()->json(['message' => 'hall deleted successfully'], 200); // HTTP status code 200: OK
    }
  

   
}
