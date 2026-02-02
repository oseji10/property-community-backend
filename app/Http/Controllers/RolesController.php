<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
       
    }

    public function user_roles()
    {
        $roles = Role::whereIn('roleId', [1, 2])->get();
        return response()->json($roles);
       
    }


    public function nicratRoles()
    {
        $roles = Roles::where('roleType', '=', 'NICRAT')->get();
        return response()->json($roles);
       
    }

    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();
    
        // Create a new user with the data (ensure that the fields are mass assignable in the model)
        $roles = Roles::create($data);
    
        // Return a response, typically JSON
        return response()->json($roles, 201); // HTTP status code 201: Created
    }
    
}
