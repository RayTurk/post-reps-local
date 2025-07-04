@extends('layouts.public')

@section('content')
    <div class="container">
        <div class="text-center mt-5"><h3>Locations Served</h3></div>

        <div class="text-block pl-5 mt-3">
            <div class="text-center"><b>We currently serve the following locations</b></div>
            <div class="row mt-2">
                <div class="col-md-2"></div>
                <div class="col-md-4">
                    <ul>
                        <li>Boise</li>
                        <li>Meridian</li>
                        <li>Nampa</li>
                        <li>Caldwell</li>
                        <li>Star</li>
                        <li>Eagle</li>
                        <li>Middleton</li>
                        <li>Kuna</li>
                        <li>Emmett</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <ul>
                        <li>Mountain Home</li>
                        <li>Payette</li>
                        <li>New Plymouth</li>
                        <li>Fruitland</li>
                        <li>Parma</li>
                        <li>Homedale</li>
                        <li>Marsing</li>
                        <li>Melba</li>
                        <li>Surrounding areas</li>
                    </ul>
                </div>
                <div class="col-md-2"></div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-2"></div>
            <div class="col-md-9 text-center">
                {{-- <img class="img-fluid" src="{{asset('/storage/images/coverage.jpg')}}" alt="Coverage Area"> --}}
                <div id="zones-map" class="zones-map col-12 col-lg-10 col-md-10 width-rem-60 height-rem-48 bg-dark"></div>
            </div>
            <div class="col-md-1"></div>
        </div>
        <!-- Boise, Meridian, Nampa, Caldwell, Star, Eagle, Middleton, Kuna, Emmett, Mountain Home, Payette, New Plymouth, Fruitland, Parma, Homedale, Marsing, Melba, and surrounding areas. -->
    </div>
@endsection

@section('page_scripts')
    <script src="{{ mix('/js/public.js') }}" defer></script>
@endsection
