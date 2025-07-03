@component('mail::message')
# New Agent registration

Agent Name: {{$agent->user->name}}<br>
Agent Email: {{$agent->user->email}}<br>
Agent Phone: {{$agent->user->phone}}<br>

{{-- @component('mail::button', ['url' => ''])
Button Text
@endcomponent --}}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
