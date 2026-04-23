<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    /**
     * Get all users
     * GET /api/users
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::with('user_role')->get();
            
            return response()->json([
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch users'
            ], 500);
        }
    }

    /**
     * Create a new user
     * POST /api/users
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validatedData = $request->validate([
                'firstName'   => 'required|string|max:255',
                'lastName'    => 'required|string|max:255',
                'otherNames'  => 'nullable|string|max:255',
                'phoneNumber' => 'nullable|string|max:20',
                'email'       => 'required|email|max:255|unique:users,email',
                'password'    => 'required|string|min:6',
                'role'        => 'required|integer|in:1,2,3,4', // 1=USER, 2=AGENT, 3=ADMIN, 4=OWNER
                'status'      => 'nullable|string|in:active,inactive,suspended,pending',
            ]);

            // Create user in a transaction
            $user = DB::transaction(function () use ($validatedData, $request) {
                $user = User::create([
                    'firstName'   => $validatedData['firstName'],
                    'lastName'    => $validatedData['lastName'],
                    'otherNames'  => $validatedData['otherNames'] ?? null,
                    'phoneNumber' => $validatedData['phoneNumber'] ?? null,
                    'email'       => $validatedData['email'],
                    'password'    => Hash::make($validatedData['password']),
                    'role'        => $validatedData['role'],
                    'status'      => $validatedData['status'] ?? 'active',
                ]);

                return $user;
            });

            Log::info('User created:', ['email' => $user->email, 'id' => $user->id]);

            // Queue welcome email
            if ($user->email) {
                try {
                    Mail::to($user->email)->send(new WelcomeEmail(
                        $user->firstName,
                        $user->lastName,
                        $user->email,
                        $validatedData['password']
                    ));
                    Log::info('Welcome email queued for ' . $user->email);
                } catch (\Exception $e) {
                    Log::error('Failed to queue welcome email: ' . $e->getMessage());
                }
            }

            // Load role relationship
            $user->load('user_role');

            return response()->json([
                'message' => 'User created successfully',
                'data'    => $user
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create user'
            ], 500);
        }
    }

    /**
     * Update an existing user
     * PUT /api/users/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            // Validate input
            $validatedData = $request->validate([
                'firstName'   => 'sometimes|required|string|max:255',
                'lastName'    => 'nullable|string|max:255',
                'otherNames'  => 'nullable|string|max:255',
                'phoneNumber' => 'nullable|string|max:20',
                'email'       => [
                    'sometimes',
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($id)
                ],
                'password'    => 'nullable|string|min:6',
                'role'        => 'sometimes|required|integer|in:1,2,3,4',
                'status'      => 'sometimes|required|string|in:active,inactive,suspended,pending',
            ]);

            // Update user in transaction
            $user = DB::transaction(function () use ($user, $validatedData) {
                $updateData = [
                    'firstName'   => $validatedData['firstName'] ?? $user->firstName,
                    'lastName'    => $validatedData['lastName'] ?? $user->lastName,
                    'otherNames'  => $validatedData['otherNames'] ?? $user->otherNames,
                    'phoneNumber' => $validatedData['phoneNumber'] ?? $user->phoneNumber,
                    'email'       => $validatedData['email'] ?? $user->email,
                    'role'        => $validatedData['role'] ?? $user->role,
                    'status'      => $validatedData['status'] ?? $user->status,
                ];

                // Only update password if provided
                if (isset($validatedData['password'])) {
                    $updateData['password'] = Hash::make($validatedData['password']);
                }

                $user->update($updateData);
                return $user;
            });

            Log::info('User updated:', ['email' => $user->email, 'id' => $user->id]);

            // Load role relationship
            $user->load('user_role');

            return response()->json([
                'message' => 'User updated successfully',
                'data'    => $user
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update user'
            ], 500);
        }
    }

    /**
     * Delete a user
     * DELETE /api/users/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            DB::transaction(function () use ($user) {
                $user->delete();
            });

            Log::info('User deleted:', ['id' => $id]);

            return response()->json([
                'message' => 'User deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete user'
            ], 500);
        }
    }

    /**
     * Update user status only
     * PATCH /api/users/{id}/status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            $validatedData = $request->validate([
                'status' => 'required|string|in:active,inactive,suspended,pending',
            ]);

            $user->update([
                'status' => $validatedData['status']
            ]);

            Log::info('User status updated:', ['id' => $id, 'status' => $validatedData['status']]);

            return response()->json([
                'message' => 'User status updated successfully',
                'data'    => $user
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating user status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update user status'
            ], 500);
        }
    }

    /**
     * Get properties for a specific user (agents/owners)
     * GET /api/users/{id}/properties
     */
    public function getUserProperties($id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            // Get properties based on user role
            // Adjust the relationship name based on your User model
            $properties = $user->properties()
                ->with(['currency', 'images'])
                ->get();

            return response()->json($properties, 200);

        } catch (\Exception $e) {
            Log::error('Error fetching user properties: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch properties'
            ], 500);
        }
    }

    /**
     * Alias for getUserProperties (for route convenience)
     * GET /api/users/{id}/properties
     */
    public function properties($id): JsonResponse
    {
        return $this->getUserProperties($id);
    }
}