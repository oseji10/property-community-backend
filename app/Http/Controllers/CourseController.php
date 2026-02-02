<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Applications;
use App\Models\Admissions;
use Illuminate\Support\Facades\Auth;
class CourseController extends Controller
{
 

  public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');
    $programme = $request->query('programme');
    $level = $request->query('level');
    $courseType = $request->query('course_type');
    $status = $request->query('status');

    $query = Course::with(['programmes', 'course_type', 'level'])->orderBy('courseId', 'desc');

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('courseName', 'like', "%$search%")
              ->orWhere('courseId', 'like', "%$search%")
              ->orWhere('courseCode', 'like', "%$search%");
        });
    }

    if ($programme) {
        $query->where(function($q) use ($programme) {
            $q->where('programme', $programme);
        });
    }

    if ($level) {
        $query->where(function($q) use ($level) {
            $q->where('level', $level);
        });
    }

    if ($courseType) {
        $query->where(function($q) use ($courseType) {
            $q->where('courseType', $courseType);
        });
    }

    if ($status) {
        $query->where(function($q) use ($status) {
            $q->where('status', $status);
        });
    }

    $courses = $query->paginate($perPage);

    return response()->json($courses);
}

public function store(Request $request)
{
    // Directly get the data from the request
    $data = $request->all();

    $course = Course::create($data);

    // Load the relationships
    $courseWithRelations = Course::with(['programmes', 'course_type', 'level'])
                                ->find($course->courseId);

    // Return a response with relationships
    return response()->json($courseWithRelations, 201);
}

    public function update(Request $request, $courseId)
    {
        $course = Course::where('courseId', $courseId)->first();
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $data = $request->all();
        $course->update($data);

        return response()->json([
            'message' => 'Course updated successfully',
            'courseId' => $course->courseId,
            'courseType' => $course->courseType,
            'courseName' => $course->courseName,
            'programme' => $course->programme,
            'creditUnit' => $course->creditUnit,
            'status' => $course->status,
        ], 200); // HTTP status code 200: OK
    }



    public function destroy($courseId)
    {
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $course->delete();
        return response()->json(['message' => 'Course deleted successfully'], 200); // HTTP status code 200: OK
    }
  


     public function availableCourses(Request $request)
{
    $user = Auth::user();

    // 1. Get the user’s application
    $application = Applications::where('userId', $user->id)->first();

    if (!$application) {
        return response()->json([
            'message' => 'No application found for this user.'
        ], 404);
    }

    // 2. Get admission record
    $admission = Admissions::where('applicationId', $application->applicationId)->first();

    if (!$admission) {
        return response()->json([
            'message' => 'No admission record found.'
        ], 404);
    }

    // 3. Get courses for this admission level
    $courses = Course::where('level', $admission->level)->get();

    if ($courses->isEmpty()) {
        return response()->json([
            'message' => 'No courses found for this level.'
        ], 404);
    }

    return response()->json($courses);
}

   
}
