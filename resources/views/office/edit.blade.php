@extends('layouts.auth')

@section('content')
    <div class="container">
        @include('layouts.includes.alerts')

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card auth-card">
                    <div class="card-header d-flex justify-content-between">
                        <h6 class="mt-2">
                            <a href="{{ route('offices.index') }}">Offices</a>
                            / Edit
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('offices.update',['office' => $office->id] ) }}" files=true enctype="multipart/form-data" >
                            @csrf
                            @method('PATCH')
                            <div class="row mt-1">
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <div>
                                            <label for="address"><b>Office Name</b></label>
                                            <input type="text" id="name"
                                                class="form-control  @error('name') is-invalid @enderror" name="name"
                                                value="{{ old('name',$office->name) }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <div>
                                            <label for="primary_contact"><b>Office Primary Contact Name</b></label>
                                            <input type="text" id="primary_contact"
                                                class="form-control  @error('primary_contact') is-invalid @enderror"
                                                name="primary_contact" value="{{ old('primary_contact',$office->primary_contact) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <div>
                                            <label for="address"><b>Office Street Address</b></label>
                                            <input type="text" id="street_address"
                                                class="form-control  @error('address') is-invalid @enderror" placeholder=""
                                                name="address" value="{{ old('address',$office->user->address) }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <div>
                                            <label for="phone"><b>Office Phone Number</b></label>
                                            <input type="text" id="phone"
                                                class="form-control  @error('phone') is-invalid @enderror phones"
                                                name="phone" value="{{ old('phone',$office->user->phone) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <div>
                                            <label for="city"><b>Office City</b></label>
                                            <input type="text" id="city"
                                                class="form-control  @error('city') is-invalid @enderror" placeholder=""
                                                name="city" value="{{ old('city',$office->user->city) }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <div>
                                            <label for="email"><b>Office Email</b></label>
                                            <input type="text" id="email"
                                                class="form-control @error('email') is-invalid @enderror " placeholder=""
                                                name="email" value="{{ old('email',$office->user->email) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-12 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <div>
                                            <label for="state"><b>Office State</b></label>
                                            <select id="state" class="form-control  @error('state') is-invalid @enderror"
                                                placeholder="" name="state">
                                                <option value=""></option>
                                                @if (count($states))
                                                    @foreach ($states as $code => $state)
                                                        <option value="{{ $code }}" @if (old('state',$office->user->state) === $code) selected @endif>
                                                            {{ $state }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <div>
                                            <label for="email"><b>Office Zipcode</b></label>
                                            <input type="text" id="zipcode"
                                                class="form-control @error('zipcode') is-invalid @enderror zipcode"
                                                name="zipcode" value="{{ old('zipcode',$office->user->zipcode) }}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <div>
                                            <label for="email"><b>Office Website</b></label>
                                            <input type="text" id="website"
                                                class="form-control @error('website') is-invalid @enderror " name="website"
                                                value="{{ old('website',$office->website) }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-12 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <div>
                                            <label for="inactive"><b>Account Status</b></label>
                                            <select id="inactive"
                                                class="form-control  @error('inactive') is-invalid @enderror"
                                                name="inactive">
                                                <option value="0" @if (old('inactive',$office->inactive) == 0) selected @endif><b
                                                        class="text-muted">Active</b></option>
                                                <option value="1" @if (old('inactive',$office->inactive) == 1) selected @endif><b
                                                        class="text-muted">Inactive</b></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <div>
                                            <label for="private"><b>Private/Public</b></label>
                                            <select id="private"
                                                class="form-control  @error('private') is-invalid @enderror" name="private">
                                                <option value="0" @if (old('private',$office->private) == 0) selected @endif><b
                                                        class="text-muted">Public</b></option>
                                                <option value="1" @if (old('private',$office->private) == 1) selected @endif><b
                                                        class="text-muted">Private</b></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <div>
                                            <label for="email"><b>Office Name Abbreviation</b></label>
                                            <input type="text" id="name_abbreviation"
                                                class="form-control @error('name_abbreviation') is-invalid @enderror "
                                                name="name_abbreviation" value="{{old('name_abbreviation',$office->name_abbreviation)}}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 col-lg-3">
                                    <div class="form-group">
                                        <div>
                                            <label for="logo_image" class="btn btn-primary text-white btn-sm"><b>Upload
                                                    Logo</b></label>
                                            <input type="file" name="logo_image" style="display: none" id="logo_image"
                                                accept="image/*" />
                                            <div class="logo_preview"><img src="{{url('/private/image/'.$office->logo_image)}}" /></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-5 col-lg-5">
                                    <div class="form-group">
                                        <div>
                                            <label for="region_id"><b>Office Region</b></label>
                                            <select id="region_id" class="form-control  @error('region_id') is-invalid @enderror"
                                                name="region_id">
                                                @foreach ($regions as $r)
                                                    <option value="{{ $r->id }}" @if (old('region_id',$office->user->region_id) == $r->id) selected @endif><b
                                                            class="text-muted">{{ $r->name }}</b>
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12 text-center">
                                    <button type="submit" class="btn btn-success">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
@section('page_scripts')
    <script src="{{ mix('/js/user.js') }}" defer></script>
@endsection
