@component('mail::message')
# New agent registered without office

Agent {{$agent->user->name}} has just registered but doesn't have any office assigned.<br>
Agent Email: {{$agent->user->email}}<br>
Agent Phone: {{$agent->user->phone}}<br>

{{-- @component('mail::button', ['url' => ''])
Button Text
@endcomponent --}}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
