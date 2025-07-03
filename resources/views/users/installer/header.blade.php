<!doctype html>
<html style="height: 100%; overflow: hidden" lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="background-color: #134185cc;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Post Reps') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    <noscript>
        <h3> You must have JavaScript enabled in order to use this website. Please
            enable JavaScript and then reload this page in order to continue.
        </h3>
        <meta HTTP-EQUIV="refresh" content=0; url="https://www.enable-javascript.com/">
    </noscript>

</head>

<body style="margin: auto; max-width: 800px; height: 100%; " class="auth-card">
    <div class="container-fluid auth-card" style="position: fixed; max-width: 800px; z-index: 1000">
        <div class="row margin-top-10px" style="margin-right: 0;">
            <div class="col-md-11 col-10 font-px-20 pl-5">
                Welcome, {{auth()->user()->first_name}}.
            </div>
            <div class="col-md-1 col-2 text-right" >
                <span id="installerToggler">
                    <i class="fa fa-bars installer-menu-toggle font-px-30" ></i>
                </span>
                <div class="d-none" id="installerSettings">
                    <ul class="navbar-nav pl-0">
                        <li class="pb-2 text-left pt-1 pl-2">
                            <a href="#" data-toggle="modal" data-target="#changePasswordModal">Change Password</a>
                        </li>
                        <li class="pb-2 text-left pl-2 pt-1">
                            <form action="{{ route('logout') }}" method="post" id="logoutForm">
                                @csrf
                                <a onclick="document.getElementById('logoutForm').submit()">
                                    Logout
                                </a>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row margin-top-5px" style="margin-right: 0; z-index: 100;">
            <div class="col-md-12 col-12 pl-5">
                @php
                    use Carbon\Carbon;
                    $today = Carbon::now();
                    $tomorrow = Carbon::tomorrow();
                    $formattedToday = 'Today - ' . $today->format('F jS, Y');
                    $formattedTomorrow = 'Tomorrow - ' . $tomorrow->format('F j, Y');
                    $todayVal = $today->format('Y-m-d');
                    $tomorrowVal = $tomorrow->format('Y-m-d');

                    $routeDate = $routeDate ?? $todayVal;
                @endphp
                <select id="installerRouteDateSelect" class="form-control mt-1 mb-1 font-px-16">
                    <option value="{{$todayVal}}" {{$routeDate == $todayVal ? 'selected' : ''}}>{{$formattedToday}}</option>
                    <option value="{{$tomorrowVal}}" {{$routeDate == $tomorrowVal ? 'selected' : ''}}>{{$formattedTomorrow}}</option>
                </select>
            </div>
        </div>

        <div class="row margin-top-5px" style="margin-right: 0; z-index: 100;">
            <div class="col-md-12 pr-0">
                <div class="row installer-menu-bar ">
                    <div class="col-md-4 col-4 text-center menu-item" id="installerRoute">
                        <a class="font-weight-bold text-white" style="padding-left:16px;" href="{{url('dashboard')}}/{{$routeDate}}" >Route</a>
                    </div>
                    <div class="col-md-4 col-4 text-center menu-item" id="installerMapView">
                        <a class="font-weight-bold text-white" href="{{url('/installer/map/view')}}/{{$routeDate}}">Map View</a>
                    </div>
                    <div class="col-md-4 col-4 text-center menu-item" id="installerPullList">
                        <a class="font-weight-bold text-white" style="padding-right:14px;" href="{{url('/installer/pull/list')}}/{{$routeDate}}">Pull List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid installer-details-container" id="contentDiv">
        @yield('content')
    </div>


    <!-- Scripts -->
    <script src="{{ mix('/js/manifest.js') }}" defer></script>
    <script src="{{ mix('/js/vendor.js') }}" defer></script>
    <script src="{{ mix('/js/app.js') }}" defer></script>
    <script src="{{ mix('/js/user.js') }}" defer></script>


    @yield('page_scripts')

    @include('layouts.includes.error_modal')
    @include('layouts.includes.confirm_modal')
    @include('layouts.includes.loader_image_modal')
    @include('layouts.includes.message_model')

    <input type="hidden" id="installerId" value="{{auth()->id()}}">
</body>

</html>
