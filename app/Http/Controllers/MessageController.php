<?php
// app/Http/Controllers/Api/MessageController.php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
   
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipientId' => 'required|exists:users,id',
            'propertyId'  => 'nullable|exists:properties,propertyId',
            'message'     => 'required|string|min:10|max:5000',
        ]);

        // if (Auth::id() === $validated['recipientId']) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Cannot send message to yourself'
        //     ], 422);
        // }

        $message = Message::create([
            'senderId'    => Auth::id(),
            'receiverId'  => $validated['recipientId'],
            'propertyId'  => $validated['propertyId'] ?? null,
            'content'      => $validated['message'],
            'isRead'      => false,
        ]);

        // Queue email notification to receiver
    $receiver = $message->receiver;
    if ($receiver->email_verified_at) { // only if email is verified
        \Illuminate\Support\Facades\Mail::to($receiver->email)
            ->queue(new \App\Mail\NewMessageReceived($message));
    }

        return response()->json([
            'status' => 'success',
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }




//     public function inbox()
// {
//     $messages = Message::where('receiverId', Auth::id())
//         ->with(['sender', 'property'])
//         ->latest()
//         ->get();

//     return response()->json([
//         'status' => 'success',
//         'data' => $messages
//     ]);
// }


public function inbox(Request $request)
{
    $query = Message::where('receiverId', Auth::id())
        ->with(['sender:id,firstName,lastName,email', 'property:propertyId,propertyTitle,slug']);

    // Search by keyword in content or sender name/email
    if ($search = $request->input('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('content', 'like', "%{$search}%")
              ->orWhereHas('sender', function ($sq) use ($search) {
                  $sq->where('firstName', 'like', "%{$search}%")
                     ->orWhere('lastName', 'like', "%{$search}%")
                     ->orWhere('email', 'like', "%{$search}%");
              });
        });
    }

    // Filter by read/unread
    if ($request->filled('status')) {
        if ($request->status === 'unread') {
            $query->where('isRead', false);
        } elseif ($request->status === 'read') {
            $query->where('isRead', true);
        }
    }

    // Date range filter (optional)
    if ($start = $request->input('start_date')) {
        $query->whereDate('created_at', '>=', $start);
    }
    if ($end = $request->input('end_date')) {
        $query->whereDate('created_at', '<=', $end);
    }

    $messages = $query->latest()->paginate(15);

    return response()->json([
        'status' => 'success',
        'data' => $messages->items(),
        'pagination' => [
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'per_page' => $messages->perPage(),
            'total' => $messages->total(),
        ]
    ]);
}


public function reply(Request $request, Message $message)
{
    // Ensure the message belongs to the authenticated user
    if ($message->receiverId !== Auth::id()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $validated = $request->validate([
        'content' => 'required|string|min:10|max:5000',
    ]);

    $reply = Message::create([
        'senderId'    => Auth::id(),
        'receiverId'  => $message->senderId,
        'propertyId'  => $message->propertyId,
        'content'      => $validated['content'],
        'isRead'      => false,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Reply sent',
        'data' => $reply
    ], 201);
}

public function markAsRead(Message $message)
{
    if ($message->receiverId !== Auth::id()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $message->update(['isRead' => true]);

    return response()->json([
        'status' => 'success',
        'message' => 'Marked as read'
    ]);
}


public function unreadCount()
{
    $count = Message::where('receiverId', Auth::id())
        ->where('isRead', false)
        ->count();

    return response()->json([
        'status' => 'success',
        'unreadCount' => $count
    ]);
}

}