<x-mail::message>
# 📬 New Message Received

Hello **{{ $recipient->firstName ?? 'User' }} {{ $recipient->lastName ?? '' }}**,

You have received a new message from **{{ $sender->firstName ?? 'User' }} {{ $sender->lastName ?? '' }}**.

<x-mail::panel>
{{ $message->content ?? 'No content available' }}
</x-mail::panel>

**From:** {{ $sender->firstName ?? 'User' }} {{ $sender->lastName ?? '' }}
@if($sender && $sender->email)
**Email:** {{ $sender->email }}
@endif
**Sent:** {{ isset($message->created_at) ? $message->created_at->format('F j, Y \a\t g:i A') : 'N/A' }}

@if($message->property)
**Property:** {{ $message->property->propertyTitle ?? 'N/A' }}
@if(isset($message->property->address))
**Location:** {{ $message->property->address ?? '' }}, {{ $message->property->city ?? '' }}, {{ $message->property->state ?? '' }}
@endif
@endif

<x-mail::button :url="url('/dashboard/messages')" color="primary">
View Message
</x-mail::button>

This message was sent to you from your Property Plus account. Please do not reply to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>