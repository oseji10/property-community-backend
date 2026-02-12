@component('mail::message')
# New Message Received

You have received a new message from **{{ $message->sender->firstName }} {{ $message->sender->lastName }}**.

**Subject / Property:**  
@if($message->property)  
{{ $message->property->propertyTitle }}  
@else  
General inquiry  
@endif

**Message:**  
{!! nl2br(e($message->content)) !!}

@component('mail::button', ['url' => url('/dashboard/messages')])
View & Reply in Dashboard
@endcomponent

Best regards,  
Property Plus Africa Team  
@endcomponent