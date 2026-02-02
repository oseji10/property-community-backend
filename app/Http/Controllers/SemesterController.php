<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Semester;
class SemesterController extends Controller
{
 

  public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');

    $query = Semester::orderBy('semesterId', 'desc');

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('semesterName', 'like', "%$search%")
              ->orWhere('semesterId', 'like', "%$search%");
        });
    }

    $semesters = $query->paginate($perPage);

    return response()->json($semesters);
}

    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();

        $semester = Semester::create($data);

        // Return a response, typically JSON
        return response()->json($semester, 201); // HTTP status code 201: Created
    }

    public function update(Request $request, $semesterId)
    {
        $semester = Semester::where('semesterId', $semesterId)->first();
        if (!$semester) {
            return response()->json(['message' => 'Semester not found'], 404);
        }

        $data = $request->all();
        $semester->update($data);

        return response()->json([
            'message' => 'semester updated successfully',
            'semesterId' => $semester->semesterId,
            'semesterName' => $semester->semesterName,
        ], 200); // HTTP status code 200: OK
    }



    public function destroy($semesterId)
    {
        $semester = Semester::find($semesterId);
        if (!$semester) {
            return response()->json(['message' => 'Semester not found'], 404);
        }

        $semester->delete();
        return response()->json(['message' => 'Semester deleted successfully'], 200); // HTTP status code 200: OK
    }
  

    public function activeSemesters()
    {
        $activeSemesters = Semester::where('status', 'active')->first();

        return response()->json($activeSemesters);
    }
   
}
