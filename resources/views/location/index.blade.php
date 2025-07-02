@extends('layouts.auth')

@section('content')
    <div class="container">
        @include('layouts.includes.alerts')
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card auth-card">
                    <div class="card-header d-flex justify-content-between">
                        <a href="{{url('/locations/create')}}" class="btn btn-primary">
                            Add New Region
                        </a>
                        <h6 class="mt-2">Locations</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="locationsTable">
                                <thead>
                                    <th>Region Name</th>
                                    <th>Date Created</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </thead>
                                <tbody>
                                    @if ($locations->isNotEmpty())
                                        @foreach ($locations as $location)
                                            <tr>
                                                <td class="text-center">{{$location->name}}</td>
                                                <td class="text-center">{{$location->created_at->format('m/d/Y')}}</td>
                                                <td class="text-center">{{$location->updated_at->format('m/d/Y')}}</td>
                                                <td class="text-center" >
                                                    <a href="{{url('/locations/'.$location->id.'/edit')}}" class="btn btn-info btn-sm" title="Edit">
                                                        Edit
                                                    </a>
                                                    <a
                                                        class="btn btn-danger ml-3 btn-sm deleteLocationBtn"
                                                        data-id="{{$location->id}}"
                                                    >
                                                        Delete
                                                        <form
                                                            id="deleteLocationForm{{$location->id}}"
                                                            style="display:block;"
                                                            method="post"
                                                            action="{{url('/locations')}}/{{$location->id}}"
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
        </div>
    </div>
@endsection

@section('page_scripts')
    <script src="{{ mix('/js/location.js') }}" defer></script>
@endsection
