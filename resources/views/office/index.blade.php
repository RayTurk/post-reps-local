@extends('layouts.auth')

@section('content')
@include('layouts.includes.alerts')
    <div class="container bg-white p-0 offices-page">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <ul class="nav nav-pills mb-3 ml-0" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="pills-offices-tab" data-toggle="pill" href="#pills-offices"
                            role="tab">Offices</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="pills-agents-tab" data-toggle="pill" href="#pills-agents"
                            role="tab">Agents</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="pills-installers-tab" data-toggle="pill" href="#pills-installers"
                            role="tab">Installers</a>
                    </li>
                </ul>
                <div class="tab-content p-2" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-offices" role="tabpanel">

                        <div class="card auth-card mb-2">
                            <div class="card-header d-flex justify-content-between">
                                <a href="{{ route('offices.create') }}" class="btn btn-primary">
                                    Add New Office
                                </a>
                                <h6 class="mt-2">Offices</h6>
                            </div>
                            <div class="card-body">

                                <div class="table-responsive">
                                    <table class="table table-hover " id="officesTable"> </table>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="tab-pane fade" id="pills-agents" role="tabpanel">
                        <div class="card auth-card mb-2">
                            <div class="card-header d-flex justify-content-between">
                                <a href="{{ route('offices.create') }}" class="btn btn-primary">
                                    Add New Agent
                                </a>
                                <h6 class="mt-2">Agents</h6>
                            </div>
                            <div class="card-body">

                                <div class="table-responsive">
                                    {{-- <table class="table table-hover " id="officesTable"> </table> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pills-installers" role="tabpanel">
                        <div class="card auth-card mb-2">
                            <div class="card-header d-flex justify-content-between">
                                <a href="{{ route('offices.create') }}" class="btn btn-primary">
                                    Add New Installer
                                </a>
                                <h6 class="mt-2">Installers</h6>
                            </div>
                            <div class="card-body">

                                <div class="table-responsive">
                                    {{-- <table class="table table-hover " id="officesTable"> </table> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_scripts')
    <script src="{{ mix('/js/user.js') }}" defer></script>
@endsection
