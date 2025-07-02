@extends('layouts.auth')

@section('content')

    @include('service.zones')
    <div class="menu-bar pb-3 adjust-menu-bar desktop-view tablet-view">
        @include('layouts.includes.account_resources')
    </div>

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/service.js') }}" defer></script>


@endsection
