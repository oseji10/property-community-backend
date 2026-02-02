<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
class SessionController extends Controller
{
 

  public function index(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $search = $request->query('search');

    $query = Session::orderBy('sessionId', 'desc');

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('sessionName', 'like', "%$search%")
              ->orWhere('sessionId', 'like', "%$search%");
        });
    }

    $sessions = $query->paginate($perPage);

    return response()->json($sessions);
}

    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();

        $session = Session::create($data);

        // Return a response, typically JSON
        return response()->json($session, 201); // HTTP status code 201: Created
    }

    public function update(Request $request, $sessionId)
    {
        $session = Session::where('sessionId', $sessionId)->first();
        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $data = $request->all();
        $session->update($data);

        return response()->json([
            'message' => 'session updated successfully',
            'sessionId' => $session->sessionId,
            'sessionName' => $session->sessionName,
            'duration' => $session->duration,
        ], 200); // HTTP status code 200: OK
    }



    public function destroy($sessionId)
    {
        $session = Session::find($sessionId);
        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $session->delete();
        return response()->json(['message' => 'Session deleted successfully'], 200); // HTTP status code 200: OK
    }

    public function activeSession()
    {
        $activeSessions = Session::where('status', 'active')->first();

        return response()->json($activeSessions);
    }
  

   
}
