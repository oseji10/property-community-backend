<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Doctors;
use App\Models\Applications;
use App\Models\ApplicationType;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\JWTAuth;
// use Tymon\JWTAuth\Facades\JWTAuth; // Ensure the facade is imported
use App\Models\RefreshToken;    
use Carbon\Carbon;

use App\Mail\OtpEmail;

use Tymon\JWTAuth\Exceptions\JWTException; // Uncomment if using JWTException




class AuthController extends Controller
{
   

    // use Illuminate\Http\Request;
    // use Illuminate\Support\Facades\Hash;
    // use Illuminate\Validation\ValidationException;
    // use App\Models\User;
    
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }

    public function contacts()
    {
        $users = User::all();
        return response()->json($users);
       
    }

 public function general_login(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',   // ← usually required
    ]);

    // Try to log in using the username (email or phone)
    $credentials = $request->only('username', 'password');

    $user = User::with(['user_role'])
        ->where('email', $request->username)
        ->orWhere('phoneNumber', $request->username)
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => false,
            'code'    => 'USER_NOT_FOUND',
            'message' => 'No account found',
        ], 404);
    }

      if (!$accessToken = auth('api')->attempt([
        'email'    => $user->email,
        'password' => $request->password,
    ])) {
        return response()->json([
            'status'  => false,
            'message' => 'Invalid credentials',
        ], 401);
    }

    $user = auth('api')->user()->load('user_role');

    $refreshToken = bin2hex(random_bytes(40)); // or Str::random(80)

    RefreshToken::create([
        'user_id'    => $user->id,
        'token'      => $refreshToken,
        'expires_at' => now()->addDays(14),
    ]);

    $user->makeHidden(['password']);

    return response()->json([
        'status'      => true,
        'message'     => 'Logged in',
        'firstName'   => $user->firstName ?? '',
        'lastName'    => $user->lastName ?? '',
        'email'       => $user->email ?? '',
        'phoneNumber' => $user->phoneNumber ?? '',
        'role'        => $user->user_role->roleName ?? null,
        'profileImage'=> $user->profileImage ?? '/avatar.png',
        'coverImage'  => $user->coverImage ?? '/cover_photo.jpg',
    ])
    ->cookie('access_token',  $accessToken,  60 * 24 * 2,   null, null, true, true, false, 'lax')
    ->cookie('refresh_token', $refreshToken, 60 * 24 * 14,  null, null, true, true, false, 'lax');
}
public function signin(Request $request)
{
    $request->validate([
        'username' => 'required',
        'password' => 'nullable', // Allow empty password
    ]);

    $user = User::with(['user_role'])
        ->where('email', $request->username)
        ->orWhere('phoneNumber', $request->username)
        ->first();

    if (!$user) {
        return response()->json([
            'status'  => false,
            'code'    => 'USER_NOT_FOUND',
            'message' => 'No account found',
        ], 404);
    }

    // ── STATE 1: Email not verified (pending) ──
    if ($user->status === 'pending') {
        // Generate a new OTP (adjust logic to match how you generate OTPs elsewhere)
        $otp = rand(100000, 999999); // ← Replace with your secure OTP generator

        // Save OTP (assuming you have otp & otp_expires_at columns)
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // Send OTP email
        try {
            Mail::to($user->email)->send(new OtpEmail(
                $user->firstName,
                $user->lastName,
                $otp
            ));
            Log::info('OTP email sent successfully to ' . $user->email);
        } catch (\Exception $e) {
            Log::error('OTP email sending failed: ' . $e->getMessage());
            // Still proceed — don't fail login attempt due to email issue
        }

        return response()->json([
            'status'                  => false,
            'requiresEmailVerification' => true,
            'email'                   => $user->email,
            'message'                 => 'Email not verified. A new OTP has been sent.',
        ], 403);
    }

    // ── STATE 2: Email verified but no password yet ──
    if (is_null($user->password)) {
        return response()->json([
            'status'               => false,
            'requiresPasswordSetup' => true,
            'email'                => $user->email,
            'message'              => 'Password not set. Please complete setup.',
        ], 403);
    }

    // ── STATE 3: Fully active user ──
    if (!$request->password) {
        return response()->json([
            'status'          => false,
            'requiresPassword' => true,
            'message'         => 'Password required',
        ], 422);
    }

    // Attempt standard password login
    if (!$accessToken = auth('api')->attempt([
        'email'    => $user->email,
        'password' => $request->password,
    ])) {
        return response()->json([
            'status'  => false,
            'message' => 'Invalid credentials',
        ], 401);
    }

    // Generate refresh token
    $refreshToken = Str::random(64);
    $user = auth('api')->user()->load(['user_role']); // Reload with relations

    // Store refresh token
    RefreshToken::create([
        'user_id'    => $user->id,
        'token'      => $refreshToken,
        'expires_at' => Carbon::now()->addDays(14),
    ]);

    $user->makeHidden(['password']);

    return response()->json([
        'status'      => true,
        'message'     => 'Logged in',
        'firstName'   => $user->firstName ?? '',
        'lastName'    => $user->lastName ?? '',
        'email'       => $user->email ?? '',
        'phoneNumber' => $user->phoneNumber ?? '',
        'role'        => $user->user_role->roleName ?? '',
        
        'profileImage'=> $user->profileImage ?? '/avatar.png',
        'coverImage'  => $user->coverImage ?? '/cover_photo.jpg',
    ])
    
    ->cookie(
        'access_token',
        $newAccessToken,
        60 * 24 * 2, // 2 days
        null,
        null,
        true,
        true,
        false,
        'strict'
    )
    ->cookie(
        'refresh_token',
        $newRefreshToken,
        60 * 24 * 14, // 14 days
        null,
        null,
        true,
        true,
        false,
        'strict'
    );

}

    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return response()->json(['error' => 'Refresh token missing'], 401);
        }

        // Verify refresh token
        $tokenRecord = RefreshToken::where('token', $refreshToken)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$tokenRecord) {
            return response()->json(['error' => 'Invalid or expired refresh token'], 401);
        }

        // Generate new access token
        $user = User::find($tokenRecord->user_id);
        // $newAccessToken = JWTAuth::fromUser($user);
        $newAccessToken = $this->jwt->fromUser($user);
        

        // Optionally, issue a new refresh token and invalidate the old one
        $newRefreshToken = Str::random(64);
        $tokenRecord->update([
            'token' => $newRefreshToken,
            'expires_at' => Carbon::now()->addDays(14),
        ]);

     return response()->json(['message' => 'Token refreshed'])
    ->cookie(
        'access_token',
        $newAccessToken,
        60 * 24 * 2, // 2 days
        null,
        null,
        true,
        true,
        false,
        'strict'
    )
    ->cookie(
        'refresh_token',
        $newRefreshToken,
        60 * 24 * 14, // 14 days
        null,
        null,
        true,
        true,
        false,
        'strict'
    );
    }
      

    // Logout
    // public function logout(Request $request)
    // {
    //     $request->user()->tokens()->delete();

    //     return response()->json(['message' => 'Logged out successfully']);
    // }

    public function logout(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if ($refreshToken) {
            RefreshToken::where('token', $refreshToken)->delete();
        }

        return response()->json(['message' => 'Logged out'])
            ->cookie('access_token', '', -1)
            ->cookie('refresh_token', '', -1);
    }

    // Get authenticated user
    public function user(Request $request)
    {
        return response()->json($request->user());
    }


       public function signup(Request $request)
{
    try {
    
        // Validate request data
        $validated = $request->validate([
            'fullName' => 'nullable|string|max:255',
            'phoneNumber' => 'nullable|string|unique:users,phoneNumber|max:14|regex:/^\+?\d{10,15}$/',
            'email' => 'required|email|unique:users,email|max:255',
            'role' => 'required|exists:roles,roleId',
        ]);

        // Generate OTP (6 digits)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = now()->addMinutes(10); // OTP valid for 10 minutes

        // Generate a temporary password (you can keep this or remove it)
        $tempPassword = strtoupper(Str::random(8));

        // Create user with OTP fields
        // Assuming Laravel controller method



// Break down fullName into firstName, lastName, and otherNames
$fullName = trim($validated['fullName']);
$nameParts = preg_split('/\s+/', $fullName); // Split by whitespace

$firstName = $nameParts[0] ?? '';
$lastName = count($nameParts) > 1 ? array_pop($nameParts) : ''; // Last word as lastName
$otherNames = count($nameParts) > 1 ? implode(' ', $nameParts) : ''; // Everything in between

// Fallback if only one name
if (empty($lastName)) {
    $lastName = $firstName;
    $firstName = '';
    $otherNames = '';
}


// Create the user
$user = User::create([
    'firstName' => $firstName,
    'lastName' => $lastName,
    'otherNames' => $otherNames,
    'phoneNumber' => $validated['phoneNumber'],
    'email' => $validated['email'],
    'password' => Hash::make($validated['password'] ?? 'default_temp_password'), // Use sent password or fallback
    'role' => $validated['role'],
    'currentPlan' => 1,
    'otp_code' => $otp, // Assume $otp and $otpExpiresAt are generated earlier
    'otp_expires_at' => $otpExpiresAt,
    'email_verified_at' => null,
]);


   
        Log::info('User created:', ['email' => $user->email]);

        // Send OTP email instead of welcome email
        try {
            Mail::to($user->email)->send(new OtpEmail(
                $user->firstName,
                $user->lastName,
                $otp
            ));
            Log::info('OTP email sent successfully to ' . $user->email);
        } catch (\Exception $e) {
            Log::error('OTP email sending failed: ' . $e->getMessage());
            
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Signup successful! Please check your email for the verification code.',
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Registration failed: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Signup failed due to an unexpected error. Please try again later.',
        ], 500);
    }
}


    public function changePassword(Request $request)
{
    // Validate input
    $request->validate([
        'currentPassword' => 'required',
        'newPassword' => 'required|min:6', // 'confirmed' ensures newPassword_confirmation is also sent
    ]);

    $user = Auth::user();

    // Check if the current password matches
    if (!Hash::check($request->currentPassword, $user->password)) {
        return response()->json(['message' => 'Current password is incorrect.'], 422);
    }

    // // Only update the fields if they are provided
    // if ($request->has('email')) {
    //     $user->email = $request->email;
    // }
    // if ($request->has('phoneNumber')) {
    //     $user->phoneNumber = $request->phoneNumber;
    // }
    // if ($request->has('firstName')) {
    //     $user->firstName = $request->firstName;
    // }
    // if ($request->has('lastName')) {
    //     $user->lastName = $request->lastName;
    // }

    // Update the user's password
    $user->password = Hash::make($request->newPassword);
    $user->save();

    return response()->json(['message' => 'Password changed successfully.']);
}



