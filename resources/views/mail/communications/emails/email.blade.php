@component('mail::message')

<p>{!! nl2br(e($message))!!}</p>

Thanks,<br>
{{ config('app.name') }}
@endcomponent
