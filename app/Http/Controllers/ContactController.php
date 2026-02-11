<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $key = 'contact-form:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) { // matches the 5/min defined above
            return response()->json([
                'error' => 'Too many messages. Please try again in a minute.',
            ], 429);
        }

        RateLimiter::hit($key); // increment attempt count

        // 2. Honeypot check (very effective against bots)
        // Field name: my_website_url (or whatever you choose – bots often fill hidden/all fields)
        if ($request->filled('my_website_url')) {
            // Silently reject spam (no error message to the bot)
            // You could log it if you want: 
            \Log::warning('Honeypot caught spam from ' . $request->ip());
            return response()->json([
                'message' => 'Thank you! Your message has been sent successfully.',
            ], 200); // pretend success so bot thinks it worked
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|min:2|max:100',
            'mobile'   => 'required|string|min:8|max:20',
            'email'    => 'required|email|max:100',
            'message'  => 'required|string|min:10|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        ContactMessage::create([
    'fullname'    => $data['fullname'],
    'email'       => $data['email'],
    'mobile'      => $data['mobile'],
    'message'     => $data['message'],
    'ip_address'  => $request->ip(),
]);

$recipients = array_filter([
    config('mail.contact_to', 'support@propertyplusafrica.com'),
    'uhuanghoehiosu@gmail.com',
    'ayouhuangho@gmail.com',
    'vctroseji@gmail.com',
]);

// Mail::to($recipients)->queue(new ContactMessageReceived(/* ... */));

        // Send email (queued = better UX)
        Mail::to($recipients)
            ->send(new ContactMessageReceived(
                fullname: $data['fullname'],
                email: $data['email'],
                mobile: $data['mobile'],
                message: $data['message']
            ));

        return response()->json([
            'message' => 'Thank you! Your message has been sent successfully.',
        ], 200);
    }
}