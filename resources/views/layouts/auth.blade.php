<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
    <meta name="robots" content="noindex">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @yield('recaptcha_scripts')

    <title>{{ config('app.name', 'Post Reps') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    {{-- bootstrap-select library styles --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css" defer>
    {{--  krajee Bootstrap Star Rating styles --}}
    <link href="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-star-rating@4.0.7/css/star-rating.css" media="all" rel="stylesheet" type="text/css" defer/>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    <link rel="shortcut icon" href="{{asset('/storage/images/pr_icon.ico')}}" type="image/x-icon">
    <link rel="icon" href="{{asset('/storage/images/pr_icon.ico')}}" type="image/x-icon">

    <noscript>
        <h3> You must have JavaScript enabled in order to use this website. Please
            enable JavaScript and then reload this page in order to continue.
        </h3>
        <meta HTTP-EQUIV="refresh" content=0; url="https://www.enable-javascript.com/">
    </noscript>

    <!-- Google tag (gtag.js) -->
    <!-- <script async src="https://www.googletagmanager.com/gtag/js?id=G-YJ31R9VT3F"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-YJ31R9VT3F');
    </script> -->
</head>

<body class="blue-background">
    <div class="loading-overlay d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p><strong>Proccessing...</strong></p>
    </div>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark justify-content-between">
            <div class="container">
                <ul class="navbar-nav ml-auto desktop-view tablet-view">
                    <li class="nav-item">
                        <a class="btn btn-secondary font-weight-bold" href="{{ route('logout') }}" onclick="event.preventDefault();
                            localStorage.clear(); document.getElementById('logout-form').submit();" style="width: 120px;">
                            LOGOUT
                        </a><br>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                        <a class="btn btn-orange font-weight-bold mt-2" href="{{ url('/dashboard') }}"
                            style="width: 120px;">DASHBOARD</a>
                    </li>
                </ul>

                <ul class="navbar-nav ml-0 mr-0 mobile-view">
                    <a class="navbar-brand p-0" href="{{ url('/') }}">
                        <img src="{{ asset('/storage/images/logo.png') }}"
                            alt="{{ config('app.name', 'Post Reps') }}" style="width: 80px;">
                    </a>
                </ul>

                <ul class="navbar-nav ml-0 mobile-view">
                    <li class="nav-item">
                        <a class="btn btn-secondary btn-sm " href="{{ route('logout') }}" onclick="event.preventDefault();
                            localStorage.clear(); document.getElementById('logout-form').submit();" style="">
                            LOGOUT
                        </a>
                        <a class="btn btn-orange btn-sm"
                            href="{{ url('/dashboard') }}">DASHBOARD</a>
                    </li>
                </ul>

                <button class="navbar-toggler ml-1" type="button" data-toggle="collapse"
                    data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon" style="width: 35px"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-end ml-5 pl-5" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav pl-2 desktop-view tablet-view">
                        <li>
                            <h5 class="text-white">
                                Welcome {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                            </h5>
                        </li>
                    </ul>

                    <div class="navbar-nav pl-2 mobile-view text-white w-100"
                        style="background-color: #134185; font-size: 13px; z-index: 2147483647; position:fixed;">
                        <ul class="navbar-nav">
                            <li class="pb-2 text-center pl-5">
                                <span id="mobileAccountResourcesMenu">
                                    <h5 class="pl-4">X</h5>
                                </span>
                            </li>
                            <li class="pb-2">
                                <img src="{{ asset('/images/Orders_Icon.png') }}" style="width: 25px">
                                <a href="/order/status" class="text-white" title="Orders">ORDERS</a>
                            </li>
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/Financial_Icon.png') }}" style="width: 25px">
                                <a href="/accounting" class="text-white" title="Accounting">ACCOUNTING</a>
                            </li>
                            @can('Admin', auth()->user())
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/Area_Icon.png') }}" style="width: 25px">
                                <a href="{{ route('services.index') }}" class="text-white" title="Services">
                                    SERVICES
                                </a>
                            </li>
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/Users_Icon.png') }}" style="width: 25px">
                                <a href="{{ route('users.index') }}" class="text-white" title="Users">
                                    USERS
                                </a>
                            </li>
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/Inventory_Icon.png') }}" style="width: 25px">
                                <a href="{{ route('inventories.index') }}" class="text-white" title="Inventory">
                                    INVENTORY
                                </a>
                            </li>
                            <li class="pb-2">
                                <img src="{{ asset('/images/Com_Icon.png') }}" style="width: 25px">
                                <a href="/communications/notices" class="text-white" title="Communications">COMMUNICATIONS</a>
                            </li>
                            @elsecan('Office', auth()->user())
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/Users_Icon.png') }}" style="width: 25px">
                                <a href="{{ route('office.users.index') }}" class="text-white" title="Users">
                                    USERS
                                </a>
                            </li>
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/Inventory_Icon.png') }}" style="width: 25px">
                                <a href="{{ route('office.inventories.index') }}" class="text-white" title="Inventory">
                                    INVENTORY
                                </a>
                            </li>
                            @elsecan('Agent', auth()->user())
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/Inventory_Icon.png') }}" style="width: 25px">
                                <a href="{{ route('agent.inventories.index') }}" class="text-white" title="Inventory">
                                    INVENTORY
                                </a>
                            </li>
                            @endCan
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/Question_Icon.png') }}" style="width: 25px">
                                <a href="{{ url('/contact-us') }}" class="text-white" title="Contact Us">CONTACT US</a>
                            </li>
                            <li class="pb-2">
                                <img src="{{ asset('/storage/images/settings_Icon.png') }}" style="width: 25px">
                                <a href="{{ url('/settings') }}" class="text-white" title="Settings">SETTINGS</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto mr-0 desktop-view tablet-view">
                        <a class="navbar-brand" href="{{ url('/') }}">
                            <img src="{{ asset('/storage/images/logo.png') }}"
                                alt="{{ config('app.name', 'Post Reps') }}" style="width: 150px;">
                        </a>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="mobile-view">
            <div class="d-flex flex-row justify-content-around">
                <div class="text-white btn btn-sm py-1 px-1 m-1 open_install_post_modal" style="background: #0a1a30; border-left: 1px solid #dedede;" title="Install Order">
                    <img src="{{asset('/storage/images/Install_Icon.png')}}" class="" style="width: 20px;" alt="Install">
                    INSTALLATION
                </div>
                <div class="text-white btn btn-sm py-1 px-1 m-1 order-repair" style="background: #0a1a30; border-left: 1px solid #dedede;" title="Repair Order">
                    <img src="{{asset('/storage/images/Repair_Icon.png')}}" style="width: 20px;" alt="Repair">
                    REPAIR
                </div>
                <div class="text-white btn btn-sm py-1 px-1 m-1 order-removal" style="background: #0a1a30; border-left: 1px solid #dedede;" title="Removal Order">
                    <img src="{{asset('/storage/images/Removal_Icon.png')}}" style="width: 20px;" alt="Removal">
                    REMOVAL
                </div>
                <div class="text-white btn btn-sm py-1 px-1 m-1 order-delivery" style="background: #0a1a30; border-left: 1px solid #dedede;" title="Delivery Order">
                    <img src="{{asset('/storage/images/Deliver_Icon.png')}}" style="width: 20px;" alt="Delivery">
                    DELIVERY
                </div>
            </div>
        </div>

        {{-- <div
            class="container mobile-view py-1 px-1"
            style="background-color: #FF9047; color: rgba(17, 15, 15, 0.911)">
            <div class="order-bar-mobile d-flex justify-content-between">
                <a class="btn btn-success btn-sm px-2" href="">INSTALLATION</a>
                <a class="btn btn-primary btn-sm px-2" href="">REPAIR</a>
                <a class="btn btn-danger btn-sm px-2" href="">REMOVAL</a>
                <a class="btn btn-info btn-sm px-2" href="">DELIVERY/PICKUP</a>
            </div>
        </div> --}}

        <main class="py-4 px-0">
            @yield('content')
        </main>

        {{-- FOOTER --}}
        <div class="container-fluid mt-5 pt-4">
            <footer class="py-2 mt-5" style="background-color: transparent;">
                <div class="row text-white">
                    <div class="col-md-4 text-center ">
                        &copy {{ date('Y') }} {{ config('app.name') }}. All Rights Reserved.
                    </div>
                    <div class="col-md-4 text-center ">
                        <a class="text-white" href="https://www.facebook.com/PostReps/" target="_blank">
                            <i class="fab fa-facebook-square" style="font-size: 22px"></i>
                        </a>
                    </div>
                    <div class="col-md-4 text-center d-flex flex-column flex-lg-row">
                        <a href="{{ url('/terms') }}" class="text-white">Terms & Conditions</a>
                        <a href="{{ url('/privacy') }}" class="ml-4 text-white">Privacy Policy</a>
                    </div>
                </div>
            </footer>
        </div>

    </div>

    <!-- Scripts -->
    <script src="{{ mix('/js/manifest.js') }}" defer></script>
    <script src="{{ mix('/js/vendor.js') }}" defer></script>
    <script src="{{ mix('/js/app.js') }}" defer></script>
    <script src="{{ mix('/js/dashboard.js') }}" defer></script>

    @yield('page_scripts')
    {{-- bootstrap-select library js --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js" defer></script>
    {{--  krajee Bootstrap Star Rating styles --}}
    <script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-star-rating@4.0.7/js/star-rating.js" type="text/javascript" defer></script>

    @include('layouts.includes.error_modal')

    @include('layouts.includes.message_model')

    @include('inventory.accessory.document_modal')

    @include('layouts.includes.confirm_modal')

    @include('layouts.includes.loader_image_modal')

    @include('layouts.includes.card_declined_modal')
    @include('layouts.includes.action_needed_modal')

    <input type="hidden" id="userRole" value="{{auth()->user()->role}}">
    <input type="hidden" id="userId" value="{{auth()->user()->id}}">
    @if (auth()->user()->role != 1)
    <input type="hidden" id="officeId" value="{{auth()->user()->office ? auth()->user()->office->id : auth()->user()->agent->agent_office}}">
    <input type="hidden" id="agentId" value="{{auth()->user()->agent->id ?? 0}}">
    @endif
</body>

</html>
