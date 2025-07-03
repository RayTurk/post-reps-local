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
        @if ($orderType == 'install')
            <div class="row installer-order-card font-px-15 w-100 height-px-120 mb-2 ml-0 overflow-x-auto overflow-y-auto">
                <div class="col-2 text-left px-2 py-3" >
                    <img src="{{asset('storage/images/Install_Icon.png')}}" title="Edit" alt="Edit" class="width-px-55" >
                </div>
                <div class="col-10 py-1 pl-3" >
                    <div class="text-success-dark font-weight-bold d-flex justify-content-between" >
                        <div>Install: {{$order->post->post_name}}</div>
                        @can('Installer', auth()->user())
                        <div class="font-weight-bold font-px-20 text-dark close-order">X</div>
                        @endCan
                    </div>
                    @can('Installer', auth()->user())
                    <span ><a style="color: blue;" href="{{url('/installer/map/view/install')}}/{{$order->id}}">{{$order->address}}</a></span><br>
                    @else
                    <span style="color: blue;">{{$order->address}}</span><br>
                    @endif
                    <div class="row">
                        @if ($order->agent)
                        <div class="col-6">
                            {{$order->agent->user->name}}<br>
                            Ph: <a style="color: blue;" href="tel:{{$order->agent->user->phone}}">{{$order->agent->user->phone}}</a>
                        </div>
                        @endif
                        <div class="{{! $order->agent ? 'col-12' : 'col-6'}}">
                        {{$order->office->user->name}}<br>
                        Ph: <a style="color: blue;" href="tel:{{$order->office->user->phone}}">{{$order->office->user->phone}}</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if ($orderType == 'repair')
            <div class="row installer-order-card font-px-15 w-100 height-px-115 mb-2 ml-0 overflow-x-auto overflow-y-auto">
                <div class="col-2 text-left px-2 py-3" >
                    <img src="{{asset('storage/images/Repair_Icon.png')}}" title="Edit" alt="Edit" class="width-px-55" >
                </div>
                <div class="col-10 py-1 pl-1" >
                    <div class="text-primary-dark font-weight-bold d-flex justify-content-between" >
                        <div>Repair: {{$order->order->post->post_name}}</div>
                        @can('Installer', auth()->user())
                        <div class="font-weight-bold font-px-20 text-dark close-order">X</div>
                        @endCan
                    </div>
                    @can('Installer', auth()->user())
                    <span ><a style="color: blue;" href="{{url('/installer/map/view/repair')}}/{{$order->id}}">{{$order->order->address}}</a></span><br>
                    @else
                    <span style="color: blue;">{{$order->order->address}}</span><br>
                    @endif
                    <div class="row">
                        @if ($order->order->agent)
                        <div class="col-6">
                            {{$order->order->agent->user->name}}<br>
                            Ph: <a style="color: blue;" href="tel:{{$order->order->agent->user->phone}}">{{$order->order->agent->user->phone}}</a>
                        </div>
                        @endif
                        <div class="{{! $order->order->agent ? 'col-12' : 'col-6'}}">
                        {{$order->order->office->user->name}}<br>
                        Ph: <a style="color: blue;" href="tel:{{$order->order->office->user->phone}}">{{$order->order->office->user->phone}}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 text-center">
                    <a
                        href="#"
                        class="link font-weight-bold font-px-16"
                        data-toggle="modal"
                        data-target="#repairHistoryModal"
                    >View History</a>
                </div>
            </div>
        @endif
        @if ($orderType == 'removal')
            <div class="row installer-order-card font-px-15 w-100 height-px-115 mb-2 ml-0 overflow-x-auto overflow-y-auto">
                <div class="col-2 text-left px-2 py-3" >
                    <img src="{{asset('storage/images/Removal_Icon.png')}}" title="Edit" alt="Edit" class="width-px-55" >
                </div>
                <div class="col-10 py-1 pl-1" >
                    <div class="text-danger font-weight-bold d-flex justify-content-between" >
                        <div>Removal: {{$order->order->post->post_name}}</div>
                        @can('Installer', auth()->user())
                        <div class="font-weight-bold font-px-20 text-dark close-order">X</div>
                        @endCan
                    </div>
                    @can('Installer', auth()->user())
                    <span ><a style="color: blue;" href="{{url('/installer/map/view/removal')}}/{{$order->id}}">{{($order->pickup_address != 'null' && !is_null($order->pickup_address)) ? $order->pickup_address : $order->order->address}}</a></span><br>
                    @else
                        @php $address = $order->pickup_address ? $order->pickup_address : $order->order->address; @endphp
                        <span style="color: blue;">{{$address}}</span><br>
                    @endif
                    <div class="row">
                        @if ($order->order->agent)
                        <div class="col-6">
                            {{$order->order->agent->user->name}}<br>
                            Ph: <a style="color: blue;" href="tel:{{$order->order->agent->user->phone}}">{{$order->order->agent->user->phone}}</a>
                        </div>
                        @endif
                        <div class="{{! $order->order->agent ? 'col-12' : 'col-6'}}">
                        {{$order->order->office->user->name}}<br>
                        Ph: <a style="color: blue;" href="tel:{{$order->order->office->user->phone}}">{{$order->order->office->user->phone}}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-12 text-center">
                    <a
                        href="#"
                        class="link font-weight-bold font-px-16"
                        data-toggle="modal"
                        data-target="#removalHistoryModal"
                    >View History</a>
                </div>
            </div>
        @endif
        @if ($orderType == 'delivery')
            <div class="row installer-order-card font-px-15 w-100 height-px-115 mb-2 ml-0 overflow-x-auto overflow-y-auto">
                <div class="col-2 text-left px-2 py-3" >
                    <img src="{{asset('storage/images/Deliver_Icon.png')}}" title="Edit" alt="Edit" class="width-px-55" >
                </div>
                <div class="col-10 py-1 pl-1" >
                    <div class="text-success-dark font-weight-bold d-flex justify-content-between" >
                        <div style="color:#ad6333;">Delivery: {{$order->pickup_delivery}}</div>
                        @can('Installer', auth()->user())
                        <div class="font-weight-bold font-px-20 text-dark close-order">X</div>
                        @endCan
                    </div>
                    @can('Installer', auth()->user())
                    <span ><a style="color: blue;" href="{{url('/installer/map/view/delivery')}}/{{$order->id}}">{{$order->address}}</a></span><br>
                    @else
                    <span style="color: blue;">{{$order->address}}</span><br>
                    @endif
                    <div class="row">
                        @if ($order->agent)
                        <div class="col-6">
                            {{$order->agent->user->name}}<br>
                            Ph: <a style="color: blue;" href="tel:{{$order->agent->user->phone}}">{{$order->agent->user->phone}}</a>
                        </div>
                        @endif
                        <div class="{{! $order->agent ? 'col-12' : 'col-6'}}">
                        {{$order->office->user->name}}<br>
                        Ph: <a style="color: blue;" href="tel:{{$order->office->user->phone}}">{{$order->office->user->phone}}</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <div class="container-fluid overflow-y-auto overflow-x-hidden" id="contentDiv" style="margin-top: 130px; min-height: 700px;">
        <div class="row px-3">
            <div class="col-12">
                <label for="agent_comments" class="text-dark d-block w-100 mb-0">
                    Agent Comments:
                </label>
                <textarea class="form-control" rows="3" id="agent_comments" readonly>{{$order->comment}}</textarea>
            </div>
        </div>

        @if ($orderType == 'install')
            @include('users.installer.order_details_install')
        @endif
        @if ($orderType == 'repair')
            @include('users.installer.order_details_repair')
        @endif
        @if ($orderType == 'removal')
            @include('users.installer.order_details_removal')
        @endif
        @if ($orderType == 'delivery')
            @include('users.installer.order_details_delivery')
        @endif

        <div class="row px-3 mt-3">
            <div class="col-12">
                <label for="order_comment" class="text-dark d-block w-100 text-right"><b>(<span
                    class="char-used">0</span> of 500 characters Used)</b></label>
                <textarea
                    class="form-control"
                    rows="4"
                    id="installerComments"
                    maxlength="500"
                    placeholder="Comments (Max 500 characters)"
                    required
                ></textarea>
            </div>
        </div>

        <div class="row px-3 mt-3">
            <div class="col-2">
                <input id="addressVerified" type="checkbox" class="form-control w-h-px-30" id="panelCheckbox">
            </div>
            <div class="col-10 pl-0 pt-1 font-weight-bold font-px-16" style="color:#ad6333;">
                Address Verified
            </div>
        </div>

        <div class="row px-3 mt-2">
            <div class="col-12 text-center font-px-18 font-weight-bold" style="color: #1a4485">
                UPLOAD PHOTOS (Max 3)
            </div>
        </div>
        <div class="row pl-30px">
            <div class="col-3 font-px-45 text-center bg-dark height-px-70 p-0" id="installPhotoDiv1">
                <i class="fa fa-camera text-white" id="photoIcon1"></i>
                <img src="" id="photo1" class="d-none" style="margin-top: -9px; max-width: 96px; max-height: 70px;">
            </div>
            <div class="col-1"></div>
            <div class="col-3 font-px-45 text-center bg-dark height-px-70 p-0" id="installPhotoDiv2">
                <i class="fa fa-camera text-white" id="photoIcon2"></i>
                <img src="" id="photo2" class="d-none" style="margin-top: -9px; max-width: 96px; max-height: 70px;">
            </div>
            <div class="col-1"></div>
            <div class="col-3 font-px-45 text-center bg-dark height-px-70 p-0" id="installPhotoDiv3">
                <i class="fa fa-camera text-white" id="photoIcon3"></i>
                <img src="" id="photo3" class="d-none" style="margin-top: -9px; max-width: 96px; max-height: 70px;">
            </div>
        </div>

        <input type="file" name="installation_photos[]" data-index="1" class="d-none" id="fileUpload1" accept="image/*">
        <input type="file" name="installation_photos[]" data-index="2" class="d-none" id="fileUpload2" accept="image/*">
        <input type="file" name="installation_photos[]" data-index="3" class="d-none" id="fileUpload3" accept="image/*">

        <div class="row mt-5 font-px-15 w-100 height-px-75 mb-2 ml-0" >
            <div class="col-12 text-center">
                @if ($orderType == 'install')
                    <button class="btn btn-success mt-3 mb-3 font-px-16 font-weight-bold width-px-120" id="markInstallCompleteBtn">SUBMIT</button>
                    <br><br><br>
                @endif
                @if ($orderType == 'repair')
                    <button class="btn btn-success mt-3 mb-3 font-px-16 font-weight-bold width-px-120" id="markRepairCompleteBtn">SUBMIT</button>
                    <br><br><br>
                @endif
                @if ($orderType == 'removal')
                    <button class="btn btn-success mt-3 mb-3 font-px-16 font-weight-bold width-px-120" id="markRemovalCompleteBtn">SUBMIT</button>
                    <br><br><br>
                @endif
                @if ($orderType == 'delivery')
                    <button class="btn btn-success mt-3 mb-3 font-px-16 font-weight-bold width-px-120" id="markDeliveryCompleteBtn">SUBMIT</button>
                    <br><br><br>
                @endif
            </div>
        </div>

    </div>

    <!-- <div class="container-fluid auth-card" style="position: fixed; max-width: 800px; z-index: 1000; bottom: -10px;">
        <div class="row installer-order-card font-px-15 w-100 height-px-75 mb-2 ml-0 overflow-x-auto overflow-y-auto">
            <div class="col-12 text-center">
                <button class="btn btn-success mt-3 font-px-16 font-weight-bold">MARK COMPLETE</button>
            </div>
        </div>
    </div> -->

    <!-- Scripts -->
    <script src="{{ mix('/js/manifest.js') }}" defer></script>
    <script src="{{ mix('/js/vendor.js') }}" defer></script>
    <script src="{{ mix('/js/app.js') }}" defer></script>


    <script src="{{ mix('/js/user.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier/1.0.3/oms.min.js"></script>

    @include('layouts.includes.error_modal')
    @include('layouts.includes.confirm_modal')
    @include('layouts.includes.loader_image_modal')
    @include('layouts.includes.message_model')

    <input type="hidden" id="installerId" value="{{auth()->id()}}">
    <input type="hidden" id="orderId" value="{{$order->id}}">
    <input type="hidden" id="orderType" value="{{$orderType}}">
    <input type="hidden" id="userRole" value="{{auth()->user()->role}}">
</body>

</html>
