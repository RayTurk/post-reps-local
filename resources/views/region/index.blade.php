@extends('layouts.auth')

@section('content')
    <div class="container-fluid">
        @include('layouts.includes.alerts')
        <div class="row justify-content-center">
            <div class="col-md-1 pb-3">
                @include('layouts.includes.order_bar_icons')
            </div>
            <div class="col-md-10">
                <div class="card auth-card">
                    <div class="card-header d-flex justify-content-between">
                        <a href="{{url('/regions/create')}}" class="btn btn-primary">
                            Add New Region
                        </a>
                        <h6 class="mt-2">Regions</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="regionsTable">
                                <thead>
                                    <th>Region Name</th>
                                    <th>Date Created</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </thead>
                                <tbody>
                                    @if ($regions->isNotEmpty())
                                        @foreach ($regions as $region)
                                            <tr>
                                                <td class="text-center">{{$region->name}}</td>
                                                <td class="text-center">{{$region->created_at->format('m/d/Y')}}</td>
                                                <td class="text-center">{{$region->updated_at->format('m/d/Y')}}</td>
                                                <td class="text-center" >
                                                    <a href="{{url('/regions/'.$region->id.'/edit')}}" class="btn btn-info btn-sm" title="Edit">
                                                        Edit
                                                    </a>
                                                    <a
                                                        class="btn btn-danger ml-3 btn-sm deleteRegionBtn"
                                                        data-id="{{$region->id}}"
                                                    >
                                                        Delete
                                                        <form
                                                            id="deleteRegionForm{{$region->id}}"
                                                            style="display:block;"
                                                            method="post"
                                                            action="{{url('/regions')}}/{{$region->id}}"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 pb-3 d-flex justify-content-end">
                @include('layouts.includes.account_resources_icons')
            </div>
        </div>
    </div>

    @include('layouts.includes.install_modal');
    @include('layouts.includes.payment_modal');
    @include('layouts.includes.edit_order');
    @include('layouts.includes.rush_order_modal');
    @include('layouts.includes.duplicated_order_modal');
    @include('layouts.includes.pricing_adjustment_modal');
@endsection

@section('page_scripts')
    <script src="{{ mix('/js/region.js') }}" defer></script>
@endsection
