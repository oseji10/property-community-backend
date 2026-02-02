<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::all();
        return response()->json($announcements);
    }

    
    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();

        // Create a new announcement with the data (ensure that the fields are mass assignable in the model)
        $announcement = Announcement::create($data);

        // Return a response, typically JSON
        return response()->json($announcement, 201); // HTTP status code 201: Created
    }
    
}
