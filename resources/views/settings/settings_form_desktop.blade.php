<form id="accoutnSettingsForm" action="/settings/{{$user->id}}/update" method="POST">
    @method('PUT')
    @csrf
    <input type="hidden" name="id" value="{{$user->id}}">
    <div class="">
        <div class="auth-card">
            <div class="modal-header">
                <h5 class="modal-title text-uppercase font-weight-bold" id="exampleModalLabel">Account
                    Settings</h5>
            </div>
            <div class="modal-body">
                <div class="row mt-1">
                    @canany(['Agent','Admin'], auth()->user())
                    <div class="col-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <div>
                                <label for="first_name"><b>First Name</b></label>
                                <input type="text" id="first_name" tabindex="1"
                                    class="form-control  @error('first_name') is-invalid @enderror"
                                    name="first_name" value="{{ $user->first_name }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <div>
                                <label for="last_name"><b>Last Name</b></label>
                                <input type="text" id="last_name" tabindex="2"
                                    class="form-control  @error('last_name') is-invalid @enderror"
                                    name="last_name" value="{{ $user->last_name }}" required>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="col-12 col-md-12col-lg-12">
                        <div class="form-group">
                            <div>
                                <label for="name"><b>Name</b></label>
                                <input type="text" id="name" tabindex="1"
                                    class="form-control  @error('name') is-invalid @enderror"
                                    name="name" value="{{ $user->name }}" required disabled>
                            </div>
                        </div>
                    </div>
                    @endCan
                </div>
                <div class="row mt-1">
                    <div class="col-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <div>
                                <label for="address"><b>Street Address</b></label>
                                <input type="text" id="street_address" tabindex="3"
                                    class="form-control  @error('address') is-invalid @enderror"
                                    placeholder="" name="address" value="{{ $user->address }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="{{$user->role == 2 || $user->role == 3 ? 'col-12 col-md-3 col-lg-3' : 'col-12 col-md-6 col-lg-6'}}">
                        <div class="form-group">
                            <div>
                                <label for="email"><b>Email</b></label>
                                <input type="email" id="email" tabindex="7"
                                    class="form-control @error('email') is-invalid @enderror "
                                    placeholder="" name="email" value="{{ $user->email }}" required>
                            </div>
                        </div>
                    </div>
                    @can('office', auth()->user())
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="text-center margin-top-40px">
                                <a href="#" class="font-weight-bold" data-toggle="modal" data-target="#emailNotificationSettingsModal" id="additionalSettingsBtn">Additional Settings...</a>
                            </div>
                        </div>
                        @elsecan('agent', auth()->user())
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="text-center margin-top-40px">
                                <a href="#" class="font-weight-bold" data-toggle="modal" data-target="#emailNotificationSettingsModalAgent" id="agentAdditionalSettingsBtn">Additional Settings...</a>
                            </div>
                        </div>
                    @endcan
                </div>
                <div class="row mt-1">
                    <div class="col-12 col-md-6 col-lg-6">
                        <div class="form-group">
                            <div>
                                <label for="city"><b>City</b></label>
                                <input type="text" id="city" tabindex="4"
                                    class="form-control  @error('city') is-invalid @enderror"
                                    placeholder="" name="city" value="{{ $user->city }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-6">

                        <div class="form-group">
                            <div>
                                <label for="phone"><b>Phone Number</b></label>
                                <input type="text" id="phone" tabindex="8"
                                    class="form-control  @error('phone') is-invalid @enderror phones"
                                    name="phone" value="{{ $user->phone }}" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-12 col-md-3 col-lg-3">
                        <div class="form-group">
                            <div>
                                <label for="state"><b>State</b></label>
                                <select id="state" required tabindex="5"
                                    class="form-control  @error('state') is-invalid @enderror"
                                    placeholder="" name="state">
                                    <option value=""></option>
                                    @if (count($states))
                                        @foreach ($states as $code => $state)
                                            <option value="{{ $code }}"
                                                @if ($code === $user->state) selected @endif>
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
                                <label for="zipcode"><b>Zipcode</b></label>
                                <input type="text" id="zipcode" tabindex="6"
                                    class="form-control @error('zipcode') is-invalid @enderror zipcode"
                                    name="zipcode" value="{{$user->zipcode}}" required />
                            </div>
                        </div>
                    </div>
                    {{--@cannot('Admin', auth()->user())--}}
                        <div class="col-12 col-md-3 col-lg-3 mx-auto my-auto pt-3">
                            <div class="form-group">
                                <div>
                                    <button type="button" class="btn btn-block bg-primary text-white mt-3" data-toggle="modal" data-target="#changePasswordModal">Change Password</button>
                                </div>
                            </div>
                        </div>
                        @can('Agent', auth()->user())
                            <div class="col-12 col-md-3 col-lg-3 mx-auto my-auto pt-3">
                                <div class="form-group">
                                    <div>
                                        <button type="button" onclick="changeOffice()" class="btn btn-block bg-primary text-white mt-3">Change Office</button>
                                    </div>
                                </div>
                            </div>
                        @endcan
                    {{--@endcannot--}}
                </div>
            </div>
            <div class="text-center mt-5 mb-5">
                <button type="submit"
                    class="btn pr-5 pl-5 btn-orange text-white text-uppercase font-weight-bold"
                    id="">Submit</button>
            </div>
        </div>
    </div>

</form>
