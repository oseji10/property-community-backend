<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff; 
use App\Models\StaffType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use DB;
use Illuminate\Http\JsonResponse;
use App\Models\Lgas;
class UsersController extends Controller
{
    public function index()
    {
        $users = User::with('staff.staff_type')->get();
        return response()->json($users);
       
    }
public function admins(Request $request)
{
    $perPage = $request->query('per_page', 10);
    $role = $request->query('role');
    $search = $request->query('search');

    $query = User::whereIn('role', [2, 3, 4])->with('user_role'); // Combine roles

    if ($role) {
        $query->where('role', $role);
    }

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('firstName', 'like', "%$search%")
              ->orWhere('lastName', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%")
              ->orWhere('phoneNumber', 'like', "%$search%");
        });
    }

    $users = $query->paginate($perPage);

    return response()->json($users);
}


  public function supervisors()
{
    $users = User::with('staff.staff_type')
        ->whereHas('staff', function ($query) {
            $query->where('staffType', 3);
        })
        ->get();

    return response()->json($users);
}


    public function staff_type()
    {
        $staffTypes = StaffType::all();
        return response()->json($staffTypes);
       
    }

public function store(Request $request)
{
    // 1. Validate input
    $validatedData = $request->validate([
        'firstName'   => 'required|string|max:255',
        'lastName'    => 'required|string|max:255',
        'otherNames'  => 'nullable|string|max:255',
        'phoneNumber' => 'nullable|string|max:20',
        'email'       => 'nullable|email|max:255|unique:users,email',
        'password' => 'nullable|string|min:6',
        'roleId'      => 'required|exists:roles,roleId',
    ]);

    // 2. Generate default password
    $default_password = strtoupper(Str::random(2)) . mt_rand(1000000000, 9999999999);
    if ($request->filled('password')) {
        $default_password = $request->input('password');
    }
    // 3. Create user
    $user = User::create([
        'firstName'   => $validatedData['firstName'],
        'lastName'    => $validatedData['lastName'],
        'otherNames'  => $validatedData['otherNames'] ?? null,
        'phoneNumber' => $validatedData['phoneNumber'] ?? null,
        'email'       => $validatedData['email'] ?? null,
        'password'    => Hash::make($default_password),
        'role'        => $validatedData['roleId'],
    ]);

    Log::info('User created:', ['email' => $user->email]);

    // 4. Queue the welcome email
    if ($user->email) {
        try {
            Mail::to($user->email)->send(new WelcomeEmail(
                $user->firstName,
                $user->lastName,
                $user->email,
                $default_password
            ));
            Log::info('Welcome email queued for ' . $user->email);
        } catch (\Exception $e) {
            Log::error('Failed to queue welcome email: ' . $e->getMessage());
        }
    }

    // 5. Load role relationship
    $user->load('user_role');

    // 6. Return response
    return response()->json([
        'message'     => 'User successfully created',
        'password'    => $default_password,
        'firstName'   => $user->firstName,
        'lastName'    => $user->lastName,
        'otherNames'  => $user->otherNames,
        'phoneNumber' => $user->phoneNumber,
        'email'       => $user->email,
        'role'        => $user->user_role->roleName,
        'id'          => $user->id,
    ], 201);
}


    
        public function update(Request $request, $staffId)  
{
        $staff = Staff::find($staffId);
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $staff->update($request->all());
        return response()->json($staff);
    }

       public function destroy($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

//    public function destroy($id): JsonResponse
//     {
//         return DB::transaction(function () use ($id) {
//             // Find the user
//             $user = User::find($id);
//             if (!$user) {
//                 return response()->json(['message' => 'Staff not found'], 404);
//             }

//             // Find the associated staff record
//             $staff = Staff::where('userId', $id)->first();
//             if (!$staff) {
//                 return response()->json(['message' => 'Associated staff record not found'], 404);
//             }

//             // Delete both records
//             $staff->delete();
//             $user->delete();

//             return response()->json(['message' => 'Staff deleted successfully']);
//         }, 5);
//     }
}
