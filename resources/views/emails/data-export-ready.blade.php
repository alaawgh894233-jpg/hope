@component('mail::message')
{{ $user->name }}



@component('mail::button', ['url' => $downloadUrl])

@endcomponent

{{ $expiresAt }}

@endcomponent
