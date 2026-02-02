<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseType;
class CourseTypeController extends Controller
{
 

  public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');

    $query = CourseType::orderBy('courseTypeId', 'desc');

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('courseTypeName', 'like', "%$search%")
              ->orWhere('courseTypeId', 'like', "%$search%");
        });
    }

    $courseTypes = $query->paginate($perPage);

    return response()->json($courseTypes);
}

    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();
        // $data['courseTypeName'] = $request->input('categoryTypeName'); // Default to '1' if not provided

        $course_type = CourseType::create($data);

        // Return a response, typically JSON
        return response()->json($course_type, 201); // HTTP status code 201: Created
    }

    public function update(Request $request, $courseTypeId)
    {
        $course_type = CourseType::where('courseTypeId', $courseTypeId)->first();
        if (!$course_type) {
            return response()->json(['message' => 'Course Type not found'], 404);
        }

        $data = $request->all();
        $course_type->update($data);

        return response()->json([
            'message' => 'Course Type updated successfully',
            'courseTypeId' => $course_type->courseTypeId,
            'courseTypeName' => $course_type->courseTypeName,
            'status' => $course_type->status,
        ], 200); // HTTP status code 200: OK
    }



    public function destroy($courseTypeId)
    {
        $course_type = CourseType::find($courseTypeId);
        if (!$course_type) {
            return response()->json(['message' => 'Course Type not found'], 404);
        }

        $course_type->delete();
        return response()->json(['message' => 'Course Type deleted successfully'], 200); // HTTP status code 200: OK
    }
  

   
}