public function updateProfile(Request $request)
{
    // Find the patient by ID
    $user = User::where('email', $request->email)->first();

    
    if (!$user) {
        return response()->json([
            'error' => 'User not found',
        ], 404); // HTTP status code 404: Not Found
    }

    
    $data = $request->all();

    
    $user->update($data);

    
    return response()->json([
        'message' => 'User updated successfully',
        'data' => $user,
    ], 200); // HTTP status code 200: OK
}




public function setupPassword(Request $request)
{
    try {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6',
        ]);

        // Find user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        // Check if email is verified
        if (!$user->email_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email not verified. Please verify your email first.',
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'status' => 'active', // Activate the user
        ]);

        // Clear OTP data
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Send welcome email
        try {
            $this->sendWelcomeEmail($user);
        } catch (\Exception $e) {
            Log::error('Welcome email failed to send: ' . $e->getMessage());
            // Continue execution - don't fail the password setup if email fails
        }

        Log::info('Password setup completed for user:', ['email' => $user->email]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password set successfully. Welcome email sent.',
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Password setup failed: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Password setup failed. Please try again.',
        ], 500);
    }
}

/**
 * Send welcome email to user
 */
private function sendWelcomeEmail(User $user)
{
    // Option 1: Using Laravel Mailable
    Mail::to($user->email)->send(new WelcomeEmail($user));

   
}

    // public function changePassword(Request $request)
    // {
    //     // Validate input
    //     $request->validate([
    //         'currentPassword' => 'required',
    //         'newPassword' => 'required|min:6', // 'confirmed' ensures newPassword_confirmation is also sent
    //     ]);

    //     $user = Auth::user();

    //     // Check if the current password matches
    //     if (!Hash::check($request->currentPassword, $user->password)) {
    //         return response()->json(['message' => 'Current password is incorrect.'], 422);
    //     }


    //     // Update the user's password
    //     $user->password = Hash::make($request->newPassword);
    //     $user->save();

    //     return response()->json(['message' => 'Password changed successfully.']);
    // }

    
}
