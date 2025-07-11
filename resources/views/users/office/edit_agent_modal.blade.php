<!-- Modal -->
<form id="editOfficeAgentForm" method="POST" files=true enctype="multipart/form-data">
    @method('PATCH')
    @csrf
    <div class="modal fade" id="editOfficeAgentFormModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content auth-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Edit Agent</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @csrf
                    <div class="row mt-1">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="first_name"><b>First Name</b></label>
                                    <input type="text" id="first_name" tabindex="1"
                                        class="form-control  @error('first_name') is-invalid @enderror"
                                        name="first_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="last_name"><b>Last Name</b></label>
                                    <input type="text" id="last_name" tabindex="2"
                                        class="form-control  @error('last_name') is-invalid @enderror" name="last_name">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="address"><b>Street Address</b></label>
                                    <input type="text" id="street_address" tabindex="3"
                                        class="form-control  @error('address') is-invalid @enderror" placeholder=""
                                        name="address" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-4">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>Email</b></label>
                                    <input type="email" id="email" tabindex="7"
                                        class="form-control @error('email') is-invalid @enderror " placeholder=""
                                        name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 col-lg-2">
                            <div class="text-center margin-top-40px">
                                <a href="#" class="font-weight-bold" data-toggle="modal" data-target="#emailNotificationSettingsModalAgent" id="agentAdditionalSettingsBtn">Additional Settings...</a>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="city"><b>City</b></label>
                                    <input type="text" id="city" tabindex="4"
                                        class="form-control  @error('city') is-invalid @enderror" placeholder=""
                                        name="city" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">

                            <div class="form-group">
                                <div>
                                    <label for="phone"><b>Phone Number</b></label>
                                    <input type="text" id="phone" tabindex="8"
                                        class="form-control  @error('phone') is-invalid @enderror phones" name="phone"
                                        required>
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
                                        class="form-control  @error('state') is-invalid @enderror" placeholder=""
                                        name="state">
                                        <option value=""></option>
                                        @if (count($states))
                                            @foreach ($states as $code => $state)
                                                <option value="{{ $code }}" @if ($code === 'ID') selected @endif>
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
                                    <label for="email"><b>Zipcode</b></label>
                                    <input type="text" id="zipcode" tabindex="6"
                                        class="form-control @error('zipcode') is-invalid @enderror zipcode"
                                        name="zipcode" required />
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <div>
                                    <label for="email"><b>RE license #</b></label>
                                    <input type="text" id="re_license" tabindex="9"
                                        class="form-control @error('re_license') is-invalid @enderror "
                                        name="re_license" />
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="row mt-1">
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="inactive"><b>Account Status</b></label>
                                    <select id="inactive" tabindex="10" class="form-control  @error('inactive') is-invalid @enderror"
                                        name="inactive">
                                        <option value="0"><b class="text-muted">Active</b></option>
                                        <option value="1"><b class="text-muted">Inactive</b></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3">
                            <div class="form-group">
                                <div>
                                    <label for="agent_office"><b>Office</b></label>
                                    <select id="agent_office" required tabindex="11"
                                        class="form-control  @error('agent_office') is-invalid @enderror" placeholder=""
                                        name="agent_office">
                                        <option value="0">No Office Assigned</option>
                                        @if (count($states))
                                            @foreach ($offices as $office)
                                                <option value="{{ $office->id }}">{{ $office->user->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3 col-lg-3 mx-auto my-auto pt-3">
                            <div class="form-group">
                                <div>
                                    <button type="button" class="btn btn-block bg-primary text-white mt-3" data-toggle="modal" data-target="#changeAgentPasswordModal">Change Password</button>
                                </div>
                            </div>
                        </div>
                    </div> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="createAgentForm" value="true" class="btn btn-success"
                        id="submitEditAgentFormButton">Save</button>
                </div>
            </div>
        </div>
    </div>

</form>

{{-- @include('users.agent.change_password_modal') --}}
@include('users.notifications.agent.notification_email_modal')
@include('users.notifications.agent.add_email_modal')